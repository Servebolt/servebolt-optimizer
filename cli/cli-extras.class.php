<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_CLI_Extras
 * @package Servebolt
 *
 * Additional methods for CLI-class.
 */
abstract class Servebolt_CLI_Extras {

	/**
	 * Store Cloudflare zones for cache purposes.
	 *
	 * @var null
	 */
	protected $zones = null;

	/**
	 * Get Cloudflare zones.
	 *
	 * @return array|null
	 */
	protected function get_zones() {
		if ( is_null($this->zones) ) {
			$this->zones = sb_cf()->list_zones();
		}
		return $this->zones;
	}

	/**
	 * List Cloudflare zones with/without numbering.
	 *
	 * @param bool $include_numbers
	 */
	protected function list_zones($include_numbers = false) {
		$zones = $this->get_zones();
		if ( ! $zones || empty($zones) ) {
			WP_CLI::error(sb__('Could not retrieve any available zones. Make sure you have configured the Cloudflare API credentials and set an active zone.'));
		}
		WP_CLI::line(sb__('The following zones are available:'));
		foreach ($zones as $i => $zone ) {
			if ( $include_numbers === true ) {
				WP_CLI::line(sprintf('[%s] %s (%s)', $i+1, $zone->name, $zone->id));
			} else {
				WP_CLI::line(sprintf('%s (%s)', $zone->name, $zone->id));
			}
		}
	}

	/**
	 * Store zone without listing available zones.
	 *
	 * @param $zone_id
	 */
	protected function store_zone_direct($zone_id) {
		$zone_object = sb_cf()->get_zone_by_id($zone_id);
		$zone_identification = $zone_object ? $zone_object->name . ' (' . $zone_object->id . ')' : $zone_id;
		if ( ! $zone_object ) {
			WP_CLI::error_multi_line(['Could not find zone with the specified ID in Cloudflare. This might indicate:', '- the API connection is not working', '- that the zone does not exists', '- that we lack access to the zone']);
			WP_CLI::confirm(sb__('Do you still wish to set the zone?'));
		}
		sb_cf()->store_active_zone_id($zone_id);
		WP_CLI::success(sprintf(sb__('Successfully selected zone %s'), $zone_identification));
	}

	/**
	 * Display Cloudflare config for a given site.
	 *
	 * @param bool $blog_id
	 *
	 * @return array
	 */
	protected function cf_get_config($blog_id = false) {
		$auth_type = sb_cf()->get_authentication_type($blog_id);
		$is_cron_purge = sb_cf()->cron_purge_is_active(true, $blog_id);

		if ( $blog_id ) {
			$arr = ['Blog' => $blog_id];
		} else {
			$arr = [];
		}

		$arr = array_merge($arr, [
			'Status'     => sb_cf()->cf_is_active($blog_id) ? 'Active' : 'Inactive',
			'Zone Id'    => sb_cf()->get_active_zone_id($blog_id),
			'Purge type' => $is_cron_purge ? 'Via cron' : 'Immediate purge',
		]);

		if ($is_cron_purge) {
			$max_items = 50;
			$items_to_purge = sb_cf()->get_items_to_purge($max_items, $blog_id);
			$items_to_purge_count = sb_cf()->count_items_to_purge();
			$arr['Purge queue (cron)'] = $items_to_purge ? sb_format_array_to_csv($items_to_purge) . ( $items_to_purge_count > $max_items ? '...' : '') : null;
		}

		$arr['API authentication type'] = $auth_type;
		switch ($auth_type) {
			case 'apiKey':
			case 'api_key':
				$arr['API key/token'] = sb_cf()->get_credential('api_key', $blog_id);
				$arr['email'] = sb_cf()->get_credential('email', $blog_id);
				break;
			case 'apiToken':
			case 'api_token':
				$arr['API key/token'] = sb_cf()->get_credential('api_token', $blog_id);
				break;
		}
		return $arr;
	}

	/**
	 * Ensure all CF config items have the same column structure.
	 *
	 * @param $array
	 *
	 * @return array
	 */
	protected function cf_ensure_config_array_integrity($array) {

		$most_column_item = [];
		foreach($array as $item) {
			if ( count($item) > count($most_column_item) ) {
				$most_column_item = array_keys($item);
			}
		}

		$new_array = [];
		foreach($array as $item) {
			$new_item = [];
			foreach ($most_column_item as $column) {
				$new_item[$column] = sb_array_get($column, $item, null);
			}
			$new_array[] = $new_item;
		}

		return $new_array;
	}

