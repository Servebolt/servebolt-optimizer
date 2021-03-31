<?php

namespace Servebolt\Optimizer\Queue\QueueSystem;

use function Servebolt\Optimizer\Helpers\camelCaseToSnakeCase;

/**
 * Class QueueItem
 * @package Servebolt\Optimizer\Queue\QueueSystem
 */
class QueueItem
{

    private $id;
    private $parent_id;
    private $parent_queue_name;
    private $queue;
    private $payload;
    private $attempts;
    private $force_retry;
    private $failed_at_gmt;
    private $reserved_at_gmt;
    private $completed_at_gmt;
    private $updated_at_gmt;
    private $created_at_gmt;

    /**
     * QueueItem constructor.
     * @param $queueItemData
     */
    public function __construct($queueItemData)
    {
        $this->registerItemData($queueItemData);
    }

    public function isReserved(): bool
    {
        return !is_null($this->reserved_at_gmt);
    }

    public function isCompleted(): bool
    {
        return !is_null($this->completed_at_gmt);
    }

    /**
     * @return QueueItem|null
     */
    public function getParentItem(): ?object
    {
        $parentQueue = Queue::getInstance($this->parent_queue_name, $this->parent_queue_name);
        if ($parentItem = $parentQueue->get($this->parent_id)) {
            return $parentItem;
        }
        return null;
    }

    /**
     * @param QueueItem $parentItem
     */
    public function addParent($parentItem): void
    {
        $this->parent_id = $parentItem->id;
        $this->parent_queue_name = $parentItem->queue;
    }

    public function release(): bool
    {
        if ($this->isReserved()) {
            $this->reserved_at_gmt = null;
            return true;
        }
        return false;
    }

    public function flagAsCompleted()
    {
        $this->flagAsReserved(); // Make sure to reserve before completing
        $this->completed_at_gmt = current_time('timestamp', true);
    }

    public function doAttempt(): void
    {
        $this->attempts++;
    }

    public function flagAsFailed(): void
    {
        $this->failed_at_gmt = current_time('timestamp', true);
    }

    public function flagAsUpdated(): void
    {
        $this->updated_at_gmt = current_time('timestamp', true);
    }

    public function flagAsReserved(bool $doAttempt = false): void
    {
        if (!$this->isReserved()) { // Only allow reservation when not already reserved
            $this->reserved_at_gmt = current_time('timestamp', true);
            if ($doAttempt) {
                $this->doAttempt();
            }
        }
    }

    private function getTableName(): ?string
    {
        $queueInstance = Queue::getInstance($this->queue, $this->queue);
        return $queueInstance->getTableName();
    }

    public function buildItemData(): array
    {
        return [
            'parent_id' => $this->parent_id,
            'parent_queue_name' => $this->parent_queue_name,
            'queue' => $this->queue,
            'payload' => serialize($this->payload),
            'attempts' => $this->attempts,
            'force_retry' => $this->force_retry ?: false,
            'failed_at_gmt' => $this->failed_at_gmt,
            'reserved_at_gmt' => $this->reserved_at_gmt,
            'completed_at_gmt' => $this->completed_at_gmt,
            'updated_at_gmt' => $this->updated_at_gmt,
            'created_at_gmt' => $this->created_at_gmt,
        ];
    }

    /**
     * @param $name
     * @return null|mixed
     */
    public function __get($name)
    {
        $name = camelCaseToSnakeCase($name);
        if (isset($this->{$name})) {
            return $this->{$name};
        } elseif (isset($this->payload->{$name})) {
            return $this->payload->{$name};
        }
        return null;
    }

    /**
     * @param $item
     */
    private function registerItemData($item): void
    {
        $this->id = isset($item->id) ? (int) $item->id : null;
        $this->parent_id = isset($item->parent_id) ? (int) $item->parent_id : $item->parent_id;
        $this->parent_queue_name = $item->parent_queue_name;
        $this->queue = $item->queue;
        $this->payload = unserialize($item->payload);
        $this->attempts = $item->attempts ?: 0;
        $this->force_retry = $item->force_retry ?: false;
        $this->failed_at_gmt = $item->failed_at_gmt;
        $this->reserved_at_gmt = $item->reserved_at_gmt;
        $this->completed_at_gmt = $item->completed_at_gmt;
        $this->updated_at_gmt = $item->updated_at_gmt;
        $this->created_at_gmt = $item->created_at_gmt;
    }

}
