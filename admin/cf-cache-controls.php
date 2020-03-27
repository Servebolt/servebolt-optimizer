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
		$this->init_assets();
		$this->init_settings();
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
		if ( $screen->id != 'servebolt_page_servebolt-cf' ) return;
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
						$items_with_values[$item] = sb_cf()->cf_is_active();
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
	 * Add AJAX handling.
	 */
	private function add_ajax_handling() {
		add_action( 'wp_ajax_servebolt_lookup_zones', [ $this, 'lookup_zones_callback' ] );
		add_action( 'wp_ajax_servebolt_lookup_zone', [ $this, 'lookup_zone_callback' ] );
		add_action( 'wp_ajax_servebolt_validate_cf_settings', [ $this, 'validate_cf_settings_form_callback' ] );
		add_action( 'wp_ajax_servebolt_update_cf_cache_purge_queue', [ $this, 'update_cache_purge_queue_callback' ] );
		add_action( 'wp_ajax_servebolt_purge_all_cache', [ $this, 'purge_all_cache_callback' ] );
		add_action( 'wp_ajax_servebolt_purge_url', [ $this, 'purge_url_callback' ] );

		if ( is_multisite() ) {
			add_action( 'wp_ajax_servebolt_purge_network_cache', [ $this, 'purge_network_cache_callback' ] );
		}
	}

	/**
	 * Update cache purge queue.
	 */
	public function update_cache_purge_queue_callback() {
		check_ajax_referer( sb_get_ajax_nonce_key(), 'security' );
		sb_ajax_user_allowed();

		$items_to_remove = sb_array_get('items', $_POST);
		if ( $items_to_remove === 'all' ) {
			sb_cf()->set_items_to_purge([]);
			wp_send_json_success();
		}
		if ( ! $items_to_remove || empty($items_to_remove) ) wp_send_json_error();
		if ( ! is_array($items_to_remove) ) $items_to_remove = [];
		$items_to_remove = array_filter($items_to_remove, function ($item) {
			return is_numeric($item) || is_string($item);
		});
		$current_items = sb_cf()->get_items_to_purge();
		if ( ! is_array($current_items) ) $current_items = [];
		$updated_items = array_filter($current_items, function($item) use ($items_to_remove) {
			return ! in_array($item, $items_to_remove);
		});
		sb_cf()->set_items_to_purge($updated_items);
		wp_send_json_success();
	}

	/**
	 * Try to resolve the zone name by zone ID.
	 */
	public function lookup_zone_callback() {
		check_ajax_referer( sb_get_ajax_nonce_key(), 'security' );
		sb_ajax_user_allowed();

		parse_str($_POST['form'], $form_data);
		$auth_type = sanitize_text_field($form_data['servebolt_cf_auth_type']);
		$api_token = sanitize_text_field($form_data['servebolt_cf_api_token']);
		$email     = sanitize_text_field($form_data['servebolt_cf_email']);
		$api_key   = sanitize_text_field($form_data['servebolt_cf_api_key']);
		$zone_id   = sanitize_text_field($form_data['servebolt_cf_zone_id']);
		try {
			switch ($auth_type) {
				case 'api_token':
					sb_cf()->cf()->set_credentials('api_token', compact('api_token'));
					break;
				case 'api_key':
					sb_cf()->cf()->set_credentials('api_key', compact('email', 'api_key'));
					break;
				default:
					throw new Exception;
			}
			$zone = sb_cf()->get_zone_by_id($zone_id);
			if ( $zone && isset($zone->name) ) {
				return wp_send_json_success([
					'zone' => $zone->name,
				]);
			}
			throw new Exception;
		} catch (Exception $e) {
			wp_send_json_error();
		}
	}

	/**
	 * Generate li-markup of zones-array.
	 *
	 * @param $zones
	 *
	 * @return string
	 */
	private function generate_zone_list_markup($zones) {
		$markup = '';
		foreach($zones as $zone) {
			$markup .= sprintf('<li><a href="#" data-name="%s" data-id="%s">%s (%s)</a></li>', esc_attr($zone->name), esc_attr($zone->id), $zone->name, $zone->id);
		}
		return $markup;
	}

	/**
	 * Try to fetch available zones based on given API credentials.
	 */
	public function lookup_zones_callback() {
		check_ajax_referer(sb_get_ajax_nonce_key(), 'security');
		sb_ajax_user_allowed();

		$auth_type = sanitize_text_field($_POST['auth_type']);
		$credentials = sb_array_get('credentials', $_POST);
		try {
			sb_cf()->cf()->set_credentials($auth_type, $credentials);
			$zones = sb_cf()->cf()->list_zones();
			if ( ! empty($zones) ) {
				wp_send_json_success([
					'markup' => $this->generate_zone_list_markup($zones),
				]);
				return;
			}
			throw new Exception;
		} catch (Exception $e) {
			wp_send_json_error();
		}
	}

	/**
	 * Validate Cloudflare settings form.
	 */
	public function validate_cf_settings_form_callback() {
		check_ajax_referer(sb_get_ajax_nonce_key(), 'security');
		sb_ajax_user_allowed();

		parse_str($_POST['form'], $form_data);
		$errors = [];

		$is_active = array_key_exists('servebolt_cf_switch', $form_data) && filter_var($form_data['servebolt_cf_switch'], FILTER_VALIDATE_BOOLEAN) === true;
		$auth_type = sanitize_text_field($form_data['servebolt_cf_auth_type']);
		$api_token = sanitize_text_field($form_data['servebolt_cf_api_token']);
		$email     = sanitize_text_field($form_data['servebolt_cf_email']);
		$api_key   = sanitize_text_field($form_data['servebolt_cf_api_key']);
		$zone_id   = sanitize_text_field($form_data['servebolt_cf_zone_id']);
		$validate_zone = false;

		if ( ! $is_active ) {
			wp_send_json_success();
		}

		switch ($auth_type) {
			case 'api_token':
				if ( empty($api_token) ) {
					$errors['api_token'] = sb__('You need to provide an API token.');
				} else {
					sb_cf()->cf()->set_credentials('api_token', compact('api_token'));
					try {
						if ( ! sb_cf()->cf()->verify_token() ) {
							throw new Exception;
						}
						$validate_zone = true;
					} catch (Exception $e) {
						$errors['api_token'] = sb__('Invalid API token.');
					}
				}
				break;
			case 'api_key':
				if ( empty($email) ) {
					$errors['email'] = sb__('You need to provide an email address.');
				}

				if ( empty($api_key) ) {
					$errors['api_key'] = sb__('You need to provide an API key.');
				}

				if ( ! empty($email) && ! empty($api_key) ) {
					sb_cf()->cf()->set_credentials('api_key', compact('email', 'api_key'));
					try {
						if ( ! sb_cf()->cf()->verify_user() ) {
							throw new Exception;
						}
						$validate_zone = true;
					} catch (Exception $e) {
						$errors['api_key_credentials'] = sb__( 'Invalid API credentials.' );
					}
				}

				break;
			default:
				$errors[] = sb__('Invalid authentication type.');

		}

		if ( $validate_zone ) {
			if ( empty($zone_id) ) {
				$errors['zone_id'] = sb__('You need to provide a zone.');
			} else {
				try {
					if ( ! $zone_id = sb_cf()->cf()->get_zone_by_id($zone_id) ) {
						throw new Exception;
					}
				} catch (Exception $e) {
					$errors['zone_id'] = sb__('Seems like we are lacking access to zone (check permissions) or the zone does not exist.');
				}
			}
		} else {
			/*
			$string = $auth_type == 'api_token' ? 'token' : 'credentials';
			$errors[] = sprintf(sb__('Cannot validate zone due to insufficient/invalid API %s'), $string);
			*/
		}

		if ( empty($errors) ) {
			wp_send_json_success();
		} else {
			wp_send_json_error([
				'errors' => $errors,
				'error_html' => $this->generate_error_html($errors),
			]);
		}
	}

	/**
	 * Generate markup for form validation errors.
	 *
	 * @param $errors
	 *
	 * @return string
	 */
	private function generate_error_html($errors) {
		$errors = array_map(function ($error) {
			return rtrim(trim($error), '.');
		}, $errors);
		return '<br><strong>' . sb__('Validation errors:') . '</strong><ul><li>- ' . implode('</li><li>- ', $errors) . '</li></ul>';
	}

	/**
	 * Purge all cache in Cloudflare cache.
	 */
	public function purge_network_cache_callback() {
		check_ajax_referer(sb_get_ajax_nonce_key(), 'security');
		sb_require_superadmin();

		$failed = [];
		sb_iterate_sites(function($site) use (&$failed) {

			// Switch context to blog
			if ( sb_cf()->cf_switch_to_blog($site->blog_id) === false ) {
				$failed[] = [
					'blog_id' => $site->blog_id,
					'reason' => false,
				];
				return;
			}

			// Check if we should use Cloudflare
			if ( ! sb_cf()->should_user_cf_feature() ) {
				$failed[] = [
					'blog_id' => $site->blog_id,
					'reason' => sb__('Cloudflare feature not active/available'),
				];
				return;
			}

			// Purge all cache
			if ( ! sb_cf()->purge_all() ) {
				$failed[] = [
					'blog_id' => $site->blog_id,
					'reason' => false,
				];
			}

		});

		$failed_count = count($failed);
		$all_failed   = $failed_count == sb_count_sites();

		if ( $all_failed ) {
			wp_send_json_error([
				'message' => sb__('Could not purge cache on any sites.'),
			]);
		} else {
			if ( $failed_count > 0 ) {
				wp_send_json_success([
					'type' => 'warning',
					'title' => sb__('Could not clear cache on all sites'),
					'markup' => $this->purge_network_cache_failed_sites($failed),
				]);
			} else {
				wp_send_json_success([
					'type' => 'success',
					'title' => sb__('Cache cleared for all sites'),
				]);
			}
		}
	}

	/**
	 * Generate markup for user feedback after purging cache on all sites in multisite-network.
	 *
	 * @param $failed
	 *
	 * @return string
	 */
	private function purge_network_cache_failed_sites($failed) {
		$markup = '<strong>' . sb__('The cache was cleared on all sites except the following:') . '</strong>';
		$markup .= create_li_tags_from_array($failed, function ($item) {
			return sb_get_blog_name($item['blog_id']) . ( $item['reason'] ? ' (' . $item['reason'] . ')' : '' );
		});
		return $markup;
	}

	/**
	 * Purge all cache in Cloudflare cache.
	 */
	public function purge_all_cache_callback() {
		check_ajax_referer(sb_get_ajax_nonce_key(), 'security');
		sb_ajax_user_allowed();
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
		check_ajax_referer(sb_get_ajax_nonce_key(), 'security');
		sb_ajax_user_allowed();
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