	/**
	 * Schedule / Un-schedule the cron to execute queue-based cache purge.
	 *
	 * @param $state
	 * @param $assoc_args
	 */
	protected function cf_cron_toggle($state, $assoc_args) {
		if ( $this->affect_all_sites( $assoc_args ) ) {
			$this->iterate_sites(function ( $site ) use ($state) {
				$this->cf_cron_control($state, $site->blog_id);
			});
		} else {
			$this->cf_cron_control($state);
		}
	}

	/**
	 * Check if Cloudflare feature is active/inactive.
	 *
	 * @param bool $blog_id
	 */
	protected function cf_status($blog_id = false) {

		// Switch context to blog
		if ( $this->cf_switch_to_blog($blog_id) === false ) {

			// Could not switch to blog
			WP_CLI::error(sprintf(sb__('Could not get Cloudflare feature status on site %s'), get_site_url($blog_id)), false);
			return;

		}

		$current_state = sb_cf()->cf_is_active();
		$cron_state_string = sb_boolean_to_state_string($current_state);
		if ( $blog_id ) {
			WP_CLI::line(sprintf(sb__('Cloudflare feature is %s for site %s'), $cron_state_string, get_site_url($blog_id)));
		} else {
			WP_CLI::line(sprintf(sb__('Cloudflare feature is %s'), $cron_state_string));
		}
	}

	/**
	 * Check if Cloudflare cron cache purge feature is active/inactive.
	 *
	 * @param bool $blog_id
	 */
	protected function cf_cron_status($blog_id = false) {

		// Switch context to blog
		if ( $this->cf_switch_to_blog($blog_id) === false ) {

			// Could not switch to blog
			WP_CLI::error(sprintf(sb__('Could not get cron status on site %s'), get_site_url($blog_id)), false);
			return;

		}

		$current_state = sb_cf()->cron_purge_is_active();
		$cron_state_string = sb_boolean_to_state_string($current_state);
		if ( $blog_id ) {
			WP_CLI::line(sprintf(sb__('Cron cache purge is %s for site %s'), $cron_state_string, get_site_url($blog_id)));
		} else {
			WP_CLI::line(sprintf(sb__('Cron cache purge is %s'), $cron_state_string));
		}
	}

	/**
	 * Enable/disable Nginx cache headers.
	 *
	 * @param bool $cron_state
	 * @param bool $blog_id
	 */
	protected function cf_cron_control(bool $cron_state, $blog_id = false) {

		$cron_state_string = sb_boolean_to_state_string($cron_state);

		// Switch context to blog
		if ( $this->cf_switch_to_blog($blog_id) === false ) {

			// Could not switch to blog
			WP_CLI::error(sprintf(sb__('Could not set cache purge cron as %s on site %s'), $cron_state_string, get_site_url($blog_id)), false);
			return;

		}

		$current_state = sb_cf()->cron_purge_is_active();
		if ( $current_state === $cron_state ) {
			if ( is_numeric($blog_id) ) {
				WP_CLI::line(sprintf(sb__('Cloudflare cache purge cron is already %s on site %s'), $cron_state_string, get_site_url($blog_id)));
			} else {
				WP_CLI::line(sprintf(sb__('Cloudflare cache purge cron is already %s'), $cron_state_string));
			}
		} else {
			sb_cf()->cf_toggle_cron_active($cron_state);
			if ( is_numeric($blog_id) ) {
				WP_CLI::success(sprintf(sb__('Cloudflare cache purge cron is now %s on site %s'), $cron_state_string, get_site_url($blog_id)));
			} else {
				WP_CLI::success(sprintf(sb__('Cloudflare cache purge cron is now %s'), $cron_state_string));
			}
		}

		// Add / remove task from schedule
		( Servebolt_CF_Cron_Handle::get_instance() )->update_cron_state($blog_id);
	}

