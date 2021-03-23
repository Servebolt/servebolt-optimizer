<?php

namespace Servebolt\Optimizer\Queue;

use Servebolt\Optimizer\CachePurge\CachePurge as CachePurgeDriver;
use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

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
        $this->wpObjectQueue = Queue::getInstance(WpObjectQueue::$queueName);
        for ($i = 1; $i <= $this->numberOfRuns; $i++) {
            $this->parseQueue();
        }
    }

    private function parseQueue(): void
    {
        $items = $this->queue->getAndReserveItems($this->urlChunkSize);
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
