<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Nginx_Controls
 */
class Nginx_Controls {

	/**
	* @var null Singleton instance.
	*/
	private static $instance = null;

	/**
	* Singleton instantiation.
	*
	* @return Nginx_Controls|null
	*/
	public static function getInstance() {
		if ( self::$instance == null ) {
			self::$instance = new Nginx_Controls;
		}
		return self::$instance;
	}

	/**
	* Nginx_Controls constructor.
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
		sb_view('admin/views/nginx-controls', [
			'sites'        => is_network_admin() ? get_sites() : [],
			'options'      => sb_get_option('fpc_settings'),
			'post_types'   => get_post_types(['public' => true], 'objects'),
			'nginx_switch' => sb_get_option('fpc_switch') === 'on',
			'sb_admin_url' => get_sb_admin_url(),
		]);
	}

}
Nginx_Controls::getInstance();
