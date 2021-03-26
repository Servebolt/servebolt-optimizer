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
     * Queue constructor.
     * @param $queueName
     */
    public function __construct($queueName)
    {
        $this->queueName = $queueName;
        $this->setTableName();
    }

    /**
     * @param bool $queueConstraint
     * @return QueueQuery
     */
    public function query(bool $queueConstraint = true)
    {
        $query = new QueueQuery($this->tableName);
        if ($queueConstraint) {
            $query->byQueue($this->queueName);
        }
        return $query;
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
     * @param int|null $chunkSize
     * @return array|null
     */
    public function getUnfinishedPreviouslyAttemptedItems($maxAttemptsBeforeIgnore = 3, ?int $chunkSize = 30): ?array
    {
        $query = $this->query();
        $query->whereShouldRun()
            ->isReserved()
            ->isNotCompleted();
        if ($chunkSize) {
            $query->limit($chunkSize);
        }
        if ($result = $query->result()) {
            return $result;
        }
        return null;
    }

    public function getUnfinishedItemsByParent(int $parentId, string $parentQueueName, ?int $chunkSize = 30): ?array
    {
        $query = $this->query();
        $query->isActive()
            ->byParentQueue($parentId, $parentQueueName);
        if ($chunkSize) {
            $query->limit($chunkSize);
        }
        if ($result = $query->result()) {
            return $result;
        }
        return null;
    }

    /**
     * @param int|null $chunkSize
     * @return QueueItem[]|null
     */
    public function getReservedItems(?int $chunkSize = 30): ?array
    {
        $query = $this->query();
        $query->isActive()
            ->isReserved();
        if ($chunkSize) {
            $query->limit($chunkSize);
        }
        if ($result = $query->result()) {
            return $result;
        }
        return null;
    }

    /**
     * @param int|null $chunkSize
     * @param bool $onlyUnreserved
     * @return QueueItem[]|null
     */
    public function getItems(?int $chunkSize = 30, $onlyUnreserved = true): ?array
    {
        $query = $this->query();
        if ($chunkSize) {
            $query->limit($chunkSize);
        }
        if ($onlyUnreserved) {
            $query->isNotReserved();
        }
        $query->orderBy('id', 'DESC');
        if ($result = $query->result()) {
            return $result;
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
     * @param int|null $chunkSize
     * @param bool $doAttempt
     * @return QueueItem[]|null
     */
    public function getAndReserveItems(?int $chunkSize = 30, bool $doAttempt = false): ?array
    {
        $query = $this->query();
        $query->isActive()
            ->andWhere(function($query) {
                $query->whereShouldForceRetry();
                $query->orWhere(function($query) {
                    $query->isNotReserved();
                });
            })
            ->orderBy('id', 'DESC');
            if ($chunkSize) {
                $query->limit($chunkSize);
            }
        if ($result = $query->result()) {
            $this->reserveItems($result, $doAttempt);
            return $result;
        }
        return null;
    }

    /**
     * @param QueueItem[] $items
     * @return array
     */
    public function setItemsAsFailed(array $items): array
    {
        $items = $this->filterItemsFromOtherQueues($items);
        return array_map(function($item) {
            return $this->setItemAsFailed($item);
        }, $items);
    }

    /**
     * @param QueueItem|int $item
     * @return QueueItem|null
     */
    public function setItemAsFailed($item): ?object
    {
        if ($item = $this->resolveItem($item)) {
            $item->flagAsFailed();
            if ($this->persistItem($item)) {
                return $item;
            }
        }
        return null;
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
            $item->flagAsReserved($doAttempt);
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
     * @param QueueItem|int $item
     * @return QueueItem|null
     */
    public function completeItem($item): ?object
    {
        if ($item = $this->resolveItem($item)) {
            $item->flagAsCompleted();
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
    public function flagItemsAsUpdated(array $items): array
    {
        $items = $this->filterItemsFromOtherQueues($items);
        return array_map(function($item) {
            return $this->flagItemAsUpdated($item);
        }, $items);
    }

    /**
     * @param QueueItem|int $item
     * @return QueueItem|null
     */
    public function flagItemAsUpdated($item): ?object
    {
        if ($item = $this->resolveItem($item)) {
            $item->flagAsUpdated();
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
     * @param bool $ignoreCompleted
     * @param bool $ignoreFailed
     * @return null|object
     */
    public function get(
        $identifier,
        string $key = 'id',
        bool $ignoreCompleted = false,
        bool $ignoreFailed = true
    ): ?object
    {
        $query = $this->query();
        $query->where($key, $identifier);
        if ($ignoreFailed) {
            $query->hasNotFailed(); // Ignore failed items
        }
        if ($ignoreCompleted) {
            $query->isNotCompleted();
        }
        if ($item = $query->first()) {
            return $item;
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
     * @param bool|callable $constraintClosureOrSkipConstraintBoolean
     * @return bool
     */
    public function clearQueue($constraintClosureOrSkipConstraintBoolean = false)
    {
        $query = $this->query();
        $query->delete();
        if (is_callable($constraintClosureOrSkipConstraintBoolean)) {
            $constraintClosureOrSkipConstraintBoolean($query);
        } elseif ($constraintClosureOrSkipConstraintBoolean === false) {
            $query->hasNotFailed()
                ->isNotCompleted();
        }
        $query->run();
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
        $query = $this->query();
        $query->selectCount();
        return $query->getVar();
    }

    public function countAvailableItems(): int
    {
        $query = $this->query();
        $query->selectCount()
            ->isNotReserved();
        return $query->getVar();
    }

    public function countReservedItems(): int
    {
        $query = $this->query();
        $query->selectCount()
            ->isReserved()
            ->isNotCompleted();
        return $query->getVar();
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
        $query = $this->query();
        $query->selectCount()
            ->isReserved()
            ->isCompleted();
        return $query->getVar();
    }
}
