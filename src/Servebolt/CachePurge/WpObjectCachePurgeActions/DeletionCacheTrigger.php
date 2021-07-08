<?php

namespace Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge;
use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\getTaxonomyFromTermId;

/**
 * Class DeletionCacheTrigger
 * @package Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions
 */
class DeletionCacheTrigger
{
    use Singleton;

    public static function on(): void
    {
        $instance = self::getInstance();
        $instance->registerEvents();
    }

    public static function off(): void
    {
        $instance = self::getInstance();
        $instance->deregisterEvents();
    }

    public function deregisterEvents(): void
    {
        remove_action('delete_term_taxonomy', [$this, 'deleteTerm'], 10, 1);
        remove_action('delete_attachment', [$this, 'deletePost'], 10, 1);
        remove_action('wp_trash_post', [$this, 'deletePost'], 10, 1);
        remove_action('before_delete_post', [$this, 'deletePost'], 10, 1);
    }

    public function registerEvents()
    {
        // Skip this feature if automatic cache purge for deletion is inactive
        if (!CachePurge::automaticCachePurgeOnDeletionIsActive()) {
            return;
        }

        // TODO: Handle post transitions too?

        // Should skip all automatic cache purge on content update?
        if (apply_filters('sb_optimizer_disable_automatic_purge_on_deletion', false)) {
            return;
        }

        // Purge on term delete
        if (apply_filters('sb_optimizer_automatic_purge_on_term_delete', true)) {
            add_action('delete_term_taxonomy', [$this, 'deleteTerm'], 10, 1);
        }

        // Purge on attachment delete
        if (apply_filters('sb_optimizer_automatic_purge_on_attachment_delete', true)) {
            add_action('delete_attachment', [$this, 'deletePost'], 10, 1);
        }

        // Purge on post trash
        if (apply_filters('sb_optimizer_automatic_purge_on_post_trash', true)) {
            add_action('wp_trash_post', [$this, 'deletePost'], 10, 1);
        }

        // Purge on post delete
        if (apply_filters('sb_optimizer_automatic_purge_on_post_delete', true)) {
            add_action('before_delete_post', [$this, 'deletePost'], 10, 1);
        }
    }

    /**
     * Callback for just before a term gets deleted.
     *
     * @param int $termId
     */
    public function deleteTerm(int $termId): void
    {
        $taxonomySlug = (getTaxonomyFromTermId($termId))->name;
        try {
            WordPressCachePurge::skipQueueOnce();
            WordPressCachePurge::purgeByTermId($termId, $taxonomySlug);
        } catch (Exception $e) {}
    }

    /**
     * Callback for just before a post gets deleted.
     *
     * @param int $postId
     */
    public function deletePost(int $postId): void
    {
        try {
            WordPressCachePurge::skipQueueOnce();
            WordPressCachePurge::purgeByPostId($postId);
        } catch (Exception $e) {}
    }
}
