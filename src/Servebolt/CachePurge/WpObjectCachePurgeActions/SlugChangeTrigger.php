<?php

namespace Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\CachePurge\CachePurge;
use Servebolt\Optimizer\Traits\Singleton;
use Exception;

/**
 * Class SlugChangeTrigger
 */
class SlugChangeTrigger
{
    use Singleton;

    /**
     * Current state of the permalink before post update.
     *
     * @var null
     */
    private $previousPostPermalink = null;

    /**
     * SlugChangeTrigger constructor.
     */
    public function __construct()
    {
        $this->registerPurgeActions();
    }

    /**
     * Register action hooks.
     */
    private function registerPurgeActions()
    {

        // Skip this feature if the cache purge feature is inactive or insufficiently configured, or it automatic cache purge is inactive
        if (!CachePurge::featureIsAvailable() || !CachePurge::automaticCachePurgeOnContentUpdateIsActive()) {
            return;
        }

        if (!apply_filters('sb_optimizer_automatic_purge_on_permalink_change', true)) {
            return;
        }

        // Purge old term permalink if slug is changed
        if (apply_filters('sb_optimizer_automatic_purge_on_term_permalink_change', true)) {
            add_filter('wp_update_term_data', [$this, 'checkPreviousTermPermalink'], 10, 3);
        }

        // Purge old post permalink if slug is changed
        if (apply_filters('sb_optimizer_automatic_purge_on_post_permalink_change', true)) {
            add_action('pre_post_update', [$this, 'recordPostPermalink'], 99, 1);
            add_action('post_updated', [$this, 'checkPreviousPostPermalink'], 99, 1);
        }

    }

    /**
     * Check if the term permalink changed, and if so then purge the old one.
     *
     * @param array $updateData
     * @param $termId
     * @param $taxonomy
     * @return array
     */
    public function checkPreviousTermPermalink(array $updateData, $termId, $taxonomy): array
    {
        $term = get_term($termId, $taxonomy);
        $termSlugDidChange = $term->slug !== $updateData['slug'];
        if ($termSlugDidChange) {
            if ($previousTermPermalink = get_term_link($term)) {
                try {
                    // TODO: Consider whether we should add pagination-support to this cache purge
                    WordPressCachePurge::purgeByUrl($previousTermPermalink);
                } catch (Exception $e) {}
            }
        }
        return $updateData;
    }

    /**
     * Record the current state of the permalink before post update.
     *
     * @param $postId
     */
    public function recordPostPermalink($postId): void
    {
        $this->previousPostPermalink = get_permalink($postId);
    }

    /**
     * Check if the permalink changed.
     *
     * @param $postId
     * @return bool
     */
    public function postPermalinkDidChange($postId): bool
    {
        if (!is_null($this->previousPostPermalink) && get_permalink($postId) !== $this->previousPostPermalink) {
            return true;
        }
        return false;
    }

    /**
     * Check if the post permalink changed, and if so then purge the old one.
     *
     * @param $postId
     */
    public function checkPreviousPostPermalink($postId): void
    {
        if ($this->postPermalinkDidChange($postId)) {
            try {
                WordPressCachePurge::purgeByUrl($this->previousPostPermalink);
            } catch (Exception $e) {}
        }
    }

}
