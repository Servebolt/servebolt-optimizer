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
 *
 * This class initiates the admin interface for the plugin.
 */
class Servebolt_Admin_Interface {

	/**
	 * Servebolt_Admin_Interface constructor.
	 */
	public function __construct() {
		add_action('init', [$this, 'admin_init']);
	}

	/**
	 * Admin init.
	 */
	public function admin_init() {
		if ( ! is_user_logged_in() ) return;
		$this->init_menus();
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

}
new Servebolt_Admin_Interface;

