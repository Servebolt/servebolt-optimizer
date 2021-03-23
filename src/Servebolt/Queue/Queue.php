<?php

namespace Servebolt\Optimizer\Queue;

use Servebolt\Optimizer\Traits\MultitonWithArgumentForwarding;

/**
 * Class Queue
 * @package Servebolt\Optimizer\Queue
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
     * Queue constructor.
     * @param $queueName
     */
    public function __construct($queueName)
    {
        $this->queueName = $queueName;
        $this->setTableName();
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

    public function getUnfinishedItemsByParent(int $parentId, string $parentQueueName): ?array
    {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM {$this->getTableName()} WHERE queue = %s AND parent_id = %s AND parent_queue_name = %s AND completed_at_gmt IS NULL", $this->queueName, $parentId, $parentQueueName, );
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
    public function getItems(int $chunkSize = 30, $onlyUnreserved = true)
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
     * @return QueueItem[]|null
     */
    public function getAndReserveItems(int $chunkSize = 30): ?array
    {
        $items = $this->getItems($chunkSize, true);
        $this->reserveItems($items);
        return $items;
    }

    /**
     * @param QueueItem[] $items
     * @return array
     */
    public function reserveItems(array $items): array
    {
        $items = $this->filterItemsFromOtherQueues($items);
        return array_map(function($item) {
            return $this->reserveItem($item);
        }, $items);
    }

    /**
     * @param QueueItem|int $item
     * @return QueueItem|null
     */
    public function reserveItem($item): ?object
    {
        if ($item = $this->resolveItem($item)) {
            $item->reserve();
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

    /**
     * @param string|int $identifier
     * @param string $key
     * @return null|object
     */
    public function get($identifier, string $key = 'id'): ?object
    {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM {$this->getTableName()} WHERE queue = %s AND {$key} = %s", $this->queueName, $identifier);
        $rawItem = $wpdb->get_row($sql);
        if ($rawItem) {
            return new QueueItem($rawItem);
        }
        return null;
    }

    /**
     * @param mixed|object $var
     * @return bool
     */
    private function isQueueItem($var): bool
    {
        return is_a($var, '\\Servebolt\\Optimizer\\Queue\\QueueItem');
    }

    /**
     * @param string|int $identifier
     * @param string $key
     * @return bool
     */
    public function delete($identifier, string $key = 'id'): bool
    {
        if ($this->isQueueItem($identifier)) {
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
        if ($this->isQueueItem($parentQueueName)) {
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
