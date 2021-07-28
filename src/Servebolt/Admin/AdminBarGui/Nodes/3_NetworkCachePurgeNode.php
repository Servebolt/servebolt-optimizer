<?php

namespace Servebolt\Optimizer\Admin\AdminBarGui\Nodes;

if (!defined('ABSPATH')) exit;

use Servebolt\Optimizer\CachePurge\CachePurge;
use Servebolt\Optimizer\Admin\AdminBarGui\NodeInterface;
use function Servebolt\Optimizer\Helpers\iterateSites;

/**
 * Class NetworkCachePurgeNode
 * @package Servebolt\Optimizer\Admin\AdminBarGui\Nodes
 */
class NetworkCachePurgeNode implements NodeInterface
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
            'sb_optimizer_admin_bar_display_network_cache_purge_node',
            (
                is_multisite()
                && is_network_admin()
                && self::hasSiteWithCachePurgeFeatureAvailable()
            )
        );
    }

    /**
     * Check if one or more sites has the cache purge feature available.
     *
     * @return bool
     */
    private static function hasSiteWithCachePurgeFeatureAvailable(): bool
    {
        $hasCachePurgeFeatureAvailable = false;
        iterateSites(function ($site) use (&$hasCachePurgeFeatureAvailable) {
            if (CachePurge::featureIsAvailable($site->blog_id)) {
                $hasCachePurgeFeatureAvailable = true;
            }
        });
        return $hasCachePurgeFeatureAvailable;
    }

    /**
     * Generate nodes.
     *
     * @return array
     */
    public static function generateNodes(): array
    {
        self::addPurgeAllNetworkNode();
        return self::$nodes;
    }

    /**
     * Purge all cache (for all sites in multisite).
     */
    private static function addPurgeAllNetworkNode(): void
    {
        if (!apply_filters(
            'sb_optimizer_admin_bar_cache_purge_can_purge_all_network',
            current_user_can('manage_options')
        )) {
            return;
        }
        self::$nodes[] = [
            'id'    => 'servebolt-clear-network-cache',
            'title' => __('Purge Cache for all sites', 'servebolt-wp'),
            'href'  => '#',
            'meta'  => [
                'target' => '_blank',
                'class' => 'sb-admin-button sb-purge-network-cache'
            ]
        ];
    }
}
