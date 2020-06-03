<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class CF_Cache_Admin_Controls_Ajax
 *
 * This class handles the CF cache related AJAX-calls.
 */
class CF_Cache_Admin_Controls_Ajax {

	/**
	 * CF_Cache_Admin_Controls constructor.
	 */
	function __construct() {
		$this->add_ajax_handling();
	}

	/**
	 * Add AJAX handling.
	 */
	private function add_ajax_handling() {
		add_action( 'wp_ajax_servebolt_lookup_zones', [ $this, 'lookup_zones_callback' ] );
		add_action( 'wp_ajax_servebolt_lookup_zone', [ $this, 'lookup_zone_callback' ] );
		add_action( 'wp_ajax_servebolt_validate_cf_settings_form', [ $this, 'validate_cf_settings_form_callback' ] );
		add_action( 'wp_ajax_servebolt_delete_cache_purge_queue_items', [ $this, 'delete_cache_purge_queue_items_callback' ] );
		add_action( 'wp_ajax_servebolt_purge_all_cache', [ $this, 'purge_all_cache_callback' ] );
		add_action( 'wp_ajax_servebolt_purge_url_cache', [ $this, 'purge_url_cache_callback' ] );
		add_action( 'wp_ajax_servebolt_purge_post_cache', [ $this, 'purge_post_cache_callback' ] );

		if ( is_multisite() ) {
			add_action( 'wp_ajax_servebolt_purge_network_cache', [ $this, 'purge_network_cache_callback' ] );
		}
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
			sb_cf_cache()->cf()->set_credentials($auth_type, $credentials);
			$zones = sb_cf_cache()->cf()->list_zones();
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
					sb_cf_cache()->cf()->set_credentials('api_token', compact('api_token'));
					break;
				case 'api_key':
					sb_cf_cache()->cf()->set_credentials('api_key', compact('email', 'api_key'));
					break;
				default:
					throw new Exception;
			}
			$zone = sb_cf_cache()->get_zone_by_id($zone_id);
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
					sb_cf_cache()->cf()->set_credentials('api_token', compact('api_token'));
					try {
						if ( ! sb_cf_cache()->cf()->verify_token() ) {
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
					sb_cf_cache()->cf()->set_credentials('api_key', compact('email', 'api_key'));
					try {
						if ( ! sb_cf_cache()->cf()->verify_user() ) {
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
					if ( ! $zone_id = sb_cf_cache()->cf()->get_zone_by_id($zone_id) ) {
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
				'error_html' => $this->generate_form_error_html($errors),
			]);
		}
	}

	/**
	 * Delete items from cache purge queue.
	 */
	public function delete_cache_purge_queue_items_callback() {
		check_ajax_referer( sb_get_ajax_nonce_key(), 'security' );
		sb_ajax_user_allowed();

		$items_to_remove = sb_array_get('items_to_remove', $_POST);

		// Clear all queue items
		if ( $items_to_remove === 'all' ) {
			sb_cf_cache()->set_items_to_purge([]);
			wp_send_json_success();
		}

		// Invalid queue items
		if ( ! is_array($items_to_remove) || ! $items_to_remove || empty($items_to_remove) ) {
			wp_send_json_error();
		}

		// Prevent invalid items
		$items_to_remove = array_filter($items_to_remove, function ($item) {
			return is_numeric($item) || is_string($item);
		});

		$current_items = sb_cf_cache()->get_items_to_purge();
		if ( ! is_array($current_items) ) $current_items = [];

		// Filter out items
		$updated_items = array_filter($current_items, function($item) use ($items_to_remove) {
			return ! in_array($item, $items_to_remove);
		});
		sb_cf_cache()->set_items_to_purge($updated_items);
		wp_send_json_success();
	}

	/**
	 * Purge all cache in Cloudflare cache.
	 */
	public function purge_all_cache_callback() {
		check_ajax_referer(sb_get_ajax_nonce_key(), 'security');
		sb_ajax_user_allowed();

		$cron_purge_is_active = sb_cf_cache()->cron_purge_is_active();
		if ( ! sb_cf_cache()->cf_cache_feature_available() ) {
			wp_send_json_error( [ 'message' => 'Cloudflare cache feature is not active so we could not purge cache. Make sure you have added Cloudflare API credentials and selected zone.' ] );
		} elseif ( $cron_purge_is_active && sb_cf_cache()->has_purge_all_request_in_queue() ) {
			wp_send_json_error( [
				'type'    => 'warning',
				'title'   => 'Woops!',
				'message' => 'A purge all-request is already queued. Either delete it or wait until it is executed.',
			] );
		} elseif ( sb_cf_cache()->purge_all() ) {
			wp_send_json_success( [
				'title'   => $cron_purge_is_active ? 'Just a moment' : false,
				'message' => $cron_purge_is_active ? 'A purge all-request was added to the queue and will be executed shortly.' : 'All cache was purged.',
			] );
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * Purge specific URL in Cloudflare cache.
	 */
	public function purge_url_cache_callback() {
		check_ajax_referer(sb_get_ajax_nonce_key(), 'security');
		sb_ajax_user_allowed();

		$url = (string) $_POST['url'];
		if ( ! sb_cf_cache()->cf_cache_feature_available() ) {
			wp_send_json_error( [ 'message' => 'Cloudflare cache feature is not active so we could not purge cache. Make sure you have added Cloudflare API credentials and selected zone.' ] );
		} elseif ( ! $url || empty($url) ) {
			wp_send_json_error( [ 'message' => 'Please specify the URL you would like to purge cache for.' ] );
		} elseif ( strpos($url, sb_purge_all_item_name()) !== false ) {
			wp_send_json_error( [ 'message' => sprintf( 'This string "%s" is used by the system and is not allowed for URL-based cache purge.', sb_purge_all_item_name()) ] );
		} elseif ( sb_cf_cache()->purge_by_url($url) ) {
			$cron_purge_is_active = sb_cf_cache()->cron_purge_is_active();
			wp_send_json_success( [
				'title'   => $cron_purge_is_active ? 'Just a moment' : false,
				'message' => $cron_purge_is_active ? sprintf('A cache purge-request for the URL "%s" was added to the queue and will be executed shortly.', $url) : sprintf('Cache was purged for URL "%s".', $url),
			] );
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * Purge specific post in Cloudflare cache.
	 */
	public function purge_post_cache_callback() {
		check_ajax_referer(sb_get_ajax_nonce_key(), 'security');
		sb_ajax_user_allowed();
		$post_id = intval($_POST['post_id']);
		if ( ! $post_id || empty($post_id) ) {
			wp_send_json_error( [ 'message' => 'Please specify the post you would like to purge cache for.' ] );
		} elseif ( strpos($post_id, sb_purge_all_item_name()) !== false ) {
			wp_send_json_error( [ 'message' => sprintf( 'This string "%s" is used by the system and is not allowed for post-based cache purge.', sb_purge_all_item_name()) ] );
		} elseif ( ! sb_post_exists($post_id) ) {
			wp_send_json_error( [ 'message' => 'The specified post does not exist.' ] );
		} elseif ( ! sb_cf_cache()->cf_cache_feature_available() ) {
			wp_send_json_error( [ 'message' => 'Cloudflare cache feature is not active so we could not purge cache. Make sure you have added Cloudflare API credentials and selected zone.' ] );
		} elseif ( sb_cf_cache()->purge_by_post($post_id) ) {
			$cron_purge_is_active = sb_cf_cache()->cron_purge_is_active();
			wp_send_json_success( [
				'title'   => $cron_purge_is_active ? 'Just a moment' : false,
				'message' => $cron_purge_is_active ? sprintf('A cache purge-request for the post "%s" was added to the queue and will be executed shortly.', get_the_title($post_id)) : sprintf('Cache was purged for the post post "%s".', get_the_title($post_id)),
			] );
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * Purge all Cloudflare cache in all sites in multisite-network.
	 */
	public function purge_network_cache_callback() {
		check_ajax_referer(sb_get_ajax_nonce_key(), 'security');
		sb_require_superadmin();

		$failed_purge_attempts = [];
		$queue_based_cache_purge_sites = [];
		sb_iterate_sites(function($site) use (&$failed_purge_attempts, &$queue_based_cache_purge_sites) {

			// Switch context to blog
			if ( sb_cf_cache()->cf_switch_to_blog($site->blog_id) === false ) {
				$failed_purge_attempts[] = [
					'blog_id' => $site->blog_id,
					'reason'  => false,
				];
				return;
			}

			// Skip if CF cache purge feature is not active
			if ( ! sb_cf_cache()->cf_is_active() ) {
				return;
			}

			// Check if the Cloudflare cache purg feature is avavilable
			if ( ! sb_cf_cache()->cf_cache_feature_available() ) {
				$failed_purge_attempts[] = [
					'blog_id' => $site->blog_id,
					'reason'  => sb__('Cloudflare feature not available'),
				];
				return;
			}

			// Flag that current site uses queue based cache purge
			if ( sb_cf_cache()->cron_purge_is_active() ) {
				$queue_based_cache_purge_sites[] = $site->blog_id;
			}

			// Check if we already added a purge all-request to queue (if queue based cache purge is used)
			if ( sb_cf_cache()->cron_purge_is_active() && sb_cf_cache()->has_purge_all_request_in_queue() ) {
				return;
			}

			// Purge all cache
			if ( ! sb_cf_cache()->purge_all() ) {
				$failed_purge_attempts[] = [
					'blog_id' => $site->blog_id,
					'reason'  => false,
				];
			}

		});

		$queue_based_cache_purge_sites_count = count($queue_based_cache_purge_sites);
		$all_sites_use_queue_based_cache_purge = $queue_based_cache_purge_sites_count == sb_count_sites();
		$some_sites_has_queue_purge_active = $queue_based_cache_purge_sites_count > 0;

		$failed_purge_attempt_count = count($failed_purge_attempts);
		$all_failed = $failed_purge_attempt_count == sb_count_sites();

		if ( $all_failed ) {
			wp_send_json_error( [
				'message' => sb__('Could not purge cache on any sites.'),
			] );
		} else {
			if ( $failed_purge_attempt_count > 0 ) {
				wp_send_json_success([
					'type'   => 'warning',
					'title'  => sb__('Could not clear cache on all sites'),
					'markup' => $this->purge_network_cache_failed_sites($failed_purge_attempts),
				]);
			} else {

				if ( $all_sites_use_queue_based_cache_purge ) {
					$feedback = sb__('Cache will be cleared for all sites in a moment.');
				} elseif ( $some_sites_has_queue_purge_active ) {
					$feedback = sb__('Cache cleared for all sites, but note that some sites are using queue based cache purging and will be purged in a moment.');
				} else {
					$feedback = sb__('Cache cleared for all sites');
				}

				wp_send_json_success( [
					'type'   => 'success',
					'markup' => $feedback,
				] );
			}
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
	 * Generate markup for form validation errors.
	 *
	 * @param $errors
	 *
	 * @return string
	 */
	private function generate_form_error_html($errors) {
		$errors = array_map(function ($error) {
			return rtrim(trim($error), '.');
		}, $errors);
		return '<br><strong>' . sb__('Validation errors:') . '</strong><ul><li>- ' . implode('</li><li>- ', $errors) . '</li></ul>';
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

}
