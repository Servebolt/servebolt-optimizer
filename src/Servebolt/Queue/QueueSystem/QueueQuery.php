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
    public function __construct(?string $tableName = null)
    {
        parent::__construct($tableName);
        $this->limit(30);
    }

    public function getResults(string $sql)
    {
        $result = parent::getResults($sql);
        if (is_array($result)) {
            return $this->instantiateQueueItems($result);
        }
        return $result;
    }

    private function instantiateQueueItems(array $rawItems): array
    {
        return array_map(function ($rawItem) {
            return new QueueItem($rawItem);
        }, $rawItems);
    }

    public function whereShouldRun()
    {
        $this->where(function($query) {
            $query->whereMaxAttemptNotExceeded();
        })->orWhere(function($query) {
            $query->whereForceRetry();
        });
        return $this;
    }

    public function whereForceRetry()
    {
        $this->where('force_retry', true);
        return $this;
    }

    public function whereMaxAttemptNotExceeded(int $maxAttempts = 3)
    {
        $this->where('attempts', '<=', $maxAttempts);
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
            $query->andWhere($parentQueueName);
        });
        return $this;
    }

    public function byQueue($queueName)
    {
        return $this->where('queue', $queueName);
        return $this;
    }


    public function countAvailableItems()
    {

    }

}
