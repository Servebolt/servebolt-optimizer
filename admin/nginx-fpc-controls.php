<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Nginx_FPC_Controls
 *
 * This class displays the Nginx Full Page Cache control GUI.
 */
class Nginx_FPC_Controls {

	/**
	 * @var null Singleton instance.
	 */
	private static $instance = null;

	/**
	 * Singleton instantiation.
	 *
	 * @return Nginx_FPC_Controls|null
	 */
	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new Nginx_FPC_Controls;
		}
		return self::$instance;
	}

	/**
	 * Nginx_FPC_Controls constructor.
	 */
	private function __construct() {
		$this->init_settings();
	}

	/**
	 * Initialize settings.
	 */
	private function init_settings() {
		add_action( 'admin_init', [$this, 'register_settings'] );
	}

	/**
	 * Register custom option.
	 */
	public function register_settings() {
		foreach(['fpc_settings', 'fpc_switch'] as $key) {
			register_setting('nginx-fpc-options-page', sb_get_option_name($key));
		}
	}

	/**
	 * Display view.
	 */
	public function view() {
		sb_view('admin/views/nginx-fpc-controls', [
			'sb_admin_url'     => sb_get_admin_url(),
		]);
	}

}
Nginx_FPC_Controls::get_instance();
