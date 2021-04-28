<?php

namespace Servebolt\Optimizer\Queue;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\WpCron\Events\MinuteEvent;
use Servebolt\Optimizer\Queue\Queues\WpObjectQueue;
use Servebolt\Optimizer\Queue\Queues\UrlQueue;

/**
 * Class QueueEventHandler
 * @package Servebolt\Optimizer\Queue
 */
class QueueEventHandler
{
    /**
     * QueueHandler constructor.
     */
    public function __construct()
    {
        if ($this->shouldParseQueue()) {
            add_action(MinuteEvent::$hook, [$this, 'handleWpObjectQueue'], 10);
            add_action(MinuteEvent::$hook, [$this, 'handleUrlQueue'], 11);
        }
    }

    /**
     * Whether to parse the queues or not (allow for override).
     *
     * @return bool
     */
    private function shouldParseQueue(): bool
    {
        if (defined('SERVEBOLT_QUEUE_BASED_CACHE_SHOULD_PARSE_QUEUE') && is_bool(SERVEBOLT_QUEUE_BASED_CACHE_SHOULD_PARSE_QUEUE)) {
            return SERVEBOLT_QUEUE_BASED_CACHE_SHOULD_PARSE_QUEUE;
        }
        if (defined('SERVEBOLT_CF_PURGE_CRON_PARSE_QUEUE') && is_bool(SERVEBOLT_CF_PURGE_CRON_PARSE_QUEUE)) {
            return SERVEBOLT_CF_PURGE_CRON_PARSE_QUEUE; // Legacy
        }
        return apply_filters('sb_optimizer_should_purge_cache_queue', true);
    }

    /**
     * Trigger WP Object queue parse.
     */
    public function handleWpObjectQueue(): void
    {
        (WpObjectQueue::getInstance())->parseQueue();
    }

    /**
     * Trigger URL queue parse.
     */
    public function handleUrlQueue(): void
    {
        (UrlQueue::getInstance())->parseQueue();
    }
}
