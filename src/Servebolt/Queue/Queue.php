<?php

namespace Servebolt\Optimizer\Queue;

use Servebolt\Optimizer\Traits\Multiton;

class Queue
{

    use Multiton;

    private string $tableName;

    private string $queueName;

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
     * @param QueueItem|int $item
     * @return bool|QueueItem
     */
    public function reserveItem($item)
    {
        if ($item = $this->resolveItem($item)) {
            $item->reserve();
            if ($this->persistItem($item)) {
                return $item;
            }
        }
        return false;
    }

    /**
     * @param QueueItem|int $item
     * @return bool|QueueItem
     */
    public function releaseItem($item)
    {
        if ($item = $this->resolveItem($item)) {
            $item->release();
            if ($this->persistItem($item)) {
                return $item;
            }
        }
        return false;
    }

    /**
     * @param QueueItem|int $item
     * @return QueueItem|null
     */
    private function resolveItem($item): ?QueueItem
    {
        if (is_int($item)) {
            return $this->get($item);
        }
        return $item;
    }

    private function persistItem($item): bool
    {
        if ($item = $this->resolveItem($item)) {
            global $wpdb;
            return $wpdb->update(
                $this->getTableName(),
                $item->buildItemData(),
                ['id' => $item->id]
            ) !== false;
        }
        return false;
    }

    /**
     * @param string|int $identifier
     * @param string $key
     * @return array|object|void|null
     */
    public function get($identifier, string $key = 'id'): ?QueueItem
    {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM {$this->getTableName()} WHERE {$key} = %s", $identifier);
        $rawItem = $wpdb->get_row($sql);
        if ($rawItem) {
            $queueItem = new QueueItem($rawItem);
            return $queueItem;
        }
        return null;
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
        return $wpdb->delete($this->getTableName(), [$key => $identifier]) !== false;
    }

    public function countItems(): int
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->getTableName()} WHERE queue = %s", $this->queueName));
    }

    /**
     * @param mixed $itemData
     * @param null|int $parentId
     * @return array|object|void|null
     */
    public function add($itemData, $parentId = null)
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
}
