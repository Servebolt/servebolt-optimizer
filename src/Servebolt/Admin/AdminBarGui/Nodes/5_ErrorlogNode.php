<?php

namespace Servebolt\Optimizer\Admin\AdminBarGui\Nodes;

if (!defined('ABSPATH')) exit;

use Servebolt\Optimizer\Admin\AdminBarGui\NodeInterface;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;

/**
 * Class ServeboltControlPanelNode
 * @package Servebolt\Optimizer\Admin\AdminBarGui\Nodes
 */
class ErrorLogNode implements NodeInterface
{
    /**
     * Check whether the nodes should be displayed.
     *
     * @return bool
     */
    public static function shouldDisplayNodes(): bool
    {
        return apply_filters(
            'sb_optimizer_admin_bar_display_error_log_node',
            (isHostedAtServebolt() && current_user_can('manage_options'))            
        );
    }

    /**
     * Generate nodes.
     *
     * @return array|null
     */
    public static function generateNodes(): ?array
    {
        $method = is_multisite() && is_network_admin() ? 'network_admin_url' : 'admin_url';
        return [
            [
                'id'    => 'servebolt-admin-menu-error-log',
                'title' => __('Error Logs', 'servebolt-wp'),
                'href'  => $method('admin.php?page=servebolt-logs')               
            ]
        ];
        return null;
    }
}
