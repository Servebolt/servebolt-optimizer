<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Servebolt\Optimizer\CachePurge\CachePurge;

/**
 * Class Servebolt_Admin_Bar_Interface
 *
 * This class initiates the WP Admin bar item for the Optimizer plugin.
 */
class Servebolt_Admin_Bar_Interface {

	/**
	 * Servebolt_Admin_Interface_Bar constructor.
	 */
	public function __construct() {
		add_action('init', [$this, 'init_admin_bar_dropdown_menu']);
	}

	/**
	 * Init admin bar dropdown menu.
	 */
	public function init_admin_bar_dropdown_menu() {
		if ( ! is_user_logged_in() ) return;
		add_action( 'admin_bar_menu', [ $this, 'admin_bar' ], 100 );
	}

	/**
	 * Add our items to the admin bar.
	 *
	 * @param $wp_admin_bar
	 */
	public function admin_bar($wp_admin_bar) {

		if ( ! apply_filters('sb_optimizer_display_admin_bar_menu', true) ) return;

		if ( ! apply_filters('sb_optimizer_display_admin_bar_menu_by_user_capabilities', current_user_can('manage_options')) ) return;

		$sb_icon = '<span class="servebolt-icon"></span>';
		$nodes = $this->get_admin_bar_nodes();

		if ( count($nodes) > 1 ) {
			$parent_id = 'servebolt-optimizer';
			$nodes = array_map(function($node) use ($parent_id) {
				$node['parent'] = $parent_id;
				return $node;
			}, $nodes);
			$nodes = array_merge([
				[
					'id'    => $parent_id,
					'title' => sb__('Servebolt Optimizer'),
					'href'  => false,
					'meta'  => [
						'target' => '_blank',
						'class' => 'sb-admin-button'
					]
				]
			], $nodes);
		}

		// Add SB icon to first element
		if ( isset($nodes[0]) ) {
			$nodes[0]['title'] = $sb_icon . $nodes[0]['title'];
		}

		if ( ! empty($nodes) ) {
			foreach ( $nodes as $node ) {
				$wp_admin_bar->add_node($node);
			}
		}

	}

	/**
	 * Get admin bar nodes.
	 *
	 * @return array
	 */
	private function get_admin_bar_nodes() {
		$nodes = [];
		$method = is_multisite() && is_network_admin() ? 'network_admin_url' : 'admin_url';

		if ( $admin_url = Servebolt\Optimizer\Helpers\sbGetAdminUrl() ) {
			$nodes[] = [
				'id'    => 'servebolt-crontrol-panel',
				'title' => sb__('Servebolt Control Panel'),
				'href'  => $admin_url,
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
				'title' => sb__('Purge Cloudflare Cache for all sites'),
				'href'  => '#',
				'meta'  => [
					'target' => '_blank',
					'class' => 'sb-admin-button sb-purge-network-cache'
				]
			];
		    */
		}

        //$cache_purge_available = sb_cf_cache()->should_use_cf_feature();
        $cache_purge_available = CachePurge::featureIsActive();

		if ( ! is_network_admin() ) {
            if ( $cache_purge_available ) {
                $nodes[] = [
                    'id' => 'servebolt-clear-all-cf-cache',
                    'title' => sb__('Purge all cache'),
                    'href' => '#',
                    'meta' => [
                        'class' => 'sb-admin-button sb-purge-all-cache'
                    ]
                ];
            }
        }

        if ( $cache_purge_available ) {
            $nodes[] = [
                'id' => 'servebolt-clear-cf-cache-url',
                'title' => sb__('Purge a URL'),
                'href' => '#',
                'meta' => [
                    'class' => 'sb-admin-button sb-purge-url'
                ]
            ];
        }

        if ( ! is_network_admin() ) {
            if ($cache_purge_available) {
				if ( $post_id = $this->is_single_post() ) {
					$nodes[] = [
						'id'    => 'servebolt-clear-current-cf-cache',
						'title' => '<span data-id="' . $post_id . '">' . sb__('Purge current post cache') . '</span>',
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
			'title' => sb__('Settings'),
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
	 * @return bool
	 */
	private function is_single_post() {
		if ( ! is_admin() && is_singular() && $post_id = get_the_ID() ) {
			return $post_id;
		}
		global $post, $pagenow;
		if ( is_admin() && $pagenow == 'post.php' && $post->ID ) {
			return $post->ID;
		}
		return false;
	}

}
new Servebolt_Admin_Bar_Interface;
