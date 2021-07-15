<?php

namespace Servebolt\Optimizer\WpCron\Events;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge;
use function Servebolt\Optimizer\Helpers\getFiltersForHook;

/**
 * Class MinuteEvent
 * @package Servebolt\Optimizer\WpCron
 */
class MinuteEvent
{
    /**
     * @var string The action hook used when triggering this event.
     */
    public static $hook = 'servebolt_optimizer_every_minute_cron_event';

    /**
     * MinuteEvent constructor.
     */
    public function __construct()
    {
        if (
            $this->hasActionsRegistered()

            // TODO: Consider to move this somewhere else, prehaps to class "QueueEventHandler"
            && CachePurge::featureIsActive()
            && CachePurge::queueBasedCachePurgeIsActive()
        ) {
            $this->registerEvent();
        } else {
            $this->deregisterEvent();
        }
    }

    /**
     * Check whether we have any actions registered on the hook/action for this event.
     *
     * @return bool
     */
    private function hasActionsRegistered(): bool
    {
        return !empty(getFiltersForHook(self::$hook));
    }

    /**
     * Register event.
     *
     * @param int|null $blogId
     */
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

    /**
     * Deregister event.
     *
     * @param int|null $blogId
     */
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
