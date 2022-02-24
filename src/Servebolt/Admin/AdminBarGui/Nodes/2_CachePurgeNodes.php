<?php

namespace Servebolt\Optimizer\Admin\AdminBarGui\Nodes;

if (!defined('ABSPATH')) exit;

use Servebolt\Optimizer\Admin\AdminBarGui\NodeInterface;
use Servebolt\Optimizer\Admin\CachePurgeControl\Ajax\PurgeActions;
use Servebolt\Optimizer\Admin\CachePurgeControl\CachePurgeControl;
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
        return apply_filters(
            'sb_optimizer_admin_bar_display_cache_purge_node',
            (
                !is_network_admin()
                && CachePurge::featureIsAvailable()
            )
        );
    }

    /**
     * Generate nodes.
     *
     * @return array
     */
    public static function generateNodes(): array
    {
        self::addPurgePost();
        self::addPurgeTerm();
        self::addPurgeUrlNode();
        self::addPurgeAllNode();
        return self::$nodes;
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
            'title' => __('Purge All Cache', 'servebolt-wp'),
            'href' => '#',
            'meta' => [
                'class' => 'sb-admin-button sb-purge-all-cache'
            ]
        ];
    }

    /**
     * Purge URL (admin feature).
     */
    private static function addPurgeUrlNode(): void
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
    private static function addPurgePost(): void
    {
        if (!apply_filters('sb_optimizer_allow_admin_bar_cache_purge_for_post', true)) {
            return;
        }
        if (!$postId = CachePurgeControl::getSinglePostId()) {
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
                'class' => 'sb-admin-button sb-purge-current-post-cache sb-purge-item',
            ]
        ];
    }

    /**
     * Purge term.
     */
    private static function addPurgeTerm(): void
    {
        if (!apply_filters('sb_optimizer_allow_admin_bar_cache_purge_for_term', true)) {
            return;
        }
        if (!$termId = CachePurgeControl::getSingleTermId()) {
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
                'class' => 'sb-admin-button sb-purge-current-term-cache sb-purge-item',
            ]
        ];
    }
}