	/**
	 * Prepare credentials for storage.
	 *
	 * @param $auth_type
	 * @param $assoc_args
	 *
	 * @return object
	 */
	protected function cf_prepare_credentials($auth_type, $assoc_args) {
		switch ( $auth_type ) {
			case 'key':
				$email = sb_array_get('email', $assoc_args);
				$api_key = sb_array_get('api-key', $assoc_args);
				if ( ! $email || empty($email) || ! $api_key || empty($api_key) ) {
					WP_CLI::error(sb__('Please specify API key and email.'));
				}
				$type         = 'api_key';
				$verbose_type = 'API key';
				$credentials = compact('email', 'api_key');
				$verbose_credentials = implode(' / ', $credentials);
				return (object) compact('type', 'verbose_type', 'credentials', 'verbose_credentials');
				break;
			case 'token':
				$token = sb_array_get('api-token', $assoc_args);
				if ( ! $token || empty($token) ) {
					WP_CLI::error(sb__('Please specify a token.'));
				}
				$type         = 'api_token';
				$verbose_type = 'API token';
				$credentials  = compact('token');
				$verbose_credentials = $token;
				return (object) compact('type', 'verbose_type', 'credentials', 'verbose_credentials');
				break;
		}

		WP_CLI::error(sb__('Could not set credentials. Please check the arguments and try again.'));
	}

	/**
	 * Clear active Cloudflare zone.
	 *
	 * @param bool $blog_id
	 */
	protected function cf_clear_zone($blog_id = false) {
		sb_cf()->clear_active_zone($blog_id);
		if ( $blog_id ) {
			WP_CLI::success(sprintf(sb__('Successfully cleared zone on site %s.'), get_site_url($blog_id)));
		} else {
			WP_CLI::success(sb__('Successfully cleared zone.'));
		}
	}

	/**
	 * Clear Cloudflare API credentials.
	 *
	 * @param bool $blog_id
	 */
	protected function cf_clear_credentials($blog_id = false) {
		sb_cf()->clear_credentials($blog_id);
		if ( $blog_id ) {
			WP_CLI::success(sprintf(sb__('Successfully cleared API credentials on site %s.'), get_site_url($blog_id)));
		} else {
			WP_CLI::success(sb__('Successfully cleared API credentials.'));
		}
	}

	/**
	 * Test Cloudflare API connection on specified blog.
	 *
	 * @param bool $blog_id
	 */
	protected function cf_test_api_connection($blog_id = false) {

		// Switch context to blog
		if ( $this->cf_switch_to_blog($blog_id) === false ) {

			// Could not switch to blog
			WP_CLI::error(sprintf(sb__('Could not test Cloudflare API connection on site %s'), get_site_url($blog_id)), false);
			return;

		}

		if ( sb_cf()->test_api_connection() ) {
			if ( $blog_id ) {
				WP_CLI::success(sprintf(sb__('API connection passed on site %s'), get_site_url($blog_id)));
			} else {
				WP_CLI::success(sb__('API connection passed!'));
			}

		} else {
			if ( $blog_id ) {
				WP_CLI::error(sprintf(sb__('Could not communicate with the API on site %s. Please check that API credentials are configured correctly and that we have the right permissions (%s).'), get_site_url($blog_id), sb_cf()->api_permissions_needed()), false);
			} else {
				WP_CLI::error(sprintf(sb__('Could not communicate with the API. Please check that API credentials are configured correctly and that we have the right permissions (%s).'), sb_cf()->api_permissions_needed()), false);
			}
		}
	}

	/**
	 * Set credentials on specified blog.
	 *
	 * @param $credential_data
	 * @param bool $blog_id
	 */
	protected function cf_set_credentials($credential_data, $blog_id = false) {

		// Switch context to blog
		if ( $this->cf_switch_to_blog($blog_id) === false ) {

			// Could not switch to blog
			WP_CLI::error(sprintf(sb__('Could not set Cloudflare credentials on site %s'), get_site_url($blog_id)), false);
			return;

		}

		if ( sb_cf()->store_credentials($credential_data->credentials, $credential_data->type) ) {
			if ( sb_cf()->test_api_connection() ) {
				if ( $blog_id ) {
					WP_CLI::success(sprintf(sb__('Cloudflare credentials set on site %s using %s: %s'), get_site_url($blog_id), $credential_data->verbose_type, $credential_data->verbose_credentials));
					WP_CLI::success(sprintf(sb__('API connection test passed on site %s'), get_site_url($blog_id)));
				} else {
					WP_CLI::success(sprintf(sb__('Cloudflare credentials set using %s: %s'), $credential_data->verbose_type, $credential_data->verbose_credentials));
					WP_CLI::success(sb__('API connection test passed!'));
				}
			} else {
				if ( $blog_id ) {
					WP_CLI::warning(sprintf(sb__('Credentials were stored on site %s but the API connection test failed. Please check that the credentials are correct and have the correct permissions (%s).'), get_site_url($blog_id), sb_cf()->api_permissions_needed()), false);
				} else {
					WP_CLI::warning(sprintf(sb__('Credentials were stored but the API connection test failed. Please check that the credentials are correct and have the correct permissions (%s).'), sb_cf()->api_permissions_needed()), false);
				}
			}
		} else {
			if ( $blog_id ) {
				WP_CLI::error(sprintf(sb__('Could not set Cloudflare credentials on site %s using %s: %s'), get_site_url($blog_id), $credential_data->verbose_type, $credential_data->verbose_credentials), false);
			} else {
				WP_CLI::error(sprintf(sb__('Could not set Cloudflare credentials using %s: %s'), $credential_data->verbose_type, $credential_data->verbose_credentials), false);
			}
		}
	}

