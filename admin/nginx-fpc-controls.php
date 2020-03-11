<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class nginx_fpc_controls
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
			'sites'            => is_network_admin() ? get_sites() : [],
			'options'          => sb_nginx_fpc()->get_cacheable_post_types(false),
			'post_types'       => get_post_types(['public' => true], 'objects'),
			'nginx_fpc_active' => sb_nginx_fpc()->fpc_is_active(),
			'sb_admin_url'     => get_sb_admin_url(),
		]);
	}

}
Nginx_FPC_Controls::get_instance();
