<?php

namespace Servebolt\Optimizer\Queue;

class QueueItem
{

    private $id;
    private $parent_id;
    private $queue;
    private $payload;
    private $attempts;
    private $reserved_at_gmt;
    private $created_at_gmt;

    public function __construct($queueItemData)
    {
        $this->registerItemData($queueItemData);
    }

    public function isReserved(): bool
    {
        return !is_null($this->reserved_at_gmt);
    }

    public function release(): bool
    {
        if ($this->isReserved()) {
            $this->reserved_at_gmt = null;
            return true;
        }
        return false;
    }

    public function reserve(): void
    {
        if (!$this->isReserved()) { // Only allow reservation when not already reserved
            $this->reserved_at_gmt = current_time('timestamp', true);
            $this->attempts++;
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
            'queue' => $this->queue,
            'payload' => serialize($this->payload),
            'attempts' => $this->attempts,
            'reserved_at_gmt' => $this->reserved_at_gmt,
            'created_at_gmt' => $this->created_at_gmt,
        ];
    }

    /**
     * @param $name
     * @return null|mixed
     */
    public function __get($name)
    {
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
        $this->id = (int) $item->id;
        $this->parent_id = is_numeric($item->parent_id) ? (int) $item->parent_id : $item->parent_id;
        $this->queue = $item->queue;
        $this->payload = unserialize($item->payload);
        $this->attempts = $item->attempts ?: 0;
        $this->reserved_at_gmt = $item->reserved_at_gmt;
        $this->created_at_gmt = $item->created_at_gmt;
    }

}