	/**
	 * Switch API credentials and zone to the specified blog.
	 *
	 * @param bool $blog_id
	 *
	 * @return bool|null
	 */
	protected function cf_switch_to_blog($blog_id = false) {
		if ( $blog_id === false ) {
			return true;
		}
		if ( is_numeric($blog_id) ) {
			sb_cf()->cf_init($blog_id);
			return true;
		}
		return null;
	}

	/**
	 * Switch API credentials and zone back to the current blog.
	 */
	protected function cf_restore_current_blog() {
		return sb_cf()->cf_init(false);
	}

	/**
	 * Check if we should affect all sites in multisite-network.
	 *
	 * @param $assoc_args
	 *
	 * @return bool
	 */
	protected function affect_all_sites($assoc_args) {
		return is_multisite() && array_key_exists('all', $assoc_args);
	}

	/**
	 * Execute cache purge queue clearing.
	 *
	 * @param bool $blog_id
	 */
	protected function cf_clear_cache_purge_queue($blog_id = false) {

		// Switch context to blog
		if ( $this->cf_switch_to_blog($blog_id) === false ) {

			// Could not switch to blog
			WP_CLI::error(sprintf(sb__('Could not execute cache purge on site %s'), get_site_url($blog_id)), false);
			return;

		}

		// Check if cron purge is active
		if ( ! sb_cf()->cron_purge_is_active() ) {
			if ( $blog_id ) {
				WP_CLI::warning(sprintf(sb__('Note: cache purge via cron is not active on site %s.'), get_site_url($blog_id)));
			} else {
				WP_CLI::warning(sb__('Note: cache purge via cron is not active.'));
			}
		}

		// Check if we got items to purge, if so we purge them
		if ( sb_cf()->has_items_to_purge() ) {
			sb_cf()->clear_items_to_purge();
			if ( $blog_id ) {
				WP_CLI::success(sprintf(sb__('Cache purge queue cleared on site %s.'), get_site_url($blog_id)));
			} else {
				WP_CLI::success(sb__('Cache purge queue cleared.'));
			}

		} else {
			if ( $blog_id ) {
				WP_CLI::warning(sprintf(sb__('Cache purge queue already empty on site %s.'), get_site_url($blog_id)));
			} else {
				WP_CLI::warning(sb__('Cache purge queue already empty.'));
			}
		}

	}

