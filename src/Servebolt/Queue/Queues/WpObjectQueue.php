<?php

namespace Servebolt\Optimizer\Queue\Queues;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\PurgeObject\PurgeObject;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\Queue\QueueSystem\Queue;

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
        //$this->flagWpObjectQueueItemsAsCompleted();
    }

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
        } elseif ($payload['type'] == 'url' && $payload->url) {
            return [$payload['url']];
        }
        return null;
    }

    /**
     * Parse queue segment.
     */
    private function parseQueueSegment(): void
    {


        $items = $this->queue->getAndReserveItems($this->chunkSize, true);
        if ($items) {
            foreach ($items as $item) {
                $payload = $item->payload;
                if ($payload['type'] === 'purge-all') {
                    $this->urlQueue()->add([
                        'type' => 'purge-all',
                        'networkPurge' => $payload->networkPurge
                    ], $item);
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
     * Flag WP Object queue items as done as long as they have no unfinished Url Queue items.
     */
    private function flagWpObjectQueueItemsAsCompleted(): void
    {
        if ($items = $this->queue->getReservedItems()) {
            foreach ($items as $item) {
                if (empty($this->urlQueue()->getUnfinishedItemsByParent($item->id, $item->queue))) {
                    $this->queue->completeItem($item); // This item does not have any active URL queue items, flag it as completed
                }
            }
        }
    }

    /**
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
     * @param $itemData
     * @return object|null
     */
    public function add($itemData): ?object
    {
        $serializedItemData = serialize($itemData);
        if ($this->queue->itemExists($serializedItemData, 'payload')) {
            return $this->queue->get($serializedItemData, 'payload');
        }
        return $this->queue->add($itemData);
    }

}
