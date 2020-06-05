<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class CF_Image_Resizing
 *
 * This class initiates the admin GUI for the Cloudflare Image Resize feature.
 */
class CF_Image_Resizing {

	/**
	 * @var null Singleton instance.
	 */
	private static $instance = null;

	/**
	 * Singleton instantiation.
	 *
	 * @return CF_Image_Resizing|null
	 */
	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new CF_Image_Resizing;
		}
		return self::$instance;
	}

	/**
	 * CF_Image_Resizing constructor.
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
		foreach(['cf_image_resizing', 'cf_image_upscale'] as $key) {
			register_setting('sb-cf-image-resizing-options-page', sb_get_option_name($key));
		}
	}

	/**
	 * Display view.
	 */
	public function view() {
		sb_view('admin/views/cf-image-resizing');
	}
}
CF_Image_Resizing::get_instance();
