<?php

namespace Servebolt\Optimizer\Queue\Queues;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge as CachePurgeDriver;
use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\Queue\QueueSystem\Queue;

/**
 * Class UrlQueue
 * @package Servebolt\Optimizer\Queue\Queues
 */
class UrlQueue
{
    use Singleton;

    /**
     * @var bool Whether to allow duplicate entries in the queue.
     */
    private $allowDuplicates = false;

    /**
     * @var int The number of times the queue parsing should be ran per event trigger.
     */
    private $numberOfRuns = 3;

    /**
     * @var int The number of attempts we should do to parse each item before abandoning it.
     */
    private $maxAttemptsPerItem = 3;

    /**
     * @var int The size of the URLs being purged at a time.
     */
    private $urlChunkSize = 30;

    /**
     * @var Queue
     */
    private $queue;

    /**
     * @var Queue
     */
    private $wpObjectQueue;

    /**
     * @var string
     */
    public static $queueName = 'sb-cache-purge-url-queue';

    /**
     * UrlQueue constructor.
     */
    public function __construct()
    {
        $this->queue = Queue::getInstance(self::$queueName);
    }

    public function add($itemData, $parentQueueName = null, $parentId = null): ?object
    {
        if ($this->allowDuplicates || !$this->queue->itemExists('payload', $itemData['payload'])) {
            return $this->queue->add($itemData, $parentQueueName, $parentId);
        }
    }

    /**
     * @return mixed|Queue
     */
    private function wpObjectQueue()
    {
        if (!$this->wpObjectQueue) {
            $this->wpObjectQueue = Queue::getInstance(WpObjectQueue::$queueName);
        }
        return $this->wpObjectQueue;
    }

    /**
     * Parse the URL queue.
     */
    public function parseQueue(): void
    {
        for ($i = 1; $i <= $this->numberOfRuns; $i++) {
            if ($items = $this->getItemsToParse()) {
                $this->parseItems($items);
            }

        }
    }

    private $itemsToRetry;

    private function havePreviouslyUnfinishedButAttemptedItems()
    {
        if ($itemsToRetry = $this->queue->getUnfinishedPreviouslyAttemptedItems($this->maxAttemptsPerItem)) {
            $this->itemsToRetry = $itemsToRetry;
            return true;
        }
        return false;
    }

    private function parseItems($items)
    {
        $cachePurgeDriver = CachePurgeDriver::getInstance();
        $urls = [];
        foreach ($items as $item) {
            switch ($item->payload['type']) {
                case 'purge-all':
                    if ($item->payload['networkPurge']) {
                        $cachePurgeDriver->purgeAllNetwork();
                        // TODO: Flag item as success
                    } else {
                        $cachePurgeDriver->purgeAll();
                        // TODO: Flag item as success
                    }
                    break;
                case 'url':
                    $urls[] = $item->payload['url'];

            }
        }

        // Parse URLs
        if (!empty($urls)) {
            $cachePurgeDriver->purgeByUrls($urls);
            // TODO: Flag items as success
        }
    }

    private function getItemsToParse()
    {
        if ($this->havePreviouslyUnfinishedButAttemptedItems($this->urlChunkSize)) {
            return $this->itemsToRetry;
        }
        if ($items = $this->queue->getAndReserveItems($this->urlChunkSize)) {
            return $items;
        }
    }
}
