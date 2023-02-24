<?php

namespace Servebolt\Optimizer\Queue\Queues;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\PurgeObject\PurgeObject;
use Servebolt\Optimizer\Traits\Multiton;
use Servebolt\Optimizer\Utils\Queue\Queue;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\convertOriginalUrlToString;

/**
 * Class WpObjectQueue
 *
 * This initiates the queue that acts as a parent for the UrlQueue.
 *
 * @package Servebolt\Optimizer\Queue\Queues
 */
class WpObjectQueue
{
    use Multiton;

    /**
     * @var int The number of times the queue parsing should be ran per event trigger.
     */
    private $numberOfRuns = 3;

    /**
     * @var int The number of items in each chunk / "run".
     */
    private $chunkSize = 30;

    /**
     * @var bool Whether to clear WP object and URL queue when a purge all-request is added to the queue.
     */
    private $clearQueueOnPurgeAll = true;

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
        $output = [
            'urls' => [],
            'tags' => [],
        ];
        
        if (in_array($payload['type'], ['post', 'term', 'cachetag'])) {

            if ($payload['type'] === 'post' && $originalUrl = arrayGet('original_url', $payload)) {
                add_filter('sb_optimizer_purge_by_post_original_url', function() use ($originalUrl) {
                    $originalUrl = convertOriginalUrlToString($originalUrl);
                    $output['urls'][] = $originalUrl;
                    return $output;
                });
            }

            // The 'cachetag' type is investigated and adapted in the PurgeObject.
            $purgeObject = new PurgeObject(
                arrayGet('id', $payload),
                arrayGet('type', $payload),
            );

            if ($purgeObject->success()) {
                
                // Handle simple purge - purging of only object URL without full URL hierachy.
                if (arrayGet('simplePurge', $payload) === true) {
                    $output['urls'][] = $purgeObject->getBaseUrl();
                    return $output;
                }
                // Add urls if they exist.
                if ($urls = $purgeObject->getUrls()) {
                    $output['urls'] = $urls;
                
                }
                // Add tags if they exist.
                if( $tags = $purgeObject->getCacheTags()) {
                    $output['tags'] = $tags;
                }
                // if there is something to share, return it.
                if(!empty($output['tags']) || !empty($output['urls'])) return $output;

            }
            return null;
        } elseif ($payload['type'] == 'url' && !empty($payload['url'])) {
            return $output['urls'][] = convertOriginalUrlToString($payload['url']);
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
     *
     * @param bool $skipConstraintBoolean
     */
    public function clearQueue(bool $skipConstraintBoolean = false): void
    {
        $this->queue->clearQueue($skipConstraintBoolean);
    }

    /**
     * Clear all active items from UrlQueue.
     */
    private function clearUrlQueue(): void
    {
        $this->urlQueue()->clearQueue();
    }

    /**
     * Check whether a queue item is a purge all request.
     *
     * @param $item
     * @return bool
     */
    private function isPurgeAll($item): bool
    {
        return arrayGet('type', $item) === 'purge-all';
    }

    /**
     * Check whether a WP Object queue item is a purge all request.
     *
     * @param $item
     * @return bool
     */
    private function queueItemIsPurgeAll($item): bool
    {
        $payload = $item->payload;
        if ($payload && $this->isPurgeAll($payload)) {
            return true;
        }
        return false;
    }

    /**
     * Get and reserve items (with chunk size constraint), then parse them into the UrlQueue-queue.
     */
    private function parseQueueSegment(): void
    {
        if ($items = $this->getItemsToParse()) {
            foreach ($items as $item) {
                if ($this->queueItemIsPurgeAll($item)) {
                    $this->urlQueue()->add([
                        'type' => 'purge-all',
                    ], $item);
                    break; // We're purging all cache, so no need to continue expanding WP objects to URL items
                } elseif ($output = $this->resolveUrlsToPurgeFromWpObject($item->payload)) {
                    if(!empty($output['urls'])){
                        foreach ($output['urls'] as $url) {
                            $this->urlQueue()->add([
                                'type' => 'url',
                                'url' => $url,
                            ], $item);
                        }
                    }
                    if(!empty($output['tags'])){
                        foreach($output['tags'] as $tag) {
                            $this->urlQueue()->add([
                                'type' => 'cachetag',
                                'tag' => $tag,
                            ], $item);
                        }
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
        $payload = serialize($itemData);
        // Added unique ID for purge items, should be faster with large queues due to indexing.
        if($existingItem = $this->queue->get(hash('sha256', $payload), 'UID', true)) {
            $this->queue->flagItemAsUpdated($existingItem);
            return $existingItem;
        }
        // fall back to payload looking the same
        if ($existingItem = $this->queue->get($payload, 'payload', true)) {
            $this->queue->flagItemAsUpdated($existingItem);
            return $existingItem;
        }
        if (
            $this->isPurgeAll($itemData)
            && $this->clearQueueOnPurgeAll
        ) {
            $this->clearQueue(); // Clear all WP objects since we're gonna purge all cache
            $this->clearUrlQueue(); // Clear all URLs since we're gonna purge all cache
        }
        return $this->queue->add($itemData);
    }

    /**
     * Clean up queue.
     */
    private function cleanUpQueue(): void
    {
        $this->cleanupCompletedItems();
        $this->cleanupOldAndPossiblyFailedItems();
    }

    /**
     * Cleanup completed queue items.
     */
    private function cleanupCompletedItems(): void
    {
        if ($items = $this->queue->getCompletedItems(null)) {
            foreach ($items as $item) {
                $this->queue->delete($item);
                $this->urlQueue()->delete($item->id, 'parent_id');
            }
        }
    }

    /**
     * Cleanup old (and possibly failed) items.
     */
    private function cleanupOldAndPossiblyFailedItems(): void
    {
        $threshold = strtotime($this->timestampOffsetCleanupThreshold);
        if ($items = $this->queue->getOldItems($threshold, null)) {
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
                if ($this->queueItemIsPurgeAll($item)) {
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
        // Check if term UID hash exists.
        $payload = [
            'type' => 'term',
            'id'  => $termId,
            'args' => [
                    'taxonomySlug' => $taxonomySlug
                ]
        ];
        if($this->checkIfPayloadExists($payload)) return true;

        // Check if cachetag UID hash exists.
        $payload = [
            'type' => 'cachetag',
            'id'  => 'term-'.$termId,
        ];
        if($this->checkIfPayloadExists($payload)) return true;
       
        if ($items = $this->queue->getActiveItems()) {
            foreach ($items as $item) {
                // TODO: does $this apply and not $item?
                if (!isset($item->payload)) {
                    continue;
                }                
                $args = arrayGet('args', $item->payload);
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
     * check if payload UID exists
     * 
     * Convert the payload to a serialized string and then SHA256 hash
     * and look for it in the UID column.
     */
    public function checkIfPayloadExists(array $payload = [] )
    {
        return $this->queue->checkUidExists($payload);
    }

    /**
     * Check if a post is already in the queue.
     * 
     * @param int $postId
     * @return bool
     */
    public function hasPostInQueue(int $postId): bool
    {
        // Check if post UID hash exists.
        $payload = [
            'type' => 'post',
            'id'  => $postId,
        ];

        if($this->checkIfPayloadExists($payload)) return true;
        // Check if cachetag UID hash exists.
        $payload = [
            'type' => 'cachetags',
            'id'  => $postId,
        ];

        if($this->checkIfPayloadExists($payload)) return true;
        

        if ($items = $this->queue->getActiveItems()) {
            foreach ($items as $item) {
                if($this->checkIfPayloadExists($item->payload)) return true;

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
     * Check if a post is already in the queue.
     *
     * @param int $postId
     * @return bool
     */
    public function hasItemInQueue(int $postId): bool
    {
        // Check if post UID hash exists.
        $payload = [
            'type' => 'post',
            'id'  => $postId,
        ];

        if($this->checkIfPayloadExists($payload)) return true;
        // Check if cachetag UID hash exists.
        $payload = [
            'type' => 'cachetags',
            'id'  => $postId,
        ];

        if($this->checkIfPayloadExists($payload)) return true;

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
        // Check if post UID hash exists.
        $payload = [
            'type' => 'url',
            'url'  => $url,
        ];

        if($this->checkIfPayloadExists($payload)) return true;
        
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

    /**
     * The garbageCollection method checks in the sb_queue table for anything that is older
     * than 24 hours that has a completed_at_gmt timestamp of that length or greater.
     * 
     * This means that the cache purge has been dealt with but from some reason the purge item
     * has not been deleted.
     * 
     * This is a belt and braces approach to cleanup.
     * 
     * note: This only deletes 1000 rows in one go. it runs every minute, thus 1.4m rows is max per day.
     * @var bool $cli is it a WP CLI run thing
     * @var int $limit the maximum number of items deleted in one go
     * 
     */
    public function garbageCollection($cli = false, $limit = 1000) : void
    {
        global $wpdb;
        if(is_multisite() && $cli == false) {
            $start_id = get_current_blog_id();

            // loop blog ids
            $sites = get_sites();
            foreach ( $sites as $site ) {
                switch_to_blog( $site->blog_id );                
                // perform a $wpdb delete 
                $wpdb->query(
                    $wpdb->prepare(
                        'DELETE FROM '.$this->queue->getTableName().' WHERE completed_at_gmt < %d AND completed_at_gmt IS NOT NULL ORDER BY `id` LIMIT %d',
                        strtotime("-1 day"),
                        $limit
                        )
                );
                restore_current_blog();
            }
            switch_to_blog($start_id);
        } else {
            $wpdb->query(
                $wpdb->prepare(
                    'DELETE FROM '.$this->queue->getTableName().' WHERE completed_at_gmt < %d AND completed_at_gmt IS NOT NULL ORDER BY `id` LIMIT %d',
                    strtotime("-1 day"),
                    $limit
                    )
            );
        }
    }

}
