<?php

namespace Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge;
use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\Traits\Singleton;

/**
 * Class DeletionCacheTrigger
 * @package Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions
 */
class DeletionCacheTrigger
{
    use Singleton;

    /**
     * DeletionCacheTrigger constructor.
     */
    public function __construct()
    {
        // Skip this feature if automatic cache purge for deletion is inactive
        if (!CachePurge::automaticCachePurgeOnDeletionIsActive()) {
            return;
        }

        // Purge on term delete
        if (apply_filters('sb_optimizer_automatic_purge_on_term_delete', true)) {
            // TODO: Find term deletion action
        }

        // Purge on attachment delete
        if (apply_filters('sb_optimizer_automatic_purge_on_attachment_delete', true)) {
                //add_action('pre_delete_attachment');
        }

        // Purge on post delete
        if (apply_filters('sb_optimizer_automatic_purge_on_post_delete', true)) {

            // Post gets trashed
            add_action('wp_trash_post', [$this, 'postDeleted'], 10, 1);

            // Post gets deleted
            add_action('before_delete_post', [$this, 'postDeleted'], 10, 1);
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
