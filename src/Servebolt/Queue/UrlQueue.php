<?php

namespace Servebolt\Optimizer\Queue;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge as CachePurgeDriver;

/**
 * Class UrlQueue
 * @package Servebolt\Optimizer\Queue
 */
class UrlQueue
{

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
        $this->queue->add($itemData, $parentQueueName, $parentId);
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
            $this->parseQueue();
        }
    }

    private function parseQueueSegment(): void
    {
        if ($itemsToRetry = $this->queue->getUnfinishedPreviouslyAttemptedItems()) {
            // TODO: Retry unfinished items
            return;
        }

        $items = $this->queue->getAndReserveItems($this->urlChunkSize);
        // TODO: Make sure we only do 3 attempts
        if ($items) {
            $urls = [];
            foreach ($items as $item) {
                $urls[] = $item->payload->url;
            }
            $cachePurgeDriver = CachePurgeDriver::getInstance();
            $cachePurgeDriver->purgeByUrls($urls);
            // TODO: Send purge cache, flag the items as done if they were successful
        }
    }
}
