<?php

namespace Servebolt\Optimizer\Utils\Queue;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\MultitonWithArgumentForwarding;
use function Servebolt\Optimizer\Helpers\isQueueItem;

/**
 * Class Queue
 * @package Servebolt\Optimizer\Utils\Queue
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
     * Return new QueueQuery-instance with optional queue constraint.
     *
     * @param bool $queueConstraint Whether to constrain the query to only cover the current queue.
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

    /**
     * Set queue table name.
     */
    private function setTableName(): void
    {
        global $wpdb;
        $this->tableName = $wpdb->prefix . 'sb_queue';
    }

    /**
     * Get queue table name.
     *
     * @return string|null
     */
    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    /**
     * Get reserved unfinished items that have not been attempted more than $maxAttemptsBeforeIgnore.
     *
     * @param int $maxAttemptsBeforeIgnore The maximum number of attempts for an item before it should be ignored.
     * @param int|null $chunkSize The maximum number of items that should be returned.
     * @param bool $doAttempt
     * @return array|null
     */
    public function getUnfinishedPreviouslyAttemptedItems($maxAttemptsBeforeIgnore = 3, ?int $chunkSize = 30, bool $doAttempt = false): ?array
    {
        $query = $this->query();

        $query->whereShouldRun($maxAttemptsBeforeIgnore)
            ->isReserved()
            ->whereHasAttempts()
            ->isNotCompleted()
            ->hasNotFailed();

        if ($chunkSize) {
            $query->limit($chunkSize);
        }
        if ($result = $query->result()) {
            if ($doAttempt) {
                $this->doAttempts($result); // Increase the number of attempts by 1
            }
            return $result;
        }
        return null;
    }

    /**
     * Get completed items.
     *
     * @param int|null $chunkSize The maximum number of items that should be returned.
     * @return array|null
     */
    public function getCompletedItems(?int $chunkSize = 30): ?array
    {
        $query = $this->query();
        $query->isCompleted();
        if ($chunkSize) {
            $query->limit($chunkSize);
        }
        if ($result = $query->result()) {
            return $result;
        }
        return null;
    }

    /**
     * Get completed items by parent queue item.
     *
     * @param int $parentId The ID of the parent queue item.
     * @param string $parentQueueName The name of the parent queue.
     * @param int|null $chunkSize The maximum number of items that should be returned.
     * @return array|null
     */
    public function getCompletedItemsByParent(int $parentId, string $parentQueueName, ?int $chunkSize = 30): ?array
    {
        $query = $this->query();
        $query->isCompleted()
            ->byParentQueueItem($parentId, $parentQueueName);
        if ($chunkSize) {
            $query->limit($chunkSize);
        }
        if ($result = $query->result()) {
            return $result;
        }
        return null;
    }

    /**
     * Get failed by parent queue item.
     *
     * @param int $parentId The ID of the parent queue item.
     * @param string $parentQueueName The name of the parent queue.
     * @param int|null $chunkSize The maximum number of items that should be returned.
     * @return array|null
     */
    public function getFailedItemsByParent(int $parentId, string $parentQueueName, ?int $chunkSize = 30): ?array
    {
        $query = $this->query();
        $query->hasFailed()
            ->isNotCompleted()
            ->byParentQueueItem($parentId, $parentQueueName);
        if ($chunkSize) {
            $query->limit($chunkSize);
        }
        if ($result = $query->result()) {
            return $result;
        }
        return null;
    }

    /**
     * Get items by parent queue item.
     *
     * @param int $parentId The ID of the parent queue item.
     * @param string $parentQueueName The name of the parent queue.
     * @param int|null $chunkSize The maximum number of items that should be returned.
     * @return array|null
     */
    public function getItemsByParent(int $parentId, string $parentQueueName, ?int $chunkSize = 30): ?array
    {
        $query = $this->query();
        $query->byParentQueueItem($parentId, $parentQueueName);
        if ($chunkSize) {
            $query->limit($chunkSize);
        }
        if ($result = $query->result()) {
            return $result;
        }
        return null;
    }

    /**
     * Get items older than given timestamp.
     *
     * @param $timestampThreshold
     * @param int|null $chunkSize The maximum number of items that should be returned.
     * @return mixed|null
     */
    public function getOldItems($timestampThreshold, ?int $chunkSize = 30): ?array
    {
        $query = $this->query();
        $query->where('created_at_gmt', '<=', $timestampThreshold);
        if ($chunkSize) {
            $query->limit($chunkSize);
        }
        if ($result = $query->result()) {
            return $result;
        }
        return null;
    }

    /**
     * Get reserved items.
     *
     * @param int|null $chunkSize The maximum number of items that should be returned.
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
     * Get items.
     *
     * @param int|null $chunkSize The maximum number of items that should be returned.
     * @param bool $onlyUnreserved Whether to only get unreserved items.
     * @return QueueItem[]|null
     */
    public function getItems(?int $chunkSize = 30, bool $onlyUnreserved = true): ?array
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
     * Delete given queue items.
     *
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
     * Get active items.
     *
     * @param int|null $chunkSize The maximum number of items that should be returned.
     * @return mixed|null
     */
    public function getActiveItems(?int $chunkSize = 30): ?array
    {
        $query = $this->query();
        $query->isActive()
            ->orderBy('id', 'DESC');
        if ($chunkSize) {
            $query->limit($chunkSize);
        }
        if ($result = $query->result()) {
            return $result;
        }
        return null;
    }

    /**
     * Get and reserve items.
     *
     * @param int|null $chunkSize The maximum number of items that should be returned.
     * @param bool $doAttempt Whether to increment the number of attempts for the items returned in the query.
     * @return QueueItem[]|null
     */
    public function getAndReserveItems(?int $chunkSize = 30, bool $doAttempt = false): ?array
    {
        $query = $this->query();
        $query->isActive()
            ->whereHasNoAttempts()
            ->isNotReserved()
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
     * Flag items that has reached the max attempt threshold as failed.
     *
     * @param int $maxAttempts
     * @return array|null
     */
    public function flagMaxAttemptedItemsAsFailed(int $maxAttempts = 3): ?array
    {
        $query = $this->query();
        $query->isActive()
            ->whereMaxAttemptAreExceeded($maxAttempts);
        // Setting limit of returned numbers and columns.            
        $query->limit(30);
        $query->select('id');
        if ($result = $query->result()) {
            // Double check its reset.
            $query->resetLimitAndColumns();
            $this->setItemsAsFailed($result);
        }
        // Setting limit of returned numbers and columns.
        $query->resetLimitAndColumns();
        return null;
    }

    /**
     * Set multiple items as failed.
     *
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
     * Set item as failed.
     *
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
     * Reserve multiple items.
     *
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
     * Reserve item.
     *
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
     * Increment the number of attempts on multiple items.
     *
     * @param $items
     */
    public function doAttempts($items): void
    {
        array_map(function($item) {
            $this->doAttempt($item);
        }, $items);
    }

    /**
     * Increment the number of attempts on an item.
     *
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
     * Flat multiple items as completed.
     *
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
     * Flag item as completed.
     *
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
     * Flag multiple items as updated.
     *
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
     * Flag item as updated.
     *
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
     * Release/unreserve multiple items.
     *
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
     * Release/unreserve item.
     *
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
     * Filter away any queue items from other queues than the one specified in the queue instance.
     *
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
     * Resolve the queue item.
     *
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
     * Persist queue item object to the queue.
     *
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
     * Update item in queue.
     *
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
     * Get item from queue.
     *
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
     * Delete item from queue.
     *
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
     * Check whether the queue is empty.
     *
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
     * Add an item to the queue.
     *
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
     * Check whether an item exists in the queue.
     *
     * @param string|int $identifier
     * @param string $key
     * @return bool
     */
    public function itemExists($identifier, string $key = 'id'): bool
    {
        return $this->get($identifier, $key) !== null;
    }

    /**
     * Count items in the queue.
     *
     * @return int
     */
    public function countItems(): int
    {
        $query = $this->query();
        $query->selectCount();
        return $query->getVar();
    }

    /**
     * Count available items.
     *
     * @return int
     */
    public function countAvailableItems(): int
    {
        $query = $this->query();
        $query->selectCount()
            ->isNotReserved();
        return $query->getVar();
    }

    /**
     * Count reserved items.
     *
     * @return int
     */
    public function countReservedItems(): int
    {
        $query = $this->query();
        $query->selectCount()
            ->isReserved()
            ->isNotCompleted();
        return $query->getVar();
    }

    /**
     * Check if queue has items.
     *
     * @return bool
     */
    public function hasItems(): bool
    {
        return $this->countItems() > 0;
    }

    /**
     * CHeck if queue has available items.
     *
     * @return bool
     */
    public function hasAvailable(): bool
    {
        return $this->countAvailableItems() > 0;
    }

    /**
     * Check if all items in the queue are completed.
     *
     * @return bool
     */
    public function allCompleted(): bool
    {
        $this->countItems() === $this->countCompletedItems();
    }

    /**
     * Count completed items in the queue.
     *
     * @return int
     */
    public function countCompletedItems(): int
    {
        $query = $this->query();
        $query->selectCount()
            ->isReserved()
            ->isCompleted();
        return $query->getVar();
    }
}
