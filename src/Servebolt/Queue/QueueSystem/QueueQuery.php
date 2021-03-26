<?php

namespace Servebolt\Optimizer\Queue\QueueSystem;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\SqlBuilder\SqlBuilder;

/**
 * Class QueueQuery
 * @package Servebolt\Optimizer\Queue\QueueSystem
 */
class QueueQuery extends SqlBuilder
{
    /**
     * QueueQuery constructor.
     * @param string|null $tableName
     */
    public function __construct(?string $tableName = null)
    {
        parent::__construct($tableName);
    }

    /**
     * @param string $sql
     * @return array|mixed
     */
    public function getResults(string $sql)
    {
        $result = parent::getResults($sql);
        if (is_array($result)) {
            return $this->instantiateQueueItems($result);
        }
        return $result;
    }

    /**
     * @param array $rawItems
     * @return array
     */
    private function instantiateQueueItems(array $rawItems): array
    {
        return array_map(function ($rawItem) {
            return new QueueItem($rawItem);
        }, $rawItems);
    }

    /**
     * @return $this
     */
    public function whereShouldRun()
    {
        $this->where(function($query) {
            $query->whereMaxAttemptNotExceeded();
        })->orWhere(function($query) {
            $query->whereShouldForceRetry();
        });
        return $this;
    }

    /**
     * @return $this
     */
    public function whereShouldForceRetry()
    {
        $this->where('force_retry', true);
        return $this;
    }

    /**
     * @param int $maxAttempts
     * @return $this
     */
    public function whereMaxAttemptNotExceeded(int $maxAttempts = 3)
    {
        $this->where('attempts', '<=', $maxAttempts);
        return $this;
    }

    /**
     * @return $this
     */
    public function hasNotFailed()
    {
        $this->where('failed_at_gmt', 'IS', 'NULL');
        return $this;
    }

    /**
     * Get items that have not failed and are not completed.
     *
     * @return $this
     */
    public function isActive()
    {
        $this->hasNotFailed();
        $this->isNotCompleted();
        return $this;
    }

    public function isReserved()
    {
        $this->where('reserved_at_gmt', 'IS NOT', 'NULL');
        return $this;
    }

    public function isNotReserved()
    {
        $this->where('reserved_at_gmt', 'IS', 'NULL');
        return $this;
    }

    public function isCompleted()
    {
        $this->where('completed_at_gmt', 'IS NOT', 'NULL');
        return $this;
    }

    public function isNotCompleted()
    {
        $this->where('completed_at_gmt', 'IS', 'NULL');
        return $this;
    }

    public function byParentQueue($parentId, $parentQueueName)
    {
        $this->where(function($query) use ($parentId, $parentQueueName) {
            $query->where('parent_id', $parentId);
            $query->andWhere('parent_queue_name', $parentQueueName);
        });
        return $this;
    }

    public function byQueue(string $queueName)
    {
        return $this->where('queue', $queueName);
    }

    public function countAvailableItems()
    {

    }

}
