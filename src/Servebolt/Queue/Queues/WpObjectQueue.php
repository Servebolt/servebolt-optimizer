<?php

namespace Servebolt\Optimizer\Queue\Queues;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\PurgeObject\PurgeObject;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\Utils\Queue\Queue;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\iterateSites;

/**
 * Class WpObjectQueue
 *
 * This initiates the queue that acts as a parent for the UrlQueue.
 *
 * @package Servebolt\Optimizer\Queue\Queues
 */
class WpObjectQueue
{
    use Singleton;

    /**
     * @var int The number of times the queue parsing should be ran per event trigger.
     */
    private $numberOfRuns = 3;

    /**
     * @var int The number of items in each chunk / "run".
     */
    private $chunkSize = 30;

    /**
     * @var Queue
     */
    public $queue;

    /**
     * @var Queue
     */
    private $urlQueue;

    /**
     * @var string
     */
    public static $queueName = 'sb-cache-purge-wp-object-queue';

    /**
     * @var string The offset used with function "strtotime" to determine whether a queue item is old enough to be handled by garbage collection.
     */
    private $timestampOffsetCleanupThreshold = 'now - 1 month';

    /**
     * WpObjectQueue constructor.
     */
    public function __construct()
    {
        //$this->queue = Queue::getInstance(self::$queueName);
        $this->queue = new Queue(self::$queueName);
    }

    /**
     * Parse the WP Object queue.
     */
    public function parseQueue(): void
    {
        for ($i = 1; $i <= $this->numberOfRuns; $i++) {
            $this->parseQueueSegment();
        }
        $this->flagItemsAsCompletedOrFailed();
        $this->cleanUpQueue();
    }

    /**
     * Resolve the URLs to purge from the payload of a WpObjectQueue-item.
     *
     * @param $payload
     * @return array|null
     */
    private function resolveUrlsToPurgeFromWpObject($payload): ?array
    {
        if (in_array($payload['type'], ['post', 'term'])) {

            if ($payload['type'] === 'post' && $originalUrl = arrayGet('original_url', $payload)) {
                add_filter('sb_optimizer_purge_by_post_original_url', function() use ($originalUrl) {
                    return $originalUrl;
                });
            }

            $purgeObject = new PurgeObject(
                arrayGet('id', $payload),
                arrayGet('type', $payload),
            );

            if ($purgeObject->success() && $urls = $purgeObject->getUrls()) {
                return $urls;
            }
            return null;
        } elseif ($payload['type'] == 'url' && $payload['url']) {
            return [$payload['url']];
        }
        return null;
    }

    /**
     * Get items to parse.
     *
     * @return array|null
     */
    public function getItemsToParse(): ?array
    {
        return $this->queue->getAndReserveItems($this->chunkSize, true);
    }

    /**
     * Get active items.
     *
     * @return array|null
     */
    public function getActiveItems(): ?array
    {
        return $this->queue->getActiveItems();
    }

    /**
     * Clear queue.
     */
    public function clearQueue(): void
    {
        $this->queue->clearQueue();
    }

    /**
     * Clear all active items from UrlQueue.
     */
    private function clearUrlQueue(): void
    {
        $this->urlQueue()->clearQueue();
    }

    /**
     * Get and reserve items (with chunk size constraint), then parse them into the UrlQueue-queue.
     */
    private function parseQueueSegment(): void
    {
        if ($items = $this->getItemsToParse()) {
            foreach ($items as $item) {
                $payload = $item->payload;
                if (arrayGet('type', $payload) === 'purge-all') {
                    if (arrayGet('networkPurge', $payload)) {
                        iterateSites(function($site) use ($item) {
                            $this->clearUrlQueue(); // Clear URL queue for each site in multisite network since we're clearing all cache
                            $this->urlQueue()->add([
                                'type' => 'purge-all',
                            ], $item);
                        }, true);
                    } else {
                        $this->clearUrlQueue();
                        $this->urlQueue()->add([
                            'type' => 'purge-all',
                        ], $item);
                    }
                    break; // We're purging all cache, so no need to continue expanding WP objects to URL items
                } elseif ($urls = $this->resolveUrlsToPurgeFromWpObject($payload)) {
                    foreach ($urls as $url) {
                        $this->urlQueue()->add([
                            'type' => 'url',
                            'url' => $url,
                        ], $item);
                    }
                }
            }
        }
    }

    /**
     * Check if a WP Object queue item has only failed child items in URL queue.
     *
     * @param $id
     * @param $queue
     * @return bool
     */
    private function itemHasOnlyFailedChildItemsInUrlQueue($id, $queue): bool
    {
        $totalChildren = $this->urlQueue()->getItemsByParent($id, $queue, null);
        $totalChildrenCount = $totalChildren ? count($totalChildren) : 0;
        $failedChildren = $this->urlQueue()->getFailedItemsByParent($id, $queue, null);
        $failedChildrenCount = $failedChildren ? count($failedChildren) : 0;
        return $totalChildrenCount === $failedChildrenCount;
    }

