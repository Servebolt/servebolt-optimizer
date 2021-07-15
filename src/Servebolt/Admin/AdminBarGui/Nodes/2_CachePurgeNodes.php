<?php

namespace Servebolt\Optimizer\Admin\AdminBarGui\Nodes;

if (!defined('ABSPATH')) exit;

use Servebolt\Optimizer\Admin\AdminBarGui\NodeInterface;
use Servebolt\Optimizer\Admin\CachePurgeControl\Ajax\PurgeActions;
use Servebolt\Optimizer\CachePurge\CachePurge;
use function Servebolt\Optimizer\Helpers\getPostTypeSingularName;
use function Servebolt\Optimizer\Helpers\getTaxonomySingularName;

/**
 * Class CachePurgeActionsNodes
 * @package Servebolt\Optimizer\Admin\AdminBarGui\Nodes
 */
class CachePurgeNodes implements NodeInterface
{

    /**
     * Array containing the nodes.
     *
     * @var array
     */
    private static $nodes = [];

    /**
     * Check whether the nodes should be displayed.
     *
     * @return bool
     */
    public static function shouldDisplayNodes(): bool
    {
        return apply_filters('sb_optimizer_admin_bar_display_cache_purge_node', CachePurge::featureIsAvailable());
    }

    /**
     * Generate nodes.
     *
     * @return array
     */
    public static function generateNodes(): array
    {
        if (is_network_admin()) {
            self::addPurgeAllNetworkNode();
        } else {
            self::addPurgeAllNode();
        }

        self::purgeUrlNode();

        if (!is_network_admin()) {
            self::purgePost();
            self::purgeTerm();
        }

        return self::$nodes;
    }

    /**
     * Purge all cache (for all sites in multisite).
     */
    private static function addPurgeAllNetworkNode(): void
    {
        // TODO: Re-introduce this feature
        /*
        if (!apply_filters(
            'sb_optimizer_admin_bar_cache_purge_can_purge_all_network',
            current_user_can('manage_options')
        )) {
            return;
        }
        self::$nodes[] = [
            'id'    => 'servebolt-clear-cf-network-cache',
            'title' => __('Purge Cloudflare Cache for all sites', 'servebolt-wp'),
            'href'  => '#',
            'meta'  => [
                'target' => '_blank',
                'class' => 'sb-admin-button sb-purge-network-cache'
            ]
        ];
        */
    }

    /**
     * Purge all cache (for current site).
     */
    private static function addPurgeAllNode(): void
    {
        if (!apply_filters(
            'sb_optimizer_admin_bar_cache_purge_can_purge_all',
            PurgeActions::canPurgeAllCache()
        )) {
            return;
        }
        self::$nodes[] = [
            'id' => 'servebolt-clear-all-cf-cache',
            'title' => __('Purge all cache', 'servebolt-wp'),
            'href' => '#',
            'meta' => [
                'class' => 'sb-admin-button sb-purge-all-cache'
            ]
        ];
    }

    /**
     * Purge URL (admin feature).
     */
    private static function purgeUrlNode(): void
    {
        if (!apply_filters(
            'sb_optimizer_admin_bar_cache_purge_can_purge_url',
            PurgeActions::canPurgeCacheByUrl()
        )) {
            return;
        }
        self::$nodes[] = [
            'id' => 'servebolt-clear-cf-cache-url',
            'title' => __('Purge a URL', 'servebolt-wp'),
            'href' => '#',
            'meta' => [
                'class' => 'sb-admin-button sb-purge-url'
            ]
        ];
    }

    /**
     * Purge post.
     */
    private static function purgePost(): void
    {
        if (!apply_filters('sb_optimizer_allow_admin_bar_cache_purge_for_post', true)) {
            return;
        }
        if (!$postId = self::getSinglePostId()) {
            return;
        }
        if (!PurgeActions::canPurgePostCache($postId)) {
            return;
        }
        $objectName = getPostTypeSingularName($postId);
        $nodeText = sprintf(__('Purge %s cache', 'servebolt-wp'), $objectName);
        self::$nodes[] = [
            'id'    => 'servebolt-clear-current-post-cache',
            'title' => sprintf('<span data-object-name="%s" data-id="%s">%s</span>', $objectName, $postId, $nodeText),
            'href'  => '#',
            'meta'  => [
                'class' => 'sb-admin-button sb-purge-current-post-cache',
            ]
        ];
    }

    /**
     * Purge term.
     */
    private static function purgeTerm(): void
    {
        if (!apply_filters('sb_optimizer_allow_admin_bar_cache_purge_for_term', true)) {
            return;
        }
        if (!$termId = self::getSingleTermId()) {
            return;
        }
        if (!PurgeActions::canPurgeTermCache($termId)) {
            return;
        }
        $objectName = getTaxonomySingularName($termId);
        $nodeText = sprintf(__('Purge %s cache', 'servebolt-wp'), $objectName);
        self::$nodes[] = [
            'id' => 'servebolt-clear-current-term-cache',
            'title' => sprintf('<span data-object-name="%s" data-id="%s">%s</span>', $objectName, $termId, $nodeText),
            'href' => '#',
            'meta' => [
                'class' => 'sb-admin-button sb-purge-current-term-cache',
            ]
        ];
    }

    /**
     * Check whether we should allow cache purge of current post (if there is any).
     *
     * @return int|null
     */
    private static function getSinglePostId(): ?int
    {
        if (!is_admin() && is_singular() && $postId = get_the_ID()) {
            return $postId;
        }
        global $post, $pagenow;
        if (is_admin() && $pagenow == 'post.php' && $post->ID) {
            return $post->ID;
        }
        return null;
    }

    /**
     * Check whether we should allow cache purge of current term (if there is any).
     *
     * @return int|null
     */
    private static function getSingleTermId(): ?int
    {
        if (!is_admin()) {
            $queriedObject = get_queried_object();
            if (is_a($queriedObject, 'WP_Term')) {
                return $queriedObject->term_id;
            }
        }
        global $pagenow;
        if (is_admin() && $pagenow == 'term.php') {
            if ($termId = absint($_REQUEST['tag_ID'])) {
                return $termId;
            }
        }
        return null;
    }
}
