<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_CLI_Cloudflare_Extra
 */
class Servebolt_CLI_Cloudflare_Extra extends Servebolt_CLI_Extras {

	/**
	 * Store Cloudflare zones for cache purposes.
	 *
	 * @var null
	 */
	protected $zones = null;

	/**
	 * Allowed authentication types for the Cloudflare API.
	 *
	 * @var array
	 */
	private $allowed_auth_types = ['token' => 'API token', 'key' => 'API keys'];

	/**
	 * Check if authentication type for the Cloudflare API is valid or not.
	 *
	 * @param $auth_type
	 * @param bool $strict
	 * @param bool $return_value
	 *
	 * @return bool|string
	 */
	private function auth_type_valid($auth_type, $strict = true, $return_value = false) {

		if ( $strict ) {
			if ( in_array($auth_type, array_keys($this->allowed_auth_types), true) ) {
				return ( $return_value ? $auth_type : true );
			}
			return false;
		}

		$auth_type = mb_strtolower($auth_type);
		$values = array_map(function ($item) {
			return mb_strtolower($item);
		}, $this->allowed_auth_types);
		$keys = array_map(function ($item) {
			return mb_strtolower($item);
		}, array_keys($this->allowed_auth_types));

		if ( in_array($auth_type, $values, true) ) {
			$value = array_flip($values)[$auth_type];
			return ( $return_value ? $value : true );
		}
		if ( in_array($auth_type, $keys, true) ) {
			return ( $return_value ? $auth_type : true );
		}
		return false;
	}

	/**
	 * Non-interactive setup for Cloudflare.
	 *
	 * @param $params
	 */
	protected function cf_setup_non_interactive($params) {

	}

	/**
	 * Interactive setup for Cloudflare.
	 *
	 * @param $params
	 */
	protected function cf_setup_interactive($params) {

		$api_connection_available = false;

		WP_CLI::line(sb__('Welcome!'));
		WP_CLI::line(sb__('This guide will set up the Cloudflare feature on your site.'));
		WP_CLI::line(sb__('This allows for automatic cache purge when the contents of your site changes.'));
		if ( $params['affect_all_sites'] ) {
			WP_CLI::warning(sb__('This will affect all your site in the multisite-network'));
		}
		WP_CLI::line();
		WP_CLI::confirm(sb__('Do you want to continue?'));

		WP_CLI::line(PHP_EOL . sb__('Okay, first we need to set up the API connection to Cloudflare.'));

		// Determine authentication type
		if ( $params['auth_type'] ) {
			if ( ! $this->auth_type_valid($params['auth_type']) ) {
				WP_CLI::error(sprintf(sb__('Invalid authentication type specified: %s'), $params['auth_type']));
			}
			WP_CLI::success(sprintf(sb__('Cloudflare API authentication type is already set to %s'), $params['auth_type']));
		} else {
			$params['auth_type'] = $this->collect_parameter(sb__('Select one of the options: '), sb__('Invalid selection, please try again.'), function($input) {
				if ( empty($input) ) return false;
				return $this->auth_type_valid($input, false, true);
			}, function () {
				WP_CLI::line(sb__('How will you authenticate with the Cloudflare API?'));
				foreach($this->allowed_auth_types as $key => $value) {
					WP_CLI::line(sprintf('[%s] %s', $key, $value));
				}
			});
		}

		// Collect credentials based on authentication type
		switch ($params['auth_type']) {
			case 'token':
			case 'API Token':
				if ( $params['api_token'] ) {
					WP_CLI::success(sprintf(sb__('API token is already set to "%s"'), $params['api_token']));
				} else {
					$params['api_token'] = $this->collect_parameter(sb__('Specify Cloudflare API Token: '), sb__('API cannot be empty.'));
				}
				if ( ! $params['disable_validation'] ) {
					// TODO: Test api connection
					if ( true ) {
						$api_connection_available = true;
					}
				}

				break;
			case 'key':
			case 'API Keys':
				if ( $params['email'] ) {
					WP_CLI::success(sprintf(sb__('E-mail is already set to %s'), $params['email']));
				} else {
					$params['email'] = $this->collect_parameter(sb__('Specify Cloudflare username (e-mail): '), sb__('E-mail cannot be empty.'));
				}

				if ( $params['api_key'] ) {
					WP_CLI::success(sprintf(sb__('API key is already set to %s'), $params['api_key']));
				} else {
					$params['email'] = $this->collect_parameter(sb__('Specify Cloudflare API key: '), sb__('API key cannot be empty.'));
				}

				if ( ! $params['disable_validation'] ) {
					// TODO: Test api connection
					if ( true ) {
						$api_connection_available = true;
					}
				}

				break;
			default:
				WP_CLI::error(sb__('Invalid authentication type, please try again.'));
				break;
		}

		if ( $api_connection_available ) {
			// TODO: Apply credentials to SB_CF-class
		}

		// Determine which zone to use
		if ( $api_connection_available && $zones = $this->get_zones() ) {

			sb_e('Choose from the list below or specify your own Zone ID:');
			$this->list_zones(true);

			$params['zone'] = $this->collect_parameter(sb__('Cloudflare Zone ID: '), sb__('Zone cannot be empty.'), function($input) use ($zones) {
				if ( empty($input) ) return false;
				foreach ( $zones as $i => $zone ) {
					if ( $i+1 == $input || $zone->id == $input || $zone->name == $input ) {
						return [
							'id'   => $zone->id,
							'name' => $zone->name,
						];
					}
				}
				return [
					'id'   => $input,
					'name' => false,
				];
			});

			if ( ! $params['disable_validation'] ) {
				// TODO: Test if zone is valid
			}

		} else {
			$zone_id = $this->collect_parameter(sb__('Cloudflare Zone ID: '), sb__('Zone cannot be empty.'));
			$params['zone'] = [
				'id'   => $zone_id,
				'name' => false,
			];
		}

		if ( $params['affect_all_sites'] ) {
			sb_iterate_sites(function($site) use ($params) {
				// TODO: Apply settings to site
			});
		} else {
			// TODO: Apply settings to site
		}

		print_r($params);

	}