    /**
     * Check if a WP Object queue item has some failed child items in URL queue.
     *
     * @param $id
     * @param $queue
     * @return bool
     */
    private function itemHasSomeFailedChildItemsInUrlQueue($id, $queue): bool
    {
        $failedChildren = $this->urlQueue()->getFailedItemsByParent($id, $queue, null);
        $failedChildrenCount = $failedChildren ? count($failedChildren) : 0;
        return $failedChildrenCount > 0;
    }

    /**
     * Check if a WP Object queue item has only completed child items in URL queue.
     *
     * @param $id
     * @param $queue
     * @return bool
     */
    private function itemHasOnlyCompletedChildItemsInUrlQueue($id, $queue): bool
    {
        $totalChildren = $this->urlQueue()->getItemsByParent($id, $queue, null);
        $totalChildrenCount = $totalChildren ? count($totalChildren) : 0;
        $completedChildren = $this->urlQueue()->getCompletedItemsByParent($id, $queue, null);
        $completedChildrenCount = $completedChildren ? count($completedChildren) : 0;
        return $totalChildrenCount === $completedChildrenCount;
    }

    /**
     * Flag WP Object queue items as done as long as they have no unfinished Url Queue items.
     */
    private function flagItemsAsCompletedOrFailed(): void
    {
        if ($items = $this->queue->getReservedItems(null)) {
            foreach ($items as $item) {
                if ($this->itemHasOnlyFailedChildItemsInUrlQueue($item->id, $item->queue)) {
                    $this->queue->setItemAsFailed($item);
                } elseif ($this->itemHasSomeFailedChildItemsInUrlQueue($item->id, $item->queue)) {
                    $this->queue->setItemAsFailed($item);
                } elseif ($this->itemHasOnlyCompletedChildItemsInUrlQueue($item->id, $item->queue)) {
                    $this->queue->completeItem($item);
                }
            }
        }
    }

    /**
     * The UrlQueue queue-Instance.
     *
     * @return mixed|Queue
     */
    private function urlQueue(): object
    {
        if (!$this->urlQueue) {
            //$this->urlQueue = Queue::getInstance(UrlQueue::$queueName);
            $this->urlQueue = new Queue(UrlQueue::$queueName);
        }
        return $this->urlQueue;
    }

    /**
     * Add item to this queue with duplication protection.
     *
     * @param $itemData
     * @return object|null
     */
    public function add($itemData): ?object
    {
        if ($existingItem = $this->queue->get(serialize($itemData), 'payload', true)) {
            $this->queue->flagItemAsUpdated($existingItem);
            return $existingItem;
        }
        return $this->queue->add($itemData);
    }

    /**
     * Clean up old queue items.
     */
    private function cleanUpQueue(): void
    {
        $threshold = strtotime($this->timestampOffsetCleanupThreshold);
        if ($items = $this->queue->getOldItems($threshold)) {
            foreach ($items as $item) {
                $this->queue->delete($item);
                $this->urlQueue()->delete($item->id, 'parent_id');
            }
        }
    }

    /**
     * Check whether there is a purge all request in the queue.
     *
     * @return bool
     */
    public function hasPurgeAllRequestInQueue(): bool
    {
        if ($items = $this->queue->getActiveItems()) {
            foreach ($items as $item) {
                if (arrayGet('type', $item->payload) === 'purge-all') {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if a term is already in the queue.
     *
     * @param int $termId
     * @param string $taxonomySlug
     * @return bool
     */
    public function hasTermInQueue(int $termId, string $taxonomySlug): bool
    {
        if ($items = $this->queue->getActiveItems()) {
            foreach ($items as $item) {
                $args = arrayGet('args', $this->payload);
                if (
                    arrayGet('type', $item->payload) === 'term'
                    && arrayGet('id', $item->payload) === $termId
                    && arrayGet('taxonomySlug', $args) === $taxonomySlug
                ) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if a post is already in the queue.
     *
     * @param int $postId
     * @return bool
     */
    public function hasPostInQueue(int $postId): bool
    {
        if ($items = $this->queue->getActiveItems()) {
            foreach ($items as $item) {
                if (
                    arrayGet('type', $item->payload) === 'post'
                    && arrayGet('id', $item->payload) === $postId
                ) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if a URL is already in the queue.
     *
     * @param string $url
     * @return bool
     */
    public function hasUrlInQueue(string $url): bool
    {
        $items = $this->queue->getActiveItems();
        if ($items) {
            foreach ($items as $item) {
                if (
                    arrayGet('type', $item->payload) === 'url'
                    && arrayGet('url', $item->payload) === $url
                ) {
                    return true;
                }
            }
        }
        return false;
    }
}
