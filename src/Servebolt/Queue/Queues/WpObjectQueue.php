<?php

namespace Servebolt\Optimizer\Queue\Queues;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\PurgeObject\PurgeObject;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\Utils\Queue\Queue;
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
     * WpObjectQueue constructor.
     */
    public function __construct()
    {
        $this->queue = Queue::getInstance(self::$queueName);
    }

    /**
     * Parse the WP Object queue.
     */
    public function parseQueue(): void
    {
        for ($i = 1; $i <= $this->numberOfRuns; $i++) {
            $this->parseQueueSegment();
        }
        $this->flagItemsAsCompleted();
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
            $purgeObject = new PurgeObject(
                $payload['id'],
                $payload['type'],
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
     * @return array|null
     */
    public function getItemsToParse(): ?array
    {
        return $this->queue->getAndReserveItems($this->chunkSize, true);
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
                if ($payload['type'] === 'purge-all') {
                    if ($payload['networkPurge']) {
                        // TODO: Make sure this is working
                        iterateSites(function($site) use ($item) {
                            $this->clearUrlQueue();
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
                } else if ($urls = $this->resolveUrlsToPurgeFromWpObject($payload)) {
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
     * Check whether we got unfinished item in the UrlQueue belonging to the item in the WpObjectQueue.
     *
     * @param $id
     * @param $queue
     * @return bool
     */
    private function itemHasActiveChildItemsInUrlQueue($id, $queue): bool
    {
        $childItems = $this->urlQueue()->getUnfinishedItemsByParent($id, $queue, null);
        return !empty($childItems);
    }

    /**
     * Flag WP Object queue items as done as long as they have no unfinished Url Queue items.
     */
    private function flagItemsAsCompleted(): void
    {
        if ($items = $this->queue->getReservedItems(null)) {
            foreach ($items as $item) {
                if (!$this->itemHasActiveChildItemsInUrlQueue($item->id, $item->queue)) {
                    $this->queue->completeItem($item); // This item does not have any active URL queue items, flag it as completed
                }
            }
        }
    }

    /**
     * The UrlQueue queue-Instance;
     * @return mixed|Queue
     */
    private function urlQueue()
    {
        if (!$this->urlQueue) {
            $this->urlQueue = Queue::getInstance(UrlQueue::$queueName);
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

    private function cleanUpQueue()
    {
        // TODO: Remove items older than x years(?), to prevent the database from filling up. Might be common with UrlQueue, so maybe share it between them.
    }
}
