<?php

namespace Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\CachePurge\CachePurge;
use Exception;
use Servebolt\Optimizer\Traits\EventToggler;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\setCachePurgeOriginEvent;

/**
 * Class CachePurgeWPActions
 *
 * This class registers the WP events which purges the cache automatically (updating/create posts, terms etc.).
 */
class ContentChangeTrigger
{
    use Singleton, EventToggler;

    public function deregisterEvents(): void
    {
        remove_action('edit_term', [$this, 'purgeTermOnSave'], 99, 3);
        remove_action('save_post', [$this, 'purgePostOnSave'], 99, 1);
        remove_action('comment_post', [$this, 'purgePostOnCommentPost'], 99, 3);
        remove_action('transition_comment_status', [$this, 'purgePostOnCommentApproval'], 99, 3);
    }

    /**
     * Register action hooks.
     */
    public function registerEvents()
    {

        // Skip this feature if automatic cache purge is inactive
        if (!CachePurge::automaticCachePurgeOnContentUpdateIsActive()) {
            return;
        }

        // Should skip all automatic cache purge on content update?
        if (apply_filters('sb_optimizer_disable_automatic_purge', false)) {
            return;
        }

        // Purge post when term is edited (Work in progress)
        if (apply_filters('sb_optimizer_automatic_purge_on_term_save', true)) {
            add_action('edit_term', [$this, 'purgeTermOnSave'], 99, 3);
        }

        // Purge post on post update
        if (apply_filters('sb_optimizer_automatic_purge_on_post_save', true)) {
            add_action('save_post', [$this, 'purgePostOnSave'], 99, 1);
        }

        // Purge post on comment post
        if (apply_filters('sb_optimizer_automatic_purge_on_comment', true)) {
            add_action('comment_post', [$this, 'purgePostOnCommentPost'], 99, 3);
        }

        // Purge post when comment is approved
        if (apply_filters('sb_optimizer_automatic_purge_on_comment_approval', true)) {
            add_action('transition_comment_status', [$this, 'purgePostOnCommentApproval'], 99, 3);
        }
    }

    /**
     * Purge cache for term on save.
     *
     * @param int $termId The term ID.
     * @param int $termTaxonomyId The term taxonomy ID.
     * @param string $taxonomy The taxonomy slug.
     */
    public function purgeTermOnSave($termId, $termTaxonomyId, $taxonomy): void
    {
        $this->maybePurgeTerm((int) $termId, (string) $taxonomy);
    }

    /**
     * Check whether we should purge cache for given term.
     *
     * @param int $termId
     * @param string|mixed $taxonomy
     */
    private function maybePurgeTerm(int $termId, string $taxonomy): void
    {
        if (!$this->shouldPurgeTermCache($termId, $taxonomy)) {
            return;
        }
        try {
            setCachePurgeOriginEvent('term_change');
            WordPressCachePurge::purgeTermCache($termId, $taxonomy);
        } catch (Exception $e) {}
    }

    /**
     * Check if we should clear cache for post that is being updated.
     *
     * @param int $termId
     * @param string $taxonomy
     *
     * @return bool|void
     */
    private function shouldPurgeTermCache(int $termId, string $taxonomy): bool
    {

        /**
         * Let 3rd party devs decide whether we should purge term cache or not.
         *
         * @param null|boolean $shouldPurge Whether to purge the cache or not.
         * @param int $termId The ID of the term.
         * @param string $taxonomy The taxonomy slug.
         */
        $overrideByTermId = apply_filters(
            'sb_optimizer_should_purge_term_cache',
            null,
            $termId,
            $taxonomy
        );
        if (is_bool($overrideByTermId)) {
            return $overrideByTermId;
        }

        // Check that the taxonomy is public
        $taxonomyObject = get_taxonomy($taxonomy);
        if (
            $taxonomyObject
            && $taxonomyObject->public !== true
            /*&& (
                $taxonomyObject->public !== true
                || $taxonomyObject->publicly_queryable !== true
            )*/
        ) {
            return false;
        }

        return true;

    }

