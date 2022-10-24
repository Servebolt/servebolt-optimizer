<?php

namespace Servebolt\Optimizer\Queue;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\WpCron\Events\QueueParseEvent;
use Servebolt\Optimizer\WpCron\Events\QueueGarbageCollectionEvent;
use Servebolt\Optimizer\Queue\Queues\WpObjectQueue;
use Servebolt\Optimizer\Queue\Queues\UrlQueue;

/**
 * Class QueueParseEventHandler
 * 
 * The constructor of this class sets up actions for the scheduled tasks. 
 * The hooks for:
 * 1. sb_optimizer_queue_parse_event
 * 2. sb_optimizer_queue_garbage_collection_event
 * 
 * @package Servebolt\Optimizer\Queue
 */
class QueueParseEventHandler
{
    /**
     * QueueParseEventHandler constructor.
     */
    public function __construct()
    {
        if ($this->shouldParseQueue()) {
            add_action(QueueParseEvent::$hook, [$this, 'handleWpObjectQueue'], 10);
            add_action(QueueParseEvent::$hook, [$this, 'handleUrlQueue'], 11);
            add_action(QueueGarbageCollectionEvent::$hook, [$this, 'handleQueueGarbageCollection'], 10);
        }
    }

    /**
     * Whether to parse the queues or not (allow for override).
     *
     * @return bool
     */
    private function shouldParseQueue(): bool
    {
        $value = true;
        if (defined('SERVEBOLT_QUEUE_BASED_CACHE_PURGE_SHOULD_PARSE_QUEUE') && is_bool(SERVEBOLT_QUEUE_BASED_CACHE_PURGE_SHOULD_PARSE_QUEUE)) {
            $value = SERVEBOLT_QUEUE_BASED_CACHE_PURGE_SHOULD_PARSE_QUEUE;
        }
        // Legacy #1
        else if (defined('SERVEBOLT_QUEUE_BASED_CACHE_SHOULD_PARSE_QUEUE') && is_bool(SERVEBOLT_QUEUE_BASED_CACHE_SHOULD_PARSE_QUEUE)) {
            $value = SERVEBOLT_QUEUE_BASED_CACHE_SHOULD_PARSE_QUEUE;
        }
        // Legacy #2
        else if (defined('SERVEBOLT_CF_PURGE_CRON_PARSE_QUEUE') && is_bool(SERVEBOLT_CF_PURGE_CRON_PARSE_QUEUE)) {
            $value = SERVEBOLT_CF_PURGE_CRON_PARSE_QUEUE; // Legacy
        }
        return apply_filters('sb_optimizer_should_purge_cache_queue', $value);
    }

    /**
     * Trigger WP Object queue parse  for scheduler.
     */
    public function handleWpObjectQueue(): void
    {
        (WpObjectQueue::getInstance())->parseQueue();
    }

    /**
     * Trigger URL queue parse for scheduler.
     */
    public function handleUrlQueue(): void
    {
        (UrlQueue::getInstance())->parseQueue();
    }

    /**
     * Trigger Garbage collection of the sb_queue table.
     */
    public function handleQueueGarbageCollection() : void
    {
        (WpObjectQueue::getInstance())->garbageCollection();
    }
    
}
