<?php

namespace Servebolt\Optimizer\Admin\AdminBarGui\Nodes;

if (!defined('ABSPATH')) exit;

use Servebolt\Optimizer\Admin\AdminBarGui\NodeInterface;
use Servebolt\Optimizer\Admin\CachePurgeControl\Ajax\PurgeActions;
use Servebolt\Optimizer\CachePurge\CachePurge;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;

/**
 * Class CdnCachePurgeNodes
 * @package Servebolt\Optimizer\Admin\AdminBarGui\Nodes
 */
class CdnCachePurgeNodes implements NodeInterface
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
                && isHostedAtServebolt()
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
        self::addPurgeCdnNode();
        return self::$nodes;
    }

    /**
     * Purge all cache (for current site).
     */
    private static function addPurgeCdnNode(): void
    {
        if (!apply_filters(
            'sb_optimizer_admin_bar_cache_purge_can_purge_cdn',
            PurgeActions::canPurgeCdnCache()
        )) {
            return;
        }
        self::$nodes[] = [
            'id' => 'servebolt-clear-all-cdn-cache',
            'title' => __('Purge CDN Cache', 'servebolt-wp'),
            'href' => '#',
            'meta' => [
                'class' => 'sb-admin-button sb-purge-cdn-cache'
            ]
        ];
    }
}
