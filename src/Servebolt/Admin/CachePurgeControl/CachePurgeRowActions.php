<?php

namespace Servebolt\Optimizer\Admin\CachePurgeControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Admin\CachePurgeControl\Ajax\PurgeActions;
use Servebolt\Optimizer\CachePurge\CachePurge;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\getPostTypeSingularName;
use function Servebolt\Optimizer\Helpers\getTaxonomySingularName;

/**
 * Class CachePurgeRowActions
 * @package Servebolt\Optimizer\Admin\CachePurgeControl
 */
class CachePurgeRowActions
{
    use Singleton;

    /**
     * Cache feature availability state.
     *
     * @var null|bool
     */
    private $cacheFeatureIsAvailable = null;

    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * CachePurgeRowActions constructor.
     */
    public function __construct()
    {
        add_action('admin_init', [$this, 'rowActionCachePurge']);
    }

    /**
     * Check if cache feature is available.
     *
     * @return bool
     */
    private function cacheFeatureIsAvailable(): bool
    {
        if (is_null($this->cacheFeatureIsAvailable)) {
            $this->cacheFeatureIsAvailable = CachePurge::featureIsAvailable();
        }
        return $this->cacheFeatureIsAvailable;
    }

    /**
     * Add cache purge-action to post/term row actions.
     */
    public function rowActionCachePurge(): void
    {
        foreach(apply_filters('sb_optimizer_cache_purge_row_action_taxonomies', get_taxonomies()) as $taxonomy) {
            add_filter($taxonomy . '_row_actions', [$this, 'addTermPurgeRowAction'], 10, 2);
        }
        foreach(apply_filters('sb_optimizer_cache_purge_row_action_post_types', get_post_types()) as $postType) {
            add_filter($postType . '_row_actions', [$this, 'addPostPurgeRowAction'], 10, 2);
        }
    }

    /**
     * Add cache purge-action to term row actions for given term/taxonomy.
     *
     * @param array $actions
     * @param WP_Term $term
     * @return array
     */
    public function addTermPurgeRowAction(array $actions, $term): array
    {
        if (
            $this->cacheFeatureIsAvailable()
            && PurgeActions::canPurgeTermCache($term->term_id)
        ) {
            $actions['purge-cache'] = sprintf(
                '<a href="%1$s" data-term-id="%2$s" data-object-name="%3$s" class="%4$s">%5$s</a>',
                '#',
                $term->term_id,
                getTaxonomySingularName($term->term_id),
                'sb-purge-term-cache',
                esc_html(__('Purge cache', 'servebolt-wp'))
            );
        }
        return $actions;
    }

    /**
     * Add cache purge-action to post row actions for given post/post type.
     *
     * @param array $actions
     * @param WP_Post $post
     * @return array
     */
    public function addPostPurgeRowAction(array $actions, $post): array
    {
        if (
            $this->cacheFeatureIsAvailable()
            && PurgeActions::canPurgePostCache($post->ID)
        ) {
            $actions['purge-cache'] = sprintf(
                '<a href="%1$s" data-post-id="%2$s" data-object-name="%3$s" class="%4$s">%5$s</a>',
                '#',
                $post->ID,
                getPostTypeSingularName($post->ID),
                'sb-purge-post-cache',
                esc_html(__('Purge cache', 'servebolt-wp'))
            );
        }
        return $actions;
    }
}
