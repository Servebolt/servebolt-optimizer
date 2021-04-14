<?php

namespace Servebolt\Optimizer\Admin\AdminBarGUI;

if ( ! defined( 'ABSPATH' ) ) exit;

use WP_Admin_Bar;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\CachePurge\CachePurge;
use function Servebolt\Optimizer\Helpers\getServeboltAdminUrl;

/**
 * Class Servebolt_Admin_Bar_Interface
 *
 * This class initiates the WP Admin bar item for the Optimizer plugin.
 */
class AdminBarGUI
{
    use Singleton;

    public static function init(): void
    {
        self::getInstance();
    }

	/**
	 * Servebolt_Admin_Interface_Bar constructor.
	 */
	private function __construct()
    {
        add_action( 'admin_bar_menu', [ $this, 'adminBar' ], 100 );
	}

	/**
	 * Add our items to the admin bar.
	 *
	 * @param WP_Admin_Bar $wpAdminBar
     * @return void|null
     */
	public function adminBar(WP_Admin_Bar $wpAdminBar): void
    {
		if (!apply_filters('sb_optimizer_display_admin_bar_menu', true)) {
            return;
        }
		if (!apply_filters('sb_optimizer_display_admin_bar_menu_by_user_capabilities', current_user_can('manage_options'))) {
            return;
        }

		$sbIcon = '<span class="servebolt-icon"></span>';
		$nodes = $this->getAdminBarNodes();

		if (count($nodes) > 1) {
			$parentId = 'servebolt-optimizer';
			$nodes = array_map(function($node) use ($parentId) {
				$node['parent'] = $parentId;
				return $node;
			}, $nodes);
			$nodes = array_merge([
				[
					'id'    => $parentId,
					'title' => __('Servebolt Optimizer', 'servebolt-wp'),
					'href'  => false,
					'meta'  => [
						'target' => '_blank',
						'class' => 'sb-admin-button'
					]
				]
			], $nodes);
		}

		// Add SB icon to first element
		if (isset($nodes[0])) {
			$nodes[0]['title'] = $sbIcon . $nodes[0]['title'];
		}

		if (!empty($nodes)) {
			foreach ($nodes as $node) {
                $wpAdminBar->add_node($node);
			}
		}
	}

	/**
	 * Get admin bar nodes.
	 *
	 * @return array
	 */
	private function getAdminBarNodes(): array
    {
		$nodes = [];
		$method = is_multisite() && is_network_admin() ? 'network_admin_url' : 'admin_url';

		if ($adminUrl = getServeboltAdminUrl()) {
			$nodes[] = [
				'id'    => 'servebolt-crontrol-panel',
				'title' => __('Servebolt Control Panel', 'servebolt-wp'),
				'href'  => $adminUrl,
				'meta'  => [
					'target' => '_blank',
					'class' => 'sb-admin-button'
				]
			];
		}

		if (is_network_admin() ) {
		    // TODO: Re-introduce this feature
		    /*
			$nodes[] = [
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

        $cachePurgeAvailable = CachePurge::featureIsAvailable();

		if (!is_network_admin()) {
            if ($cachePurgeAvailable) {
                $nodes[] = [
                    'id' => 'servebolt-clear-all-cf-cache',
                    'title' => __('Purge all cache', 'servebolt-wp'),
                    'href' => '#',
                    'meta' => [
                        'class' => 'sb-admin-button sb-purge-all-cache'
                    ]
                ];
            }
        }

        if ($cachePurgeAvailable) {
            $nodes[] = [
                'id' => 'servebolt-clear-cf-cache-url',
                'title' => __('Purge a URL', 'servebolt-wp'),
                'href' => '#',
                'meta' => [
                    'class' => 'sb-admin-button sb-purge-url'
                ]
            ];
        }

        if (!is_network_admin()) {
            if ($cachePurgeAvailable) {
				if ($postId = $this->getSinglePostId()) {
					$nodes[] = [
						'id'    => 'servebolt-clear-current-cf-cache',
						'title' => '<span data-id="' . $postId . '">' . __('Purge current post cache', 'servebolt-wp') . '</span>',
						'href'  => '#',
						'meta'  => [
							'class' => 'sb-admin-button sb-purge-current-post-cache'
						]
					];
				}
			}
		}

		$nodes[] = [
			'id'    => 'servebolt-plugin-settings',
			'title' => __('Settings', 'servebolt-wp'),
			'href'  => $method('admin.php?page=servebolt-wp'),
			'meta'  => [
				'target' => '',
				'class' => 'sb-admin-button'
			]
		];

		return $nodes;
	}

	/**
	 * Check whether we should allow post purge of current post (if there is any).
	 *
     * @return int|null
     */
	private function getSinglePostId(): ?int
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

}
