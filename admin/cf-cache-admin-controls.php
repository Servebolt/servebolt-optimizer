<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//
require __DIR__ . '/cf-cache-admin-controls-ajax.php';

/**
 * Class CF_Cache_Admin_Controls
 *
 * This class initiates the admin GUI for the Cloudflare cache feature.
 */
class CF_Cache_Admin_Controls {

	/**
	 * @var null Singleton instance.
	 */
	private static $instance = null;

	/**
	 * Singleton instantiation.
	 *
	 * @return CF_Cache_Admin_Controls|null
	 */
	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new CF_Cache_Admin_Controls;
		}
		return self::$instance;
	}

	/**
	 * CF_Cache_Admin_Controls constructor.
	 */
	private function __construct() {
		$this->init_ajax();
		$this->init_assets();
		$this->init_settings();
	}

	/**
	 * Initialize AJAX callbacks.
	 */
	private function init_ajax() {
		new CF_Cache_Admin_Controls_Ajax;
	}

	/**
	 * Init assets.
	 */
	private function init_assets() {
		add_action('admin_enqueue_scripts', [$this, 'plugin_scripts']);
	}

	/**
	 * Plugin scripts.
	 */
	public function plugin_scripts() {
		$screen = get_current_screen();
		if ( $screen->id != 'servebolt_page_servebolt-cf-cache-control' ) return;
		wp_enqueue_script( 'servebolt-optimizer-cloudflare-scripts', SERVEBOLT_PATH_URL . 'admin/assets/js/cloudflare.js', ['servebolt-optimizer-scripts'], filemtime(SERVEBOLT_PATH . 'admin/assets/js/cloudflare.js'), true );
	}

	/**
	 * Initialize settings.
	 */
	private function init_settings() {
		add_action( 'admin_init', [$this, 'register_settings'] );
	}

	/**
	 * @return array
	 */
	private function settings_items() {
		return [
			'cf_switch',
			'cf_zone_id',
			'cf_auth_type',
			'cf_email',
			'cf_api_key',
			'cf_api_token',
			//'cf_items_to_purge', // No longer managed from the options page, only via AJAX
			'cf_cron_purge'];
	}

	/**
	 * The default auth type if none is selected.
	 *
	 * @return string
	 */
	private function get_default_auth_type() {
		return 'api_token';
	}

	/**
	 * Get all plugin settings in array.
	 *
	 * @param bool $with_values
	 *
	 * @return array
	 */
	public function get_settings_items($with_values = true) {
		$items = $this->settings_items();
		if ( $with_values ) {
			$items_with_values = [];
			foreach ( $items as $item ) {
				switch ($item) {
					case 'cf_switch':
						$items_with_values[$item] = sb_cf_cache()->cf_is_active();
						break;
					case 'cf_auth_type':
						$value = sb_get_option($item);
						$items_with_values['cf_auth_type'] = $value ?: $this->get_default_auth_type();
						break;
					default:
						$items_with_values[$item] = sb_get_option($item);
						break;
				}
			}
			return $items_with_values;
		}
		return $items;
	}

	/**
	 * Register custom options.
	 */
	public function register_settings() {
		foreach($this->settings_items() as $key) {
			register_setting('sb-cf-options-page', sb_get_option_name($key));
		}
	}

	/**
	 * Display view.
	 */
	public function view() {
		sb_view('admin/views/cf-cache-admin-controls');
	}

}
CF_Cache_Admin_Controls::get_instance();
