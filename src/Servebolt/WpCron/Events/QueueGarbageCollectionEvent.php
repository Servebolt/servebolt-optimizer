<?php

namespace Servebolt\Optimizer\WpCron\Events;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge;
use function Servebolt\Optimizer\Helpers\getFiltersForHook;

/**
 * Class QueueGarbageCollectionEvent
 * 
 * Event runs daily to clean up the sb_queue table from anything that is not normally being deleted
 * The deleted items are the ones that have been completed but not removed. 
 * 
 * It is run daily so that there is a 24hr period for the item to be naturally removed from the table
 * by standard means.
 * 
 * @package Servebolt\Optimizer\WpCron
 */
class QueueGarbageCollectionEvent
{

    /**
     * @var string
     */
    private static $recurrence = 'every_minute';

    /**
     * @var string The action hook used when triggering this event.
     */
    public static $hook = 'sb_optimizer_queue_garbage_collection_event';

    /**
     * QueueGarbageCollectionEvent constructor.
     */
    public function __construct()
    {
        if (
            $this->hasActionsRegistered()
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
            wp_schedule_event(time(), self::$recurrence, self::$hook);
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
