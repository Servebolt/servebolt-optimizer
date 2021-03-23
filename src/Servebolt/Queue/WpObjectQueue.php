<?php

namespace Servebolt\Optimizer\Queue;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class WpObjectQueue
 * @package Servebolt\Optimizer\Queue
 */
class WpObjectQueue
{

    /**
     * @var int The number of times the queue parsing should be ran per event trigger.
     */
    private $numberOfRuns = 3;

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
        $this->urlQueue = Queue::getInstance(UrlQueue::$queueName);
        for ($i = 1; $i <= $this->numberOfRuns; $i++) {
            $this->parseQueue();
        }
        $this->flagQueueItemsAsCompleted();
    }

    /**
     * Parse the WP Object queue.
     */
    private function parseQueue(): void
    {
        $items = $this->queue->getAndReserveItems();
        if ($items) {
            foreach ($items as $item) {
                $purgeObject = new PurgeObject(
                    $item->payload->id,
                    $item->payload->type
                );
                foreach ($purgeObject->get_urls() as $url) {
                    $this->urlQueue->add($url, $item);
                }
            }
        }
    }

    /**
     * Flag WP Object queue items as done as long as they have no unfinished Url Queue items.
     */
    private function flagQueueItemsAsCompleted(): void
    {
        $items = $this->queue->getReservedItems();
        if ($items) {
            foreach ($items as $item) {
                if (empty($this->urlQueue->getUnfinishedItemsByParent($item->id, $item->queue))) {
                    $this->queue->completeItem($item); // This item does not have any active URL queue items, flag it as completed
                }
            }
        }
    }

}