	/**
	 * Collect parameter interactively via CLI prompt.
	 *
	 * @param $prompt_message
	 * @param $error_message
	 * @param bool $validation
	 * @param bool $before_input_prompt
	 * @param bool $quit_on_fail
	 *
	 * @return string
	 */
	protected function collect_parameter($prompt_message, $error_message, $validation = false, $before_input_prompt = false, $quit_on_fail = false) {

		// Determine validation
		$default_validation = function($input) {
			if ( empty($input) ) return false;
			return $input;
		};
		$validation = ( is_callable($validation) ? $validation : $default_validation );

		set_param:

		// Call before prompt-function
		if ( is_callable($before_input_prompt) ) {
			$before_input_prompt();
		}

		echo $prompt_message;
		$param = $this->user_input($validation);
		if ( ! $param ) {
			WP_CLI::error($error_message, $quit_on_fail);
			goto set_param;
		}
		return $param;
	}

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
	 * Execute cache purge queue clearing.
	 *
	 * @param bool $blog_id
	 */
	protected function cf_clear_cache_purge_queue($blog_id = false) {

		// Switch context to blog
		if ( sb_cf()->cf_switch_to_blog($blog_id) === false ) {

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
		if ( sb_cf()->cf_switch_to_blog($blog_id) === false ) return false;

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
	 * Activate/deactivate Cloudflare feature.
	 *
	 * @param bool $state
	 * @param bool $blog_id
	 */
	protected function cf_toggle_active(bool $state, $blog_id = false) {
		$state_string = sb_boolean_to_state_string($state);
		$is_active = sb_cf()->cf_is_active($blog_id);

		if ( $is_active === $state ) {
			if ( $blog_id ) {
				WP_CLI::warning(sprintf(sb__('Cloudflare feature is already set to %s on site %s'), $state_string, get_site_url($blog_id)));
			} else {
				WP_CLI::warning(sprintf(sb__('Cloudflare feature is already set to %s'), $state_string));
			}
			return;
		}

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
	 * List Cloudflare zones with/without numbering.
	 *
	 * @param bool $include_numbers
	 * @param bool $output_texts
	 *
	 * @return bool
	 */
	protected function list_zones($include_numbers = false, $output_texts = true) {
		$zones = $this->get_zones();
		if ( ! $zones || empty($zones) ) {
			if ( $output_texts ) {
				return false;
			} else {
				WP_CLI::error(sb__('Could not retrieve any available zones. Make sure you have configured the Cloudflare API credentials and set an active zone.'));
			}

		}
		if ( $output_texts ) {
			WP_CLI::line(sb__('The following zones are available:'));
		}
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
	protected function cf_store_zone_direct($zone_id) {
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
	 * Display status about the cache purge situation for a specified blog.
	 *
	 * @param bool $blog_id
	 * @param bool $extended
	 *
	 * @return array
	 */
	protected function cf_purge_status($blog_id = false, $extended = false) {
		$is_cron_purge = sb_cf()->cron_purge_is_active(true, $blog_id);
		if ( $blog_id ) {
			$arr = ['Blog' => $blog_id];
		} else {
			$arr = [];
		}

		if ( $extended ) {
			$arr['Cloudfare feature active'] = sb_cf()->cf_is_active($blog_id) ? 'Active' : 'Inactive';
		}

		$arr['Purge type'] = $is_cron_purge ? 'Via cron' : 'Immediate purge';
		$purge_item_column_key = 'Purge queue' . ( $extended ? '' : ' (Post ID)' );

		if ($is_cron_purge) {
			$max_items = 50;
			$items_to_purge = sb_cf()->get_items_to_purge(false, $blog_id);
			$items_to_purge_count = sb_cf()->count_items_to_purge();

			if ( $extended ) {
				$posts = sb_resolve_post_ids($items_to_purge, $blog_id);
				$posts_string = sb_format_array_to_csv($posts, ', ');
			} else {
				$posts_string = sb_format_array_to_csv($items_to_purge);
			}

			$arr[$purge_item_column_key] = $items_to_purge ? $posts_string . ( $items_to_purge_count > $max_items ? '...' : '') : 'Empty';
		} else {
			$arr[$purge_item_column_key] = '-';
		}
		return $arr;
	}

	/**
	 * Get zone for specified site.
	 *
	 * @param bool $blog_id
	 */
	protected function cf_get_zone($blog_id = false) {
		$zone_id = sb_cf()->get_active_zone_id($blog_id);
		if ( $zone_id && $zone_object = sb_cf()->get_zone_by_id($zone_id, $blog_id) ) {
			$zone = $zone_object ? $zone_object->name . ' (' . $zone_object->id . ')' : $zone_id;
		} else {
			$zone = $zone_id;
		}
		if ( empty($zone) ) {
			if ( $blog_id ) {
				WP_CLI::warning(sprintf(sb__('Active zone is not set for site %s'), get_site_url($blog_id)));
			} else {
				WP_CLI::warning(sb__('Active zone is not set'));
			}
		} else {
			if ( $blog_id ) {
				WP_CLI::success(sprintf(sb__('Active zone is %s for site %s'), $zone, get_site_url($blog_id)));
			} else {
				WP_CLI::success(sprintf(sb__('Active zone is %s'), $zone));
			}
		}
	}

	/**
	 * Display Cloudflare API credentials for a specified site.
	 *
	 * @param bool $blog_id
	 *
	 * @return array
	 */
	protected function cf_get_credentials($blog_id = false) {
		$auth_type = sb_cf()->get_authentication_type($blog_id);

		if ( $blog_id ) {
			$arr = ['Blog' => $blog_id];
		} else {
			$arr = [];
		}

		$arr['Cloudflare feature status'] = sb_cf()->cf_is_active($blog_id) ? 'Active' : 'Inactive';

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
				$arr['email'] = null;
				break;
		}
		return $arr;
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
			sb_iterate_sites(function ( $site ) use ($state) {
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
		if ( sb_cf()->cf_switch_to_blog($blog_id) === false ) {

			// Could not switch to blog
			WP_CLI::error(sprintf(sb__('Could not get Cloudflare feature status on site %s'), get_site_url($blog_id)), false);
			return;

		}

		$current_state = sb_cf()->cf_is_active();
		$cron_state_string = sb_boolean_to_state_string($current_state);
		if ( $blog_id ) {
			WP_CLI::success(sprintf(sb__('Cloudflare feature is %s for site %s'), $cron_state_string, get_site_url($blog_id)));
		} else {
			WP_CLI::success(sprintf(sb__('Cloudflare feature is %s'), $cron_state_string));
		}
	}

	/**
	 * Enable/disable Nginx cache headers.
	 *
	 * @param bool $cron_state
	 * @param bool $blog_id
	 */
	protected function cf_cron_control(bool $cron_state, $blog_id = false) {

		$purge_type = $cron_state === true ? 'cron' : 'immediately';

		// Switch context to blog
		if ( sb_cf()->cf_switch_to_blog($blog_id) === false ) {

			// Could not switch to blog
			WP_CLI::error(sprintf(sb__('Could not set cache purge type to "%s" on site %s'), $purge_type, get_site_url($blog_id)), false);
			return;

		}

		$current_state = sb_cf()->cron_purge_is_active();
		if ( $current_state === $cron_state ) {
			if ( is_numeric($blog_id) ) {
				WP_CLI::warning(sprintf(sb__('Cache purge type is already "%s" on site %s'), $purge_type, get_site_url($blog_id)));
			} else {
				WP_CLI::warning(sprintf(sb__('Cache purge type is already "%s"'), $purge_type));
			}
		} else {
			sb_cf()->cf_toggle_cron_active($cron_state);
			if ( is_numeric($blog_id) ) {
				WP_CLI::success(sprintf(sb__('Cache purge type is set to "%s" on site %s'), $purge_type, get_site_url($blog_id)));
			} else {
				WP_CLI::success(sprintf(sb__('Cache purge type is set to "%s"'), $purge_type));
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
		if ( sb_cf()->cf_switch_to_blog($blog_id) === false ) {

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
		if ( sb_cf()->cf_switch_to_blog($blog_id) === false ) {

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

}
