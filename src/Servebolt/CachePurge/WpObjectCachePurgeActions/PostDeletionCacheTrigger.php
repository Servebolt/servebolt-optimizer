<?php

namespace Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge;
use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\Traits\Singleton;

/**
 * Class PostDeletionCacheTrigger
 * @package Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions
 */
class PostDeletionCacheTrigger
{
    use Singleton;

    /**
     * PostDeletionCacheTrigger constructor.
     */
    public function __construct()
    {
        // Skip this feature if the cache purge feature is inactive or insufficiently configured, or it automatic cache purge is inactive
        if (!CachePurge::automaticCachePurgeOnDeletionIsActive()) {
            return;
        }

        // Purge post on post delete
        if (apply_filters('sb_optimizer_automatic_purge_on_post_delete', true)) {
            add_action('delete_post', [$this, 'postDeleted'], 10, 1);
            add_action('trashed_post', [$this, 'postDeleted'], 10, 1);
        }
    }

    /**
     * @param int $postId
     */
    public function postDeleted(int $postId): void
    {
        try {
            WordPressCachePurge::purgeByPostId($postId);
        } catch (Exception $e) {}
    }
}
