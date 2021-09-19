<?php

namespace Servebolt\Optimizer\Admin\AdminBarGui\Nodes;

if (!defined('ABSPATH')) exit;

use Servebolt\Optimizer\Admin\AdminBarGui\NodeInterface;
use function Servebolt\Optimizer\Helpers\getServeboltAdminUrl;

/**
 * Class ServeboltControlPanelNode
 * @package Servebolt\Optimizer\Admin\AdminBarGui\Nodes
 */
class ServeboltControlPanelNode implements NodeInterface
{
    /**
     * Check whether the nodes should be displayed.
     *
     * @return bool
     */
    public static function shouldDisplayNodes(): bool
    {
        return apply_filters(
            'sb_optimizer_admin_bar_display_control_panel_node',
            current_user_can('manage_options')
        );
    }

    /**
     * Generate nodes.
     *
     * @return array|null
     */
    public static function generateNodes(): ?array
    {
        if ($adminUrl = getServeboltAdminUrl()) {
            return [
                [
                    'id'    => 'servebolt-control-panel',
                    'title' => __('Servebolt Control Panel', 'servebolt-wp'),
                    'href'  => $adminUrl,
                    'meta'  => [
                        'target' => '_blank',
                    ]
                ]
            ];
        }
        return null;
    }
}
