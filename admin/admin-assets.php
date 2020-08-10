<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Servebolt_Admin_Assets
 *
 * This class includes the CSS and JavaScript of the plugin.
 */
class Servebolt_Admin_Assets {

	/**
	 * Servebolt_Admin_Assets constructor.
	 */
	public function __construct() {
		add_action('init', [$this, 'init_assets']);
	}

	/**
	 * Init assets.
	 */
	public function init_assets() {

		if ( ! is_user_logged_in() ) return;

		// Front-end only assets
		add_action('wp_enqueue_scripts', [$this, 'plugin_public_styling'], 100);

		// Common assets
		add_action('wp_enqueue_scripts', [$this, 'plugin_common_styling'], 100);
		add_action('wp_enqueue_scripts', [$this, 'plugin_common_scripts'], 100);

		// Admin assets
		add_action('admin_enqueue_scripts', [$this, 'plugin_common_styling'], 100);
		add_action('admin_enqueue_scripts', [$this, 'plugin_common_scripts'], 100);
	}

	/**
	 * Plugin styling (public only).
	 */
	public function plugin_public_styling() {
		wp_enqueue_style( 'servebolt-optimizer-public-styling', SERVEBOLT_PATH_URL . 'admin/assets/css/public-style.css', [], filemtime(SERVEBOLT_PATH . 'admin/assets/css/public-style.css') );
	}

	/**
	 * Plugin common scripts (scripts for both WP Admin and front end).
	 */
	public function plugin_common_styling() {
		wp_enqueue_style( 'servebolt-optimizer-styling', SERVEBOLT_PATH_URL . 'admin/assets/css/admin-style.css', [], filemtime(SERVEBOLT_PATH . 'admin/assets/css/admin-style.css') );
		wp_enqueue_style( 'sb-sweetalert2', SERVEBOLT_PATH_URL . 'admin/assets/css/sweetalert2.min.css', [], filemtime(SERVEBOLT_PATH . 'admin/assets/css/sweetalert2.min.css') );
		wp_enqueue_style( 'servebolt-optimizer-common-styling', SERVEBOLT_PATH_URL . 'admin/assets/css/common-style.css', [], filemtime(SERVEBOLT_PATH . 'admin/assets/css/common-style.css') );
	}

	/**
	 * Plugin styling (styling for both WP Admin and front end).
	 */
	public function plugin_common_scripts() {
		wp_enqueue_script( 'sb-sweetalert2', SERVEBOLT_PATH_URL . 'admin/assets/js/sweetalert2.all.min.js', [], filemtime(SERVEBOLT_PATH . 'admin/assets/js/sweetalert2.all.min.js'), true );
		wp_enqueue_script( 'servebolt-optimizer-scripts', SERVEBOLT_PATH_URL . 'admin/assets/js/general.js', [], filemtime(SERVEBOLT_PATH . 'admin/assets/js/general.js'), true );
		wp_enqueue_script( 'servebolt-optimizer-cloudflare-cache-purge-trigger-scripts', SERVEBOLT_PATH_URL . 'admin/assets/js/cloudflare-cache-purge-trigger.js', [], filemtime(SERVEBOLT_PATH . 'admin/assets/js/cloudflare-cache-purge-trigger.js'), true );
		wp_localize_script( 'servebolt-optimizer-cloudflare-cache-purge-trigger-scripts', 'ajax_object', [
			'ajax_nonce' => sb_get_ajax_nonce(),
			'ajaxurl'    => admin_url( 'admin-ajax.php' ),
		] );
	}

}
new Servebolt_Admin_Assets;
