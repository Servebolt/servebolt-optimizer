<?php

namespace Servebolt\Optimizer\Queue\QueueSystem;

use Servebolt\Optimizer\Traits\MultitonWithArgumentForwarding;
use function Servebolt\Optimizer\Helpers\isQueueItem;

/**
 * Class Queue
 * @package Servebolt\Optimizer\Queue\QueueSystem
 */
class Queue
{
    use MultitonWithArgumentForwarding;

    /**
     * @var string Table name.
     */
    private $tableName;

    /**
     * @var string Queue name.
     */
    private $queueName;

    /**
     * @var QueueQuery Query instance.
     */
    public $query;

    /**
     * Queue constructor.
     * @param $queueName
     */
    public function __construct($queueName)
    {
        $this->queueName = $queueName;
        $this->setTableName();
    }

    public function query()
    {
        return new QueueQuery($this->tableName);
    }

    private function setTableName(): void
    {
        global $wpdb;
        $this->tableName = $wpdb->prefix . 'sb_queue';
    }

    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    /**
     * Get reserved unfinished items that have not been attempted more than $maxAttemptsBeforeIgnore.
     *
     * @param int $maxAttemptsBeforeIgnore
     * @param int $chunkSize
     * @return array|null
     */
    public function getUnfinishedPreviouslyAttemptedItems($maxAttemptsBeforeIgnore = 3, int $chunkSize = 30): ?array
    {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM {$this->getTableName()} WHERE queue = %s AND (attempts <= %s OR force_retry = 1) AND reserved_at_gmt IS NOT NULL AND completed_at_gmt IS NULL LIMIT {$chunkSize}", $this->queueName, $maxAttemptsBeforeIgnore);
        $rawItems = $wpdb->get_results($sql);
        if ($rawItems) {
            return $this->instantiateQueueItems($rawItems);
        }
        return null;
    }

    public function getUnfinishedItemsByParent(int $parentId, string $parentQueueName): ?array
    {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM {$this->getTableName()} WHERE queue = %s AND parent_id = %s AND parent_queue_name = %s AND reserved_at_gmt IS NOT NULL AND completed_at_gmt IS NULL", $this->queueName, $parentId, $parentQueueName);
        $rawItems = $wpdb->get_results($sql);
        if ($rawItems) {
            return $this->instantiateQueueItems($rawItems);
        }
        return null;
    }

    /**
     * @return QueueItem[]|null
     */
    public function getReservedItems(): ?array
    {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM {$this->getTableName()} WHERE queue = %s AND reserved_at_gmt IS NOT NULL", $this->queueName);
        $rawItems = $wpdb->get_results($sql);
        if ($rawItems) {
            return $this->instantiateQueueItems($rawItems);
        }
        return null;
    }

    private function instantiateQueueItems(array $rawItems)
    {
        return array_map(function ($rawItem) {
            return new QueueItem($rawItem);
        }, $rawItems);
    }

    /**
     * @param int $chunkSize
     * @param bool $onlyUnreserved
     * @return QueueItem[]|null
     */
    public function getItems(int $chunkSize = 30, $onlyUnreserved = true): ?array
    {
        global $wpdb;
        if ($onlyUnreserved) {
            $sql = $wpdb->prepare("SELECT * FROM {$this->getTableName()} WHERE queue = %s AND reserved_at_gmt IS NULL ORDER BY id DESC LIMIT {$chunkSize}", $this->queueName);
        } else {
            $sql = $wpdb->prepare("SELECT * FROM {$this->getTableName()} WHERE queue = %s ORDER BY id DESC LIMIT {$chunkSize}", $this->queueName);
        }
        $rawItems = $wpdb->get_results($sql);
        if ($rawItems) {
            return $this->instantiateQueueItems($rawItems);
        }
        return null;
    }

    /**
     * @param QueueItem[] $items
     */
    public function deleteItems(array $items): void
    {
        $items = $this->filterItemsFromOtherQueues($items);
        array_map(function($item) {
            $this->delete($item);
        }, $items);
    }