	/**
	 * Purge all cache for site.
	 *
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	protected function cf_purge_all($blog_id = false) {

		// Switch context to blog
		if ( $this->cf_switch_to_blog($blog_id) === false ) return false;

		// Get zone name
		$current_zone_id = sb_cf()->get_active_zone_id();
		$current_zone = sb_cf()->get_zone_by_id($current_zone_id);

		// Tell the user what we're doing
		if ( $blog_id ) {
			WP_CLI::line(sprintf(sb__('Purging all cache for zone %s on site %s'), $current_zone->name, get_site_url($blog_id)));
		} else {
			WP_CLI::line(sprintf(sb__('Purging all cache for zone %s'), $current_zone->name));
		}

		// Purge all cache
		return sb_cf()->purge_all();
	}

	/**
	 * Display Nginx status - which post types have cache active.
	 *
	 * @param $assoc_args
	 * @param bool $display_cache_state
	 */
	protected function get_nginx_fpc_status($assoc_args, $display_cache_state = true) {
		if ( $this->affect_all_sites($assoc_args) ) {
			$sites = get_sites();
			$sites_status = [];
			foreach ( $sites as $site ) {
				$status = sb_nginx_fpc()->fpc_is_active($site->blog_id) ? 'Active' : 'Inactive';
				$post_types = sb_nginx_fpc()->get_post_types_to_cache(true, true, $site->blog_id);
				$enabled_post_types_string = $this->nginx_get_active_post_types_string($post_types);
				$sites_status[] = [
					'URL'               => get_site_url($site->blog_id),
					'STATUS'            => $status,
					'ACTIVE_POST_TYPES' => $enabled_post_types_string,
				];
			}
			WP_CLI\Utils\format_items( 'table', $sites_status , array_keys(current($sites_status)));
		} else {
			$status = sb_boolean_to_state_string( sb_nginx_fpc()->fpc_is_active() );
			$post_types = sb_nginx_fpc()->get_post_types_to_cache();
			$enabled_post_types_string = $this->nginx_get_active_post_types_string($post_types);
			if ( $display_cache_state ) {
				WP_CLI::line(sprintf(sb__( 'Servebolt Full Page Cache cache is %s' ), $status ));
			}
			WP_CLI::line(sprintf(sb__( 'Cache enabled for post type(s): %s' ), $enabled_post_types_string ));
		}
	}

	/**
	 * Activate/deactivate Cloudflare feature.
	 *
	 * @param bool $state
	 * @param bool $blog_id
	 */
	protected function cf_toggle_active(bool $state, $blog_id = false) {
		$state_string = sb_boolean_to_state_string($state);
		if ( sb_cf()->cf_toggle_active($state, $blog_id) ) {
			if ( $blog_id ) {
				WP_CLI::success(sprintf(sb__('Cloudflare feature was set to %s on site %s'), $state_string, get_site_url($blog_id)));
			} else {
				WP_CLI::success(sprintf(sb__('Cloudflare feature was set to %s'), $state_string));
			}
		} else {
			if ( $blog_id ) {
				WP_CLI::error(sprintf(sb__('Could not set Cloudflare feature to %s on site %s'), $state_string, get_site_url($blog_id)), false);
			} else {
				WP_CLI::error(sprintf(sb__('Could not set Cloudflare feature to %s'), $state_string), false);
			}
		}
	}

	/**
	 * Get the string displaying which post types are active in regards to Nginx caching.
	 *
	 * @param $post_types
	 *
	 * @return string|void
	 */
	private function nginx_get_active_post_types_string($post_types) {

		// Cache default post types
		if ( ! is_array($post_types) || empty($post_types) ) {
			return sprintf( sb__( 'Default [%s]' ), sb_nginx_fpc()->get_default_post_types_to_cache( 'csv' ) );
		}

		// Cache all post types
		if ( array_key_exists('all', $post_types) ) {
			return sb__( 'All' );
		}
		return sb_format_array_to_csv($post_types);
	}

	/**
	 * Toggle cachce active/inactive for site.
	 *
	 * @param $new_cache_state
	 * @param null $blog_id
	 */
	private function nginx_toggle_cache_for_blog($new_cache_state, $blog_id = null) {
		$url = get_site_url($blog_id);
		$cache_active_string = sb_boolean_to_state_string($new_cache_state);
		if ( $new_cache_state === sb_nginx_fpc()->fpc_is_active($blog_id) ) {
			WP_CLI::warning(sprintf( sb__( 'Full Page Cache already %s on %s' ), $cache_active_string, $url ));
		} else {
			sb_nginx_fpc()->fpc_toggle_active($new_cache_state, $blog_id);
			WP_CLI::success(sprintf( sb__( 'Full Page Cache %s on %s' ), $cache_active_string, $url ));
		}
	}

	/**
	 * Prepare post types from argument if present. To be used when toggling Nginx FPC active/inactive.
	 *
	 * @param $args
	 *
	 * @return array|bool
	 */
	private function nginx_prepare_post_type_argument($args) {
		if ( array_key_exists('post-types', $args) ) {
			$post_types = sb_format_comma_string($args['post-types']);
			$post_types = array_filter($post_types, function ($post_type) {
				return post_type_exists($post_type);
			});
			if ( ! empty($post_types) ) {
				return $post_types;
			}
		}
		return false;
	}

