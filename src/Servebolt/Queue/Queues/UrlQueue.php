<?php

namespace Servebolt\Optimizer\Queue\Queues;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Throwable;
use Servebolt\Optimizer\CachePurge\CachePurge as CachePurgeDriver;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\Utils\Queue\Queue;

/**
 * Class UrlQueue
 * @package Servebolt\Optimizer\Queue\Queues
 */
class UrlQueue
{
    use Singleton;

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
    private $urlChunkSize;

    /**
     * @var Queue The Queue instance.
     */
    private $queue;

    /**
     * @var array Items to retry attempt on.
     */
    private $itemsToRetry;

    /**
     * @var string The name used for the URL queue.
     */
    public static $queueName = 'sb-cache-purge-url-queue';

    /**
     * UrlQueue constructor.
     */
    public function __construct()
    {
        $this->setUrlChunkSize();
        //$this->queue = Queue::getInstance(self::$queueName); // Initialize queue system
        $this->queue = new Queue(self::$queueName); // Initialize queue system
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
            $this->flagMaxAttemptedItemsAsFailed();
        }
    }

    /**
     * Get URL items from the queue system.
     *
     * @return array
     */
    private function getItemsToParse(): array
    {
        if ($this->haveUnfinishedAndPreviouslyAttemptedItems($this->urlChunkSize, true)) {
            $items = $this->itemsToRetry;
            $itemCount = count($items);
        } else {
            $items = [];
            $itemCount = 0;
        }
        if ($itemCount < $this->urlChunkSize) { // Do we have room for more items?
            $urlChunkSize = $this->urlChunkSize - $itemCount;
            if ($newItems = $this->queue->getAndReserveItems($urlChunkSize, true)) {
                $items = array_merge($items, $newItems);
            }
        }
        return $items;
    }

    /**
     * Check whether we have a purge all-request in the items-array.
     *
     * @param $items
     * @return bool
     */
    private function hasPurgeAllRequestInQueueItems($items): bool
    {
        foreach ($items as $item) {
            if ($item->payload['type'] === 'purge-all') {
                return true;
            }
        }
        return false;
    }

    /**
     * Parse URL items and send them for cache purging.
     *
     * @param $items
     */
    private function parseItems($items)
    {
        $cachePurgeDriver = CachePurgeDriver::getInstance();
        if ($this->hasPurgeAllRequestInQueueItems($items)) {
            try {
                if ($cachePurgeDriver->purgeAll()) {
                    $this->queue->completeItems($items); // We successfully purged all cache, flag all items as completed
                }
            } catch (Throwable $e) {}
        } else {
            
            $itemssplit = [
                'urls' => [],
                'tags' => [] 
            ];
            $urls = [];
            $cachetags = [];
            foreach ($items as $item) {
                switch ($item->payload['type']) {
                    case 'purge-all':
                        break;
                    case 'url':
                        $urls[] = $item->payload['url'];
                        $itemssplit['urls'][] = $item;
                        break;
                    case 'cachetag':
                        $cachetags[] = $item->payload['tag'];
                        $itemssplit['tags'][] = $item;
                }
            }
            error_log('prep');
            error_log('itemsplit: ' . print_r($itemssplit,true));
            // Trying the urls first if not empty.
            try {
                if (
                    !empty($urls)
                    && $cachePurgeDriver->purgeByUrls($urls)
                ) {
                    $this->queue->completeItems($itemssplit['urls']);
                }
            } catch (Throwable $e) {}
            // Trying to purge by cache tags only if the method exists.
            try {
                if (
                    !empty($cachetags)
                    && method_exists($cachePurgeDriver, 'purgeByTags')
                    && $cachePurgeDriver->purgeByTags($cachetags)
                ) {
                    $this->queue->completeItems($itemssplit['tags']);
                }
            } catch (Throwable $e) {}
        }
    }

    /**
     * Check if we have unfinished and previously attempted items.
     *
     * @param int|null $chunkSize
     * @param bool $doAttempt
     * @return bool
     */
    private function haveUnfinishedAndPreviouslyAttemptedItems(?int $chunkSize = 30, bool $doAttempt = false): bool
    {
        if ($itemsToRetry = $this->queue->getUnfinishedPreviouslyAttemptedItems($this->maxAttemptsPerItem, $chunkSize, $doAttempt)) {
            $this->itemsToRetry = $itemsToRetry;
            return true;
        }
        return false;
    }

    /**
     * Clear queue.
     *
     * @param bool $skipConstraintBoolean
     */
    public function clearQueue(bool $skipConstraintBoolean = false): void
    {
        $this->queue->clearQueue($skipConstraintBoolean);
    }

    /**
     * Flag items that has reached the max attempt threshold as failed.
     */
    private function flagMaxAttemptedItemsAsFailed(): void
    {
        $this->queue->flagMaxAttemptedItemsAsFailed($this->maxAttemptsPerItem);
    }

    /**
     * Set the size of the chunks of URLs sent to be purged.
     */
    private function setUrlChunkSize(): void
    {
        $urlChunkSize = CachePurgeDriver::resolveDriverName() === 'acd' ? 500 : 30;
        $this->urlChunkSize = apply_filters('sb_optimizer_url_queue_chunk_size', $urlChunkSize);
    }

    /**
     * Get the size of the chunks of URLs sent to be purged.
     *
     * @return int
     */
    public function getUrlChunkSize(): int
    {
        return $this->urlChunkSize;
    }

    /**
     * Add URL to the queue.
     *
     * @param $itemData
     * @param null $parentQueueName
     * @param null $parentId
     * @return object|null
     */
    public function add($itemData, $parentQueueName = null, $parentId = null): ?object
    {
        return $this->queue->add($itemData, $parentQueueName, $parentId);
    }
}
