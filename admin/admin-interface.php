<?php
if ( ! defined( 'ABSPATH' ) ) exit;

require_once SERVEBOLT_PATH . 'admin/log-viewer.php';
require_once SERVEBOLT_PATH . 'admin/performance-checks.php';
require_once SERVEBOLT_PATH . 'admin/nginx-fpc-controls.php';
require_once SERVEBOLT_PATH . 'admin/cf-cache-admin-controls.php';
require_once SERVEBOLT_PATH . 'admin/cf-image-resizing.php';
require_once SERVEBOLT_PATH . 'admin/optimize-db/optimize-db.php';

/**
 * Class Servebolt_Admin_Interface
 */
class Servebolt_Admin_Interface {

	/**
	 * Servebolt_Admin_Interface constructor.
	 */
	public function __construct() {
	    $this->init_menus();
	    $this->init_assets();
	    $this->init_plugin_settings_link();
	}

	/**
	 * Init menus.
	 */
	private function init_menus() {
		if ( is_multisite() ) {
			add_action('network_admin_menu', [$this, 'admin_menu']);
			add_action('admin_menu', [$this, 'subsite_menu']);
		} else {
			add_action('admin_menu', [$this, 'admin_menu']);
		}
	}

	/**
	 * Init admin menus.
	 */
	public function admin_menu() {
		if ( ! apply_filters('sb_optimizer_display_menu', true) ) return;
		add_menu_page( sb__('Servebolt'), sb__('Servebolt'), 'manage_options', 'servebolt-wp', [$this, 'general_page_callback'], SERVEBOLT_PATH_URL . 'admin/assets/img/servebolt-icon.svg' );
		add_submenu_page('servebolt-wp', sb__('General'), sb__('General'), 'manage_options', 'servebolt-wp');
		$this->add_sub_menu_items();
	}

	/**
	 * Add sub menu items.
	 */
	private function add_sub_menu_items() {
		add_submenu_page('servebolt-wp', sb__('Performance optimizer'), sb__('Performance optimizer'), 'manage_options', 'servebolt-performance-tools', [$this, 'performance_callback']);
		add_submenu_page('servebolt-wp', sb__('Cloudflare Cache'), sb__('Cloudflare Cache'), 'manage_options', 'servebolt-cf-cache-control', [$this, 'cf_cache_callback']);

		if ( sb_feature_active('cf_image_resize') ) {
			add_submenu_page('servebolt-wp', sb__('Cloudflare Image Resizing'), sb__('Cloudflare Image Resizing'), 'manage_options', 'servebolt-cf-image-resizing', [$this, 'cf_image_resizing_callback']);
		}

		if ( host_is_servebolt() === true ) {
			add_submenu_page('servebolt-wp', sb__('Page Cache'), sb__('Full Page Cache'), 'manage_options', 'servebolt-nginx-cache', [$this, 'nginx_cache_callback']);
			add_submenu_page('servebolt-wp', sb__('Error log'), sb__('Error log'), 'manage_options', 'servebolt-logs', [$this, 'error_log_callback']);
		}

		if ( ! is_multisite() && sb_is_dev_debug() ) {
			add_submenu_page('servebolt-wp', sb__('Debug'), sb__('Debug'), 'manage_options', 'servebolt-debug', [$this, 'debug_callback']);
		}

		add_action( 'admin_bar_menu', [ $this, 'admin_bar' ], 100 );
	}

	/**
	 * Init subsite menus.
	 */
	public function subsite_menu() {
		if ( ! apply_filters('sb_optimizer_display_menu', true) ) return;
		add_menu_page( sb__('Servebolt'), sb__('Servebolt'), 'manage_options', 'servebolt-wp', [$this, 'general_page_callback'], SERVEBOLT_PATH_URL . 'admin/assets/img/servebolt-icon.svg' );
		add_submenu_page('servebolt-wp', sb__('General'), sb__('General'), 'manage_options', 'servebolt-wp');
		add_submenu_page('servebolt-wp', sb__('Cloudflare Cache'), sb__('Cloudflare Cache'), 'manage_options', 'servebolt-cf-cache-control', [$this, 'cf_cache_callback']);

		if ( sb_feature_active('cf_image_resize') ) {
			add_submenu_page('servebolt-wp', sb__('Cloudflare Image Resizing'), sb__('Cloudflare Image Resizing'), 'manage_options', 'servebolt-cf-image-resizing', [$this, 'cf_image_resizing_callback']);
		}

		if ( host_is_servebolt() === true ) {
			add_submenu_page('servebolt-wp', sb__('Page Cache'), sb__('Full Page Cache'), 'manage_options', 'servebolt-nginx-cache', [$this, 'nginx_cache_callback']);
		}

		if ( is_multisite() && sb_is_dev_debug() ) {
			add_submenu_page('servebolt-wp', sb__('Debug'), sb__('Debug'), 'manage_options', 'servebolt-debug', [$this, 'debug_callback']);
		}

		add_action('admin_bar_menu', [$this, 'admin_bar'], 100);
	}