    /**
     * @param int $chunkSize
     * @param bool $doAttempt
     * @return QueueItem[]|null
     */
    public function getAndReserveItems(int $chunkSize = 30, bool $doAttempt = false): ?array
    {
        $items = $this->getItems($chunkSize, true);
        if ($items) {
            $this->reserveItems($items, $doAttempt);
        }
        return $items;
    }

    /**
     * @param QueueItem[] $items
     * @param bool $doAttempt
     * @return array
     */
    public function reserveItems(array $items, bool $doAttempt = false): array
    {
        $items = $this->filterItemsFromOtherQueues($items);
        return array_map(function($item) use ($doAttempt) {
            return $this->reserveItem($item, $doAttempt);
        }, $items);
    }

    /**
     * @param QueueItem|int $item
     * @param bool $doAttempt
     * @return QueueItem|null
     */
    public function reserveItem($item, bool $doAttempt = false): ?object
    {
        if ($item = $this->resolveItem($item)) {
            $item->reserve($doAttempt);
            if ($this->persistItem($item)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * @param $item
     * @return object|null
     */
    public function doAttempt($item): ?object
    {
        if ($item = $this->resolveItem($item)) {
            $item->doAttempt();
            if ($this->persistItem($item)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * @param QueueItem[] $items
     * @return array
     */
    public function completeItems(array $items): array
    {
        $items = $this->filterItemsFromOtherQueues($items);
        return array_map(function($item) {
            return $this->completeItem($item);
        }, $items);
    }

    /**
     * @param array $array
     * @return array
     */
    private function filterItemsFromOtherQueues(array $array): array
    {
        return array_filter($array, function($item) {
            return $this->queueName === $item->queue;
        });
    }

    /**
     * @param QueueItem|int $item
     * @return QueueItem|null
     */
    public function completeItem($item): ?object
    {
        if ($item = $this->resolveItem($item)) {
            $item->complete();
            if ($this->persistItem($item)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * @param QueueItem[] $items
     * @return array
     */
    public function releaseItems(array $items): array
    {
        $items = $this->filterItemsFromOtherQueues($items);
        return array_map(function($item) {
            return $this->releaseItem($item);
        }, $items);
    }

    /**
     * @param QueueItem|int $item
     * @return QueueItem|null
     */
    public function releaseItem($item): ?object
    {
        if ($item = $this->resolveItem($item)) {
            $item->release();
            if ($this->persistItem($item)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * @param QueueItem|int $item
     * @return QueueItem|null
     */
    private function resolveItem($item): ?object
    {
        if (is_int($item)) {
            $item = $this->get($item);
        }
        if ($this->queueName === $item->queue) {
            return $item;
        }
        return null;
    }

    /**
     * @param QueueItem|int $item
     * @return bool
     */
    private function persistItem($item): bool
    {
        if ($item = $this->resolveItem($item)) {
            return $this->update($item) !== false;
        }
        return false;
    }

    /**
     * @param $item
     * @return bool|int
     */
    public function update($item)
    {
        global $wpdb;
        return $wpdb->update(
            $this->getTableName(),
            $item->buildItemData(),
            ['id' => $item->id]
        );
    }

    /*
    public function itemQuery(array $args)
    {
        $rawSql = "SELECT * FROM {$this->getTableName()} WHERE 1=1";

        $isReserved = isset($args['isReserved']);
        $isNotReserved = isset($args['isNotReserved']);
        $isCompleted = isset($args['isReserved']);
        $isNotCompleted = isset($args['isNotReserved']);

        $where = isset($args['where']) ? $args['where'] : [];
        $order = isset($args['order']) ? $args['order'] : false;
        $orderBy = isset($args['orderBy']) ? $args['orderBy'] : false;
        $limit = isset($args['limit']) ? $args['limit'] : false;

        foreach ($where as $queryItem) {
            $prepareArguments[] = $queryItem['value'];
            $operator = isset($args['operator']) ? $args['operator'] : '=';
            $rawSql .= " AND {$queryItem['key']} {$operator} %s";
        }

        if ($order) {
            $rawSql .= " ORDER BY {$order}";
            if ($orderBy) {
                $rawSql .= " {$orderBy}";
            }
        }

        if ($limit) {
            $rawSql .= " LIMIT {$limit}";
        }

        global $wpdb;
        $sql = $wpdb->prepare($rawSql, $prepareArguments);
        $rawItem = $wpdb->get_row($sql);
        if ($rawItem) {
            return new QueueItem($rawItem);
        }
        return null;
    }
    */

    /**
     * @param string|int $identifier
     * @param string $key
     * @param bool $ignoreExpired
     * @return null|object
     */
    public function get($identifier, string $key = 'id', $ignoreExpired = true): ?object
    {
        global $wpdb;
        if ($ignoreExpired) {
            $sql = $wpdb->prepare("SELECT * FROM {$this->getTableName()} WHERE queue = %s AND {$key} = %s", $this->queueName, $identifier);
        } else {
            $sql = $wpdb->prepare("SELECT * FROM {$this->getTableName()} WHERE queue = %s AND {$key} = %s", $this->queueName, $identifier);
        }
        $rawItem = $wpdb->get_row($sql);
        if ($rawItem) {
            return new QueueItem($rawItem);
        }
        return null;
    }

    /**
     * @param string|int $identifier
     * @param string $key
     * @return bool
     */
    public function delete($identifier, string $key = 'id'): bool
    {
        if (isQueueItem($identifier)) {
            $key = 'id';
            $identifier = $identifier->id;
        }
        global $wpdb;
        return $wpdb->delete($this->getTableName(), [
            'queue' => $this->queueName,
            $key => $identifier,
        ]) !== false;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->countItems() === 0;
    }

    /**
     * Clear all queue items.
     *
     * @return bool
     */
    public function clearQueue(): bool
    {
        global $wpdb;
        return $wpdb->delete($this->getTableName(), [
                'queue' => $this->queueName,
            ]) !== false;
    }

    /**
     * @param mixed $itemData
     * @param null|string $parentQueueName
     * @param null|int $parentId
     * @return null|QueueItem
     */
    public function add($itemData, $parentQueueName = null, $parentId = null): ?object
    {
        if (isQueueItem($parentQueueName)) {
            $parentId = $parentQueueName->id;
            $parentQueueName = $parentQueueName->queue;
        }
        if ($parentQueueName === $this->queueName) { // Parent element can't be in the same queue as child element
            $parentId = null;
            $parentQueueName = null;
        }
        global $wpdb;
        $wpdb->insert($this->getTableName(), [
            'parent_id' => $parentId,
            'parent_queue_name' => $parentQueueName,
            'queue' => $this->queueName,
            'payload' => serialize($itemData),
            'attempts' => 0,
            'created_at_gmt' => current_time('timestamp', true),
        ]);
        return $this->get($wpdb->insert_id);
    }

    /**
     * @param string|int $identifier
     * @param string $key
     * @return bool
     */
    public function itemExists($identifier, string $key = 'id'): bool
    {
        return $this->get($identifier, $key) !== null;
    }

    public function countItems(): int
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->getTableName()} WHERE queue = %s", $this->queueName));
    }

    public function countAvailableItems(): int
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->getTableName()} WHERE queue = %s AND reserved_at_gmt IS NULL", $this->queueName));
    }

    public function countReservedItems(): int
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->getTableName()} WHERE queue = %s AND reserved_at_gmt IS NOT NULL AND completed_at_gmt IS NULL ", $this->queueName));
    }

    public function hasItems(): bool
    {
        return $this->countItems() > 0;
    }

    public function hasAvailable(): bool
    {
        return $this->countAvailableItems() > 0;
    }

    public function allCompleted(): bool
    {
        $this->countItems() === $this->countCompletedItems();
    }

    public function countCompletedItems(): int
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->getTableName()} WHERE queue = %s AND reserved_at_gmt IS NOT NULL AND completed_at_gmt IS NOT NULL ", $this->queueName));
    }
}