	/**
	 * Enable/disable Nginx cache headers.
	 *
	 * @param bool $cache_active
	 * @param array $args
	 */
	protected function nginx_fpc_control($cache_active, $args = []){

		$affect_all_blogs    = array_key_exists('all', $args);
		$post_types          = $this->nginx_prepare_post_type_argument($args);
		$exclude_ids         = sb_array_get('exclude', $args);

		if ( is_multisite() && $affect_all_blogs ) {
			WP_CLI::line(sb__('Applying settings to all blogs'));
			foreach (get_sites() as $site) {
				$this->nginx_toggle_cache_for_blog($cache_active, $site->blog_id);
				if ( $post_types ) $this->nginx_set_post_types($post_types, $site->blog_id);
				if ( $exclude_ids ) $this->nginx_set_exclude_ids($exclude_ids, $site->blog_id);
			}
		} else {
			$this->nginx_toggle_cache_for_blog($cache_active);
			if ( $post_types ) $this->nginx_set_post_types($post_types);
			if ( $exclude_ids ) $this->nginx_set_exclude_ids($exclude_ids);
		}
	}

	/**
	 * Run function closure for each site in multisite-network.
	 *
	 * @param $function
	 *
	 * @return bool
	 */
	protected function iterate_sites($function) {
		$sites = apply_filters('sb_optimizer_site_iteration', get_sites(), $function);
		if ( is_array($sites) ) {
			foreach ($sites as $site) {
				$function($site);
			}
			return true;
		}
		return false;
	}

	/**
	 * Set post types to cache.
	 *
	 * @param $post_types
	 * @param bool $blog_id
	 */
	protected function nginx_set_post_types($post_types, $blog_id = false) {
		$all_post_types = $this->nginx_get_all_post_types();
		$all_post_type_keys = array_keys($all_post_types);
		$url = get_site_url($blog_id);
		$post_types = array_filter($post_types, function($post_type) use ($all_post_type_keys) {
			return in_array($post_type, $all_post_type_keys) || $post_type === 'all';
		});
		sb_nginx_fpc()->set_cacheable_post_types($post_types, $blog_id);
		WP_CLI::success(sprintf(sb__('Cache post type(s) set to %s on %s'), sb_format_array_to_csv($post_types), $url));
	}

	/**
	 * Get all post types to be used in Nginx FPC context.
	 *
	 * @return array|bool
	 */
	private function nginx_get_all_post_types() {
		$all_post_types = sb_nginx_fpc()->get_available_post_types_to_cache(false);
		if ( is_array($all_post_types) ) {
			return array_map(function($post_type) {
				return isset($post_type->name) ? $post_type->name : $post_type;
			}, $all_post_types);
		}
		return false;
	}

	/**
	 * Set posts to be excluded from the Nginx FPC.
	 *
	 * @param $ids_to_exclude
	 */
	protected function nginx_set_exclude_ids($ids_to_exclude) {
		if ( ! is_array($ids_to_exclude) ) {
			$ids_to_exclude = sb_format_comma_string($ids_to_exclude);
		}
		$already_excluded = sb_nginx_fpc()->get_ids_to_exclude_from_cache();

		if ( empty($ids_to_exclude) ) {
			WP_CLI::warning(sb__('No ids were specified.'));
			return;
		}

		$already_added = [];
		$was_excluded = [];
		$invalid_id = [];
		foreach ($ids_to_exclude as $id) {
			if ( get_post_status( $id ) === false ) {
				$invalid_id[] = $id;
			} elseif ( ! in_array($id, $already_excluded)) {
				$was_excluded[] = $id;
				$already_excluded[] = $id;
			} else {
				$already_added[] = $id;
			}
		}
		sb_nginx_fpc()->set_ids_to_exclude_from_cache($already_excluded);

		if ( ! empty($already_added) ) {
			WP_CLI::warning(sprintf(sb__('The following ids were already excluded: %s'), sb_format_array_to_csv($already_added)));
		}

		if ( ! empty($invalid_id) ) {
			WP_CLI::warning(sprintf(sb__('The following ids were invalid: %s'), sb_format_array_to_csv($invalid_id)));
		}

		if ( ! empty($was_excluded) ) {
			WP_CLI::success(sprintf(sb__('Added %s to the list of excluded ids'), sb_format_array_to_csv($was_excluded)));
		} else {
			WP_CLI::warning(sb__('No action was made.'));
		}
	}

}