    /**
     * Check if we should clear cache for a given post type.
     *
     * @param $postType
     * @return bool
     */
    public static function shouldPurgePostTypeCache($postType): bool
    {
        $overrideByPostType = apply_filters('sb_optimizer_should_purge_post_cache_by_post_type', null, $postType);
        if (is_bool($overrideByPostType)) {
            return $overrideByPostType;
        }

        return true;
    }

    /**
     * Check if we should clear cache for post that is being updated.
     *
     * @param $postId
     *
     * @return bool|void
     */
    public static function shouldPurgePostCache($postId): bool
    {

        // Let users override the outcome
        $overrideByPostId = apply_filters('sb_optimizer_should_purge_post_cache', null, $postId);
        if (is_bool($overrideByPostId)) {
            return $overrideByPostId;
        }

        // Check that the post type is public
        $postType = get_post_type($postId);

        if (!self::shouldPurgePostTypeCache($postType)) {
            return false;
        }

        $postTypeObject = get_post_type_object($postType);
        if (
            $postTypeObject
            && $postTypeObject->public !== true
            /*&& (
                $postTypeObject->public !== true
                || $postTypeObject->publicly_queryable !== true
            )*/
        ) {
            return false;
        }

        // Make sure that post is not just a draft
        $postStatus = get_post_status($postId);
        if (!in_array($postStatus, ['publish'])) {
            return false;
        }
        return true;
    }

    /**
     * Purge post on post save.
     *
     * @param int|mixed $postId
     */
    public function purgePostOnSave($postId): void
    {
        $this->maybePurgePost((int) $postId);
    }

    /**
     * Maybe purge post by post ID.
     *
     * @param int $postId
     */
    private function maybePurgePost(int $postId): void
    {
        if (!self::shouldPurgePostCache($postId)) {
            return;
        }
        try {
            setCachePurgeOriginEvent('post_change');
            WordPressCachePurge::purgeByPostId((int) $postId);
        } catch (Exception $e) {}
    }

    /**
     * Purge post cache on comment post.
     *
     * @param int $commentId
     * @param int|string $commentApproved
     * @param array $commentData
     */
    public function purgePostOnCommentPost($commentId, $commentApproved, $commentData): void
    {
        $postId = $this->getPostIdFromComment((array) $commentData);

        // Bail on the cache purge if we could not figure out which post was commented on
        if (!$postId) {
            return;
        }

        /**
         * Bail on the cache purge if the comment needs to be approved first.
         *
         * @param int|string $commentApproved Whether the comment is approved, or whether it's spam.
         * @param array $commentData An array containing the comment data.
         * @param int $commentId The ID of the comment.
         * @param int $post The ID of the post where the comment was posted.
         */
        $commentIsApproved = apply_filters(
            'sb_optimizer_comment_approved_cache_purge',
            $commentApproved,
            $commentData,
            $commentId,
            $postId
        );
        if (
            apply_filters('sb_optimizer_prevent_cache_purge_on_unapproved_comments', true)
            && !$commentIsApproved
        ) {
            return;
        }

        $this->maybePurgePost($postId);
    }

    /**
     * Purge post on comment approval.
     *
     * @param $newStatus
     * @param $oldStatus
     * @param $commentData
     */
    public function purgePostOnCommentApproval($newStatus, $oldStatus, $commentData): void
    {
        $statusDidChange = $oldStatus != $newStatus;
        if ($statusDidChange && $newStatus == 'approved') {
            $postId = $this->getPostIdFromComment((array) $commentData);
            if (!$postId) {
                return;
            }
            $this->maybePurgePost($postId);
        }
    }

    /**
     * Get post ID from comment data.
     *
     * @param array $commentData
     *
     * @return null|int
     */
    private function getPostIdFromComment(array $commentData): ?int
    {
        $commentPostId = arrayGet('comment_post_ID', $commentData);
        if (!$commentPostId) {
            return null;
        }
        return (int) $commentPostId;
    }
}
