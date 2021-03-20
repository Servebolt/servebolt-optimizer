<?php

namespace Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\CachePurge\CachePurge;
use Exception;

/**
 * Class CachePurgeWPActions
 *
 * This class registers the WP events which purges the cache automatically (updating/create posts, terms etc.).
 */
class ContentChangeTrigger
{
    /**
     * CachePurgeWPActions constructor.
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
        if ( apply_filters('sb_optimizer_disable_automatic_purge', false) ) return;

        // Purge post on post update
        if ( apply_filters('sb_optimizer_automatic_purge_on_post_save', true) ) {
            add_action( 'post_updated', [$this, 'purgePostOnSave'], 99, 3 );
        }

        // Purge post on comment post
        if ( apply_filters('sb_optimizer_automatic_purge_on_comment', true) ) {
            add_action( 'comment_post', [$this, 'purgePostOnCommentPost'], 99, 3 );
        }

        // Purge post when comment is approved
        if ( apply_filters('sb_optimizer_automatic_purge_on_comment_approval', true) ) {
            add_action( 'transition_comment_status', [$this, 'purgePostOnCommentApproval'], 99, 3 );
        }

        // Purge post when term is edited (Work in progress)
        if ( apply_filters('sb_optimizer_automatic_purge_on_term_save', true) ) {
            add_action( 'edit_term', [ $this, 'purgeTermOnSave' ], 99, 3 );
        }

    }

    /**
     * @param $termId
     * @param $termTaxonomyId
     * @param $taxonomy
     * @throws \ReflectionException
     */
    public function purgeTermOnSave($termId, $termTaxonomyId, $taxonomy): void
    {
        $this->maybePurgeTermSlugIfSlugChanged($termId, $taxonomy);
        $this->maybePurgeTerm($termId, $taxonomy);
    }

    /**
     *
     */
    private function maybePurgeTermSlugIfSlugChanged() {
        // TODO: We might need to use a different filter to try to catch the old slug before the term is being updated. Take a look at the function wp-includes/taxonomy.php:2922 for available filters/actions.
        // TODO: When a term is updated, check if the term slug is being changed and purge the cache on the old URL.
    }

    /**
     * Check whether we should purge cache for given term.
     *
     * @param $termId
     * @param $taxonomy
     */
    private function maybePurgeTerm($termId, $taxonomy) {
        if (!$this->shouldPurgeTermCache($termId, $taxonomy)) {
            return;
        }
        try {
            return WordPressCachePurge::purgeTermCache($termId, $taxonomy);
        } catch (Exception $e) {
            return false;
        }
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
        $override = apply_filters('sb_optimizer_should_purge_term_cache', null, $termId, $taxonomy);
        if ( is_bool($override) ) return $override;

        // Check that the taxonomy is public
        $taxonomyObject = get_taxonomy($taxonomy);
        if ( $taxonomyObject && ( $taxonomyObject->public !== true || $taxonomyObject->publicly_queryable !== true ) ) {
            return false;
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
    private function shouldPurgePostCache($postId): bool
    {

        // Let users override the outcome
        $override = apply_filters('sb_optimizer_should_purge_post_cache', null, $postId);
        if ( is_bool($override) ) return $override;

        // Check that the post type is public
        $postType = get_post_type($postId);
        $postTypeObject = get_post_type_object($postType);
        if ( $postTypeObject && ( $postTypeObject->public !== true || $postTypeObject->publicly_queryable !== true ) ) {
            return false;
        }

        // Make sure that post is not just a draft
        $postStatus = get_post_status($postId);
        if ( ! in_array($postStatus, ['publish']) ) return false;

        return true;
    }

    /**
     * Purge post on post save.
     *
     * @param $postId
     * @param $postAfter
     * @param $postBefore
     */
    public function purgePostOnSave($postId, $postAfter, $postBefore) {
        $this->maybePurgePostPermalinkIfSlugChanged($postId, $postAfter, $postBefore);
        $this->maybePurgePost($postId);
    }

    /**
     * Purge the cache for the old URL if the permalink was changed.
     *
     * @param $postId
     * @param $postAfter
     * @param $postBefore
     */
    private function maybePurgePostPermalinkIfSlugChanged($postId, $postAfter, $postBefore) {
        //$permalinkChanged = $postBefore->post_name != $postAfter->post_name;
        //dd($permalinkChanged);
        // TODO: Check if permalink has changed, and if so then purge cache for the old URL.
    }

    /**
     * Maybe purge post by post ID.
     *
     * @param $postId
     */
    private function maybePurgePost($postId)
    {
        if (!$this->shouldPurgePostCache($postId)) {
            return;
        }
        try {
            return WordPressCachePurge::purgeByPostId($postId);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Purge post cache on comment post.
     *
     * @param $commentId
     * @param $commentApproved
     * @param $commentData
     */
    public function purgePostOnCommentPost($commentId, $commentApproved, $commentData) {
        $postId = $this->get_post_id_from_comment($commentData);

        // Bail on the cache purge if we could not figure out which post was commented on
        if ( ! $postId ) return;

        // Bail on the cache purge if the comment needs to be approved first
        if ( apply_filters('sb_optimizer_prevent_cache_purge_on_unapproved_comments', true) && ! apply_filters('sb_optimizer_comment_approved_cache_purge', $commentApproved, $commentData) ) return;

        $this->maybePurgePost($postId);
    }

    /**
     * Purge post on comment approval.
     *
     * @param $newStatus
     * @param $oldStatus
     * @param $commentData
     */
    public function purgePostOnCommentApproval($newStatus, $oldStatus, $commentData) {
        if ( $oldStatus != $newStatus && $newStatus == 'approved' ) {
            $postId = $this->getPostIdFromComment($commentData);
            if ( ! $postId ) return;
            $this->maybePurgePost($postId);
        }
    }

    /**
     * Get post ID from comment data.
     *
     * @param $commentData
     *
     * @return bool
     */
    private function getPostIdFromComment($commentData) {
        $commentData = (array) $commentData;
        if ( ! array_key_exists('comment_post_ID', $commentData) ) return false;
        return $commentData['comment_post_ID'];
    }
}