	/**
	 * Init assets.
	 */
	private function init_assets() {
		add_action('admin_enqueue_scripts', [$this, 'plugin_styling']);
		add_action('admin_enqueue_scripts', [$this, 'plugin_scripts']);
	}

	/**
	 * Plugin scripts.
	 */
	public function plugin_scripts() {
		wp_enqueue_script( 'servebolt-optimizer-scripts', SERVEBOLT_PATH_URL . 'admin/assets/js/general.js', [], filemtime(SERVEBOLT_PATH . 'admin/assets/js/general.js'), true );
		wp_enqueue_script( 'servebolt-optimizer-cloudflare-cache-purge-scripts', SERVEBOLT_PATH_URL . 'admin/assets/js/cloudflare-cache-purge.js', ['servebolt-optimizer-scripts'], filemtime(SERVEBOLT_PATH . 'admin/assets/js/cloudflare-cache-purge.js'), true );
		wp_enqueue_script( 'sb-sweetalert2', SERVEBOLT_PATH_URL . 'admin/assets/js/sweetalert2.all.min.js', ['servebolt-optimizer-scripts'], filemtime(SERVEBOLT_PATH . 'admin/assets/js/sweetalert2.all.min.js'), true );
		wp_localize_script( 'servebolt-optimizer-scripts', 'ajax_object', ['ajax_nonce' => sb_get_ajax_nonce()] );
	}

	/**
	 * Plugin styling.
	 */
	public function plugin_styling() {
		wp_enqueue_style( 'servebolt-optimizer-styling', SERVEBOLT_PATH_URL . 'admin/assets/css/style.css', [], filemtime(SERVEBOLT_PATH . 'admin/assets/css/style.css') );
		wp_enqueue_style( 'sb-sweetalert2', SERVEBOLT_PATH_URL . 'admin/assets/css/sweetalert2.min.css', [], filemtime(SERVEBOLT_PATH . 'admin/assets/css/sweetalert2.min.css') );
	}

	/**
	 * Initialize plugin settings link hook.
	 */
	private function init_plugin_settings_link() {
		add_filter('plugin_action_links_' . SERVEBOLT_BASENAME, [$this, 'add_settings_link_to_plugin']);
	}

	/**
	 * Add settings-link in plugin list.
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public function add_settings_link_to_plugin($links) {
		$links[] = sprintf('<a href="%s">%s</a>', admin_url( 'options-general.php?page=servebolt-wp' ), sb__('Settings'));
		return $links;
	}

	/**
	 * Display Servebolt dashboard.
	 */
	public function general_page_callback() {
		sb_view('admin/views/servebolt-dashboard');
	}

	/**
	 * Display DB optimization page.
	 */
	public function performance_callback(){
		sb_performance_checks()->view();
	}

	/**
	 * Display the Cloudflare Cache control page.
	 */
	public function cf_cache_callback() {
		sb_cf_cache_admin_controls()->view();
	}

	/**
	 * Display the Cloudflare Image Resizing control pagel.
	 */
	public function cf_image_resizing_callback() {
		sb_cf_image_resizing()->view();
	}

	/**
	 * Display the Full Page Cache control page.
	 */
	public function nginx_cache_callback() {
		sb_nginx_fpc_controls()->view();
	}

	/**
	 * Display error log page.
	 */
	public function error_log_callback() {
		( Servebolt_Logviewer::get_instance() )->view();
	}

	/**
	 * Display debug information.
	 */
	public function debug_callback() {
		sb_view('admin/views/debug');
	}

	/**
	 * Get admin bar nodes.
	 *
	 * @return array
	 */
	private function get_admin_bar_nodes() {
		$nodes = [];
		$method = is_multisite() && is_network_admin() ? 'network_admin_url' : 'admin_url';

		if ( $admin_url = sb_get_admin_url() ) {
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

		if ( is_network_admin() ) {
			$nodes[] = [
				'id'    => 'servebolt-clear-cf-network-cache',
				'title' => sb__('Purge Cloudflare Cache for all sites'),
				'href'  => '#',
				'meta'  => [
					'target' => '_blank',
					'class' => 'sb-admin-button sb-purge-network-cache'
				]
			];
		}

		if ( ! is_network_admin() ) {
			$cache_purge_available = sb_cf()->should_use_cf_feature();
			if ( $cache_purge_available ) {
				$nodes[] = [
					'id'    => 'servebolt-clear-cf-cache',
					'title' => sb__('Purge All Cache'),
					'href'  => '#',
					'meta'  => [
						'class' => 'sb-admin-button sb-purge-all-cache'
					]
				];

				global $post, $pagenow;
				if ( $pagenow == 'post.php' && $post->ID ) {
					$nodes[] = [
						'id'    => 'servebolt-clear-current-cf-cache',
						'title' => '<span data-id="' . $post->ID . '">' . sb__('Purge Current Post Cache') . '</span>',
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

}
new Servebolt_Admin_Interface;

