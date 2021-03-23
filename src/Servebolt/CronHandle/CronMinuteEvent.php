<?php

namespace Servebolt\Optimizer\CronHandle;

use Servebolt\Optimizer\CachePurge\CachePurge;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class CronEvent
 * @package Servebolt\Optimizer\CronHandle
 */
class CronMinuteEvent
{
    /**
     * @var string
     */
    public static $cronKey = 'servebolt_optimizer_every_minute_cron_event';

    /**
     * CronEvent constructor.
     */
    public function __construct()
    {
        if (CachePurge::featureIsActive() && CachePurge::cronPurgeIsActive()) {
            $this->registerCachePurgeEvent();
        } else {
            $this->deregisterCachePurgeEvent();
        }
    }

    private function registerCachePurgeEvent(?int $blogId = null): void
    {
        if ($blogId) {
            switch_to_blog($blogId);
        }
        if (!wp_next_scheduled(self::$cronKey)) {
            wp_schedule_event(time(), 'every_minute', self::$cronKey);
        }
        if ($blogId) {
            restore_current_blog();
        }
    }

    private function deregisterCachePurgeEvent(?int $blogId = null): void
    {
        if ($blogId) {
            switch_to_blog($blogId);
        }
        if (wp_next_scheduled($this->cronKey)) {
            wp_clear_scheduled_hook($this->cronKey);
        }
        if ($blogId) {
            restore_current_blog();
        }
    }
}
