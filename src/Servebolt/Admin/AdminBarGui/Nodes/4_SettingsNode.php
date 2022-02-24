<?php

namespace Servebolt\Optimizer\Admin\AdminBarGui\Nodes;

if (!defined('ABSPATH')) exit;

use Servebolt\Optimizer\Admin\AdminBarGui\NodeInterface;

/**
 * Class SettingsNode
 * @package Servebolt\Optimizer\Admin\AdminBarGui\Nodes
 */
class SettingsNode implements NodeInterface
{
    /**
     * Check whether the nodes should be displayed.
     *
     * @return bool
     */
    public static function shouldDisplayNodes(): bool
    {
        return apply_filters(
            'sb_optimizer_admin_bar_display_settings_node',
            current_user_can('manage_options')
        );
    }

    /**
     * Generate nodes.
     *
     * @return array[]
     */
    public static function generateNodes(): array
    {
        $method = is_multisite() && is_network_admin() ? 'network_admin_url' : 'admin_url';
        return [
            [
                'id'    => 'servebolt-plugin-settings',
                'title' => __('Settings', 'servebolt-wp'),
                'href'  => $method('admin.php?page=servebolt-wp'),
            ]
        ];
    }
}
