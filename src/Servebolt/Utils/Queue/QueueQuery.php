<?php

namespace Servebolt\Optimizer\Utils\Queue;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Utils\SqlBuilder\WpSqlBuilder;

/**
 * Class QueueQuery
 * @package Servebolt\Optimizer\Utils\Queue
 */
class QueueQuery extends WpSqlBuilder
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
     * Scope for items that should either be forced ro retry or that have not exceeded the maximum number of attempts.
     *
     * @param int $maxAttemptsBeforeIgnore
     * @return $this
     */
    public function whereShouldRun(int $maxAttemptsBeforeIgnore = 3)
    {
        $this->where(function($query) use ($maxAttemptsBeforeIgnore) {
            $query->whereMaxAttemptNotExceeded($maxAttemptsBeforeIgnore)
            ->orWhere(function($query) {
                $query->whereShouldForceRetry();
            });
        });
        return $this;
    }

    /**
     * Scope for items that should force a retry.
     *
     * @return $this
     */
    public function whereShouldForceRetry()
    {
        $this->where('force_retry', true);
        return $this;
    }

    /**
     * Scope for items that have not exceeded the maximum number of attempts.
     *
     * @param int $maxAttempts
     * @return $this
     */
    public function whereMaxAttemptNotExceeded(int $maxAttempts = 3)
    {
        $this->where('attempts', '<', $maxAttempts);
        return $this;
    }

    /**
     * Scope for items that have exceeded the maximum number of attempts.
     *
     * @param int $maxAttempts
     * @return $this
     */
    public function whereMaxAttemptAreExceeded(int $maxAttempts = 3)
    {
        $this->where('attempts', '>=', $maxAttempts);
        return $this;
    }

    /**
     * Scope for items that has one or more attempts.
     *
     * @return $this
     */
    public function whereHasAttempts()
    {
        $this->where('attempts', '>=', 1);
        return $this;
    }

    /**
     * Scope for items that has no previous attempts.
     *
     * @return $this
     */
    public function whereHasNoAttempts()
    {
        $this->where('attempts', '=', 0);
        return $this;
    }

    /**
     * Scope for items that have not failed.
     *
     * @return $this
     */
    public function hasNotFailed()
    {
        $this->where('failed_at_gmt', 'IS', 'NULL');
        return $this;
    }

    /**
     * Scope for items that has failed.
     *
     * @return $this
     */
    public function hasFailed()
    {
        $this->where('failed_at_gmt', 'IS NOT', 'NULL');
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

    /**
     * Scope for items that are reserved.
     *
     * @return $this
     */
    public function isReserved()
    {
        $this->where('reserved_at_gmt', 'IS NOT', 'NULL');
        return $this;
    }

    /**
     * Scope for items that are not reserved.
     *
     * @return $this
     */
    public function isNotReserved()
    {
        $this->where('reserved_at_gmt', 'IS', 'NULL');
        return $this;
    }

    /**
     * Scope for items that are completed.
     *
     * @return $this
     */
    public function isCompleted()
    {
        $this->where('completed_at_gmt', 'IS NOT', 'NULL');
        return $this;
    }

    /**
     * Scope for items that are not completed.
     *
     * @return $this
     */
    public function isNotCompleted()
    {
        $this->where('completed_at_gmt', 'IS', 'NULL');
        return $this;
    }

    /**
     * Scope for items by parent queue item.
     *
     * @param $parentId
     * @param $parentQueueName
     * @return $this
     */
    public function byParentQueueItem($parentId, $parentQueueName)
    {
        $this->where(function($query) use ($parentId, $parentQueueName) {
            $query->where('parent_id', $parentId);
            $query->andWhere('parent_queue_name', $parentQueueName);
        });
        return $this;
    }

    /**
     * Scope for items by queue.
     *
     * @param string $queueName
     * @return QueueQuery
     */
    public function byQueue(string $queueName)
    {
        return $this->where('queue', $queueName);
    }
}
