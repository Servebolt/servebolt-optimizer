<?php

namespace Servebolt\Optimizer\CronHandle\Events;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge;

/**
 * Class CronEvent
 * @package Servebolt\Optimizer\CronHandle
 */
class CronMinuteEvent
{
    /**
     * @var string The action hook used when triggering this event.
     */
    public static $hook = 'servebolt_optimizer_every_minute_cron_event';

    /**
     * CronEvent constructor.
     */
    public function __construct()
    {
        if (CachePurge::featureIsActive() && CachePurge::cronPurgeIsActive()) {
            $this->registerEvent();
        } else {
            $this->deregisterEvent();
        }
    }

    private function registerEvent(?int $blogId = null): void
    {
        if ($blogId) {
            switch_to_blog($blogId);
        }
        if (!wp_next_scheduled(self::$hook)) {
            wp_schedule_event(time(), 'every_minute', self::$hook);
        }
        if ($blogId) {
            restore_current_blog();
        }
    }

    private function deregisterEvent(?int $blogId = null): void
    {
        if ($blogId) {
            switch_to_blog($blogId);
        }
        if (wp_next_scheduled(self::$hook)) {
            wp_clear_scheduled_hook(self::$hook);
        }
        if ($blogId) {
            restore_current_blog();
        }
    }
}
