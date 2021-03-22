<?php

namespace Servebolt\Optimizer\Queue;

use Servebolt\Optimizer\Traits\Multiton;

/**
 * Class Queue
 * @package Servebolt\Optimizer\Queue
 */
class Queue
{
    use Multiton;

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

    /**
     * @param int $chunkSize
     * @param bool $onlyUnreserved
     * @return QueueItem[]|null
     */
    public function getItems(int $chunkSize = 10, $onlyUnreserved = true)
    {
        global $wpdb;
        if ($onlyUnreserved) {
            $sql = $wpdb->prepare("SELECT * FROM {$this->getTableName()} WHERE queue = %s AND reserved_at_gmt IS NULL ORDER BY id DESC LIMIT {$chunkSize}", $this->queueName);
        } else {
            $sql = $wpdb->prepare("SELECT * FROM {$this->getTableName()} WHERE queue = %s ORDER BY id DESC LIMIT {$chunkSize}", $this->queueName);
        }
        $rawItems = $wpdb->get_results($sql);
        if ($rawItems) {
            $rawItems = array_map(function ($rawItem) {
                return new QueueItem($rawItem);
            }, $rawItems);
            return $rawItems;
        }
        return null;
    }

    /**
     * @param QueueItem[] $items
     */
    public function deleteItems(array $items): void
    {
        array_map(function($item) {
            $this->delete($item);
        }, $items);
    }

    /**
     * @param QueueItem[] $items
     * @return array
     */
    public function resolveItems(array $items): array
    {
        return array_map(function($item) {
            return $this->resolveItem($item);
        }, $items);
    }

    /**
     * @param int $chunkSize
     * @return QueueItem[]|null
     */
    public function getAndReserveItems(int $chunkSize = 10): ?array
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
    public function releaseItems(array $items): array
    {
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
            return $this->get($item);
        }
        return $item;
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
     * @param string|int $identifier
     * @param string $key
     * @return bool
     */
    public function delete($identifier, string $key = 'id'): bool
    {
        if (is_a($identifier, '\\Servebolt\\Optimizer\\Queue\\QueueItem')) {
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
     * @param mixed $itemData
     * @param null|int $parentId
     * @return null|QueueItem
     */
    public function add($itemData, $parentId = null): ?object
    {
        global $wpdb;
        $wpdb->insert($this->getTableName(), [
            'parent_id' => $parentId,
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

    /**
     * @return int
     */
    public function countItems(): int
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->getTableName()} WHERE queue = %s", $this->queueName));
    }
}
