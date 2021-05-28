<?php

namespace Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\CachePurge\CachePurge;
use Servebolt\Optimizer\Traits\Singleton;
use Exception;

/**
 * Class PostContentChangeTrigger
 *
 * This class registers the WP events which purges the cache automatically (updating/create posts, terms etc.).
 */
class PostContentChangeTrigger
{
    use Singleton;

    /**
     * PostContentChangeTrigger constructor.
     */
    public function __construct()
    {
        $this->registerPurgeActions();
    }

    /**
     * Register action hooks.
     */
    public function registerPurgeActions()
    {

        // Skip this feature if the cache purge feature is inactive or insufficiently configured, or it automatic cache purge is inactive
        if (!CachePurge::featureIsAvailable() || !CachePurge::automaticCachePurgeOnContentUpdateIsActive()) {
            return;
        }

        // Should skip all automatic cache purge?
        if (apply_filters('sb_optimizer_disable_automatic_purge', false)) {
            return;
        }

        // Purge post on post update
        if (apply_filters('sb_optimizer_automatic_purge_on_post_save', true)) {
            add_action('save_post', [$this, 'purgePostOnSave'], 99, 3);
        }

        // Purge post on comment post
        if (apply_filters('sb_optimizer_automatic_purge_on_comment', true)) {
            add_action('comment_post', [$this, 'purgePostOnCommentPost'], 99, 3);
        }

        // Purge post when comment is approved
        if (apply_filters('sb_optimizer_automatic_purge_on_comment_approval', true)) {
            add_action('transition_comment_status', [$this, 'purgePostOnCommentApproval'], 99, 3);
        }

        // Purge post when term is edited (Work in progress)
        if (apply_filters('sb_optimizer_automatic_purge_on_term_save', true)) {
            add_action('edit_term', [$this, 'purgeTermOnSave'], 99, 3);
        }
    }

    /**
     * @param $termId
     * @param $termTaxonomyId
     * @param $taxonomy
     */
    public function purgeTermOnSave($termId, $termTaxonomyId, $taxonomy): void
    {
        $this->maybePurgeTerm($termId, $taxonomy);
    }

    /**
     * Check whether we should purge cache for given term.
     *
     * @param $termId
     * @param $taxonomy
     */
    private function maybePurgeTerm($termId, $taxonomy): void
    {
        if (!$this->shouldPurgeTermCache($termId, $taxonomy)) {
            return;
        }
        try {
            WordPressCachePurge::purgeTermCache($termId, $taxonomy);
        } catch (Exception $e) {}
    }

    /**
     * Check if we should clear cache for post that is being updated.
     *
     * @param $termId
     * @param $taxonomy
     *
     * @return bool|void
     */
    private function shouldPurgeTermCache($termId, $taxonomy): bool
    {

        // Let users override the outcome
        $overrideByTermId = apply_filters('sb_optimizer_should_purge_term_cache', null, $termId, $taxonomy);
        if (is_bool($overrideByTermId)) {
            return $overrideByTermId;
        }

        // Check that the taxonomy is public
        $taxonomyObject = get_taxonomy($taxonomy);
        if ( $taxonomyObject && ( $taxonomyObject->public !== true || $taxonomyObject->publicly_queryable !== true ) ) {
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
        if ($postTypeObject && ($postTypeObject->public !== true || $postTypeObject->publicly_queryable !== true)) {
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
     * @param int $postId
     * @param WP_Post|object $post
     * @param bool $update
     */
    public function purgePostOnSave(int $postId, object $post, bool $update): void
    {
        $this->maybePurgePost($postId);
    }

    /**
     * Maybe purge post by post ID.
     *
     * @param $postId
     */
    private function maybePurgePost($postId): void
    {
        if (!self::shouldPurgePostCache($postId)) {
            return;
        }
        try {
            WordPressCachePurge::purgeByPostId($postId);
        } catch (Exception $e) {}
    }

    /**
     * Purge post cache on comment post.
     *
     * @param $commentId
     * @param $commentApproved
     * @param $commentData
     */
    public function purgePostOnCommentPost($commentId, $commentApproved, $commentData): void
    {
        $postId = $this->getPostIdFromComment($commentData);

        // Bail on the cache purge if we could not figure out which post was commented on
        if (!$postId) {
            return;
        }

        // Bail on the cache purge if the comment needs to be approved first
        $commentIsApproved = apply_filters('sb_optimizer_comment_approved_cache_purge', $commentApproved, $commentData, $commentId);
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
            $postId = $this->getPostIdFromComment($commentData);
            if (!$postId) {
                return;
            }
            $this->maybePurgePost($postId);
        }
    }

    /**
     * Get post ID from comment data.
     *
     * @param $commentData
     *
     * @return bool|int
     */
    private function getPostIdFromComment($commentData)
    {
        $commentData = (array) $commentData;
        if (!array_key_exists('comment_post_ID', $commentData)) {
            return false;
        }
        return $commentData['comment_post_ID'];
    }
}
