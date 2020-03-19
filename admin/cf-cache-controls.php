<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class CF_Cache_Controls
 */
class CF_Cache_Controls {

	/**
	 * @var null Singleton instance.
	 */
	private static $instance = null;

	/**
	 * Singleton instantiation.
	 *
	 * @return CF_Cache_Controls|null
	 */
	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new CF_Cache_Controls;
		}
		return self::$instance;
	}

	/**
	 * CF_Cache_Controls constructor.
	 */
	private function __construct() {
		$this->add_ajax_handling();
		$this->register_actions();
		$this->init_settings();
	}

	/**
	 * Initialize settings.
	 */
	private function init_settings() {
		add_action( 'admin_init', [$this, 'register_settings'] );
	}

	/**
	 * Register action hooks.
	 */
	private function register_actions() {
		if ( ! sb_cf()->cf_is_active() ) return;
		add_action( 'save_post', [sb_cf(), 'purge_post'], 99 );
	}

	/**
	 * @return array
	 */
	private function settings_items() {
		return ['cf_switch', 'cf_zone_id', 'cf_auth_type', 'cf_email', 'cf_api_key', 'cf_api_token', 'cf_items_to_purge', 'cf_cron_purge'];
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
						$items_with_values[$item] = sb_cf()->cf_is_active();
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
	 * Add AJAX handling.
	 */
	private function add_ajax_handling() {
		add_action( 'wp_ajax_servebolt_purge_all_cache', [ $this, 'purge_all_cache_callback' ] );
		add_action( 'wp_ajax_servebolt_purge_url', [ $this, 'purge_url_callback' ] );
	}

	/**
	 * Purge all cache in Cloudflare cache.
	 */
	public function purge_all_cache_callback() {
		if ( ! sb_cf()->cf_cache_feature_available() ) {
			wp_send_json_error(['message' => 'Cloudflare cache feature is not active so we could not purge cache. Make sure you have added Cloudflare API credentials and selected zone.']);
		} elseif ( sb_cf()->purge_all() ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * Purge specific URL in Cloudflare cache.
	 */
	public function purge_url_callback() {
		$url = esc_url_raw($_POST['url']);
		if ( ! $url || empty($url) ) {
			wp_send_json_error(['message' => 'Please specify the URL you would like to purge cache for.']);
		} elseif ( ! sb_cf()->cf_cache_feature_available() ) {
			wp_send_json_error(['message' => 'Cloudflare cache feature is not active so we could not purge cache. Make sure you have added Cloudflare API credentials and selected zone.']);
		} elseif ( sb_cf()->purge_by_url($url) ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * Display view.
	 */
	public function view() {
		sb_view('admin/views/cf-cache-controls');
	}

}
sb_cf_cache_controls();
