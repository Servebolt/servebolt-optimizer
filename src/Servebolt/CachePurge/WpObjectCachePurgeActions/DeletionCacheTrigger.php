<?php

namespace Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Throwable;
use Servebolt\Optimizer\CachePurge\CachePurge;
use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\Traits\EventToggler;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\getTaxonomyFromTermId;
use function Servebolt\Optimizer\Helpers\setCachePurgeOriginEvent;

/**
 * Class DeletionCacheTrigger
 * @package Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions
 */
class DeletionCacheTrigger
{
    use Singleton, EventToggler;

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
    public function deleteTerm($termId): void
    {
        if (!$termId) {
            return;
        }
        $termId = (int) $termId;
        $taxonomyObject = getTaxonomyFromTermId($termId);
        if (!$taxonomyObject) {
            return;
        }
        $taxonomySlug = (isset($taxonomyObject->name) && !empty($taxonomyObject->name)) ? (string) $taxonomyObject->name : false;
        if (!$taxonomySlug) {
            return;
        }
        try {
            setCachePurgeOriginEvent('term_deleted');
            WordPressCachePurge::skipQueueOnce();
            WordPressCachePurge::purgeByTermId($termId, $taxonomySlug);
        } catch (Throwable $e) {}
    }

    /**
     * Callback for just before a post gets deleted.
     *
     * @param int $postId
     */
    public function deletePost($postId): void
    {
        try {
            setCachePurgeOriginEvent('post_deleted');
            WordPressCachePurge::skipQueueOnce();
            WordPressCachePurge::purgeByPostId((int) $postId);
        } catch (Throwable $e) {}
    }
}
