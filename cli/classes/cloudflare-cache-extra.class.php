<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\booleanToStateString;
use function Servebolt\Optimizer\Helpers\resolvePostIdsToTitleAndPostIdString;
use function Servebolt\Optimizer\Helpers\formatArrayToCsv;
use function Servebolt\Optimizer\Helpers\getBlogName;
use function Servebolt\Options\Helpers\iterateSites;

/**
 * Class Servebolt_CLI_Cloudflare_Cache_Extra
 */
class Servebolt_CLI_Cloudflare_Cache_Extra extends Servebolt_CLI_Extras {

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
	 * Create a new instance of Cloudflare API class with registering credentials manually.
	 * @param $params
	 *
	 * @return bool|Servebolt_CF_Cache
	 */
	private function cf_create_cloudflare_cache_instance($params) {
		$cf = new Servebolt_CF_Cache();
		if ( $cf->register_credentials_manually($params['auth_type'], $params) ) {
			return $cf;
		}
		return false;
	}

	/**
	 * Test if we can have a valid instance to communicate with Cloudflare API and that the connection to the API is ok.
	 *
	 * @param $cf
	 * @param $params
	 * @param bool $return
	 *
	 * @return bool|string|void
	 */
	private function test_api($cf, $params, $return = false) {
		if ( ! $cf ) {
			$message = __('Could not register credentials with Cloudflare API class.', 'servebolt-wp');
			if ( $return ) {
				return $message;
			}
			WP_CLI::error($message);
		} elseif ( ! $params['disable_validation'] ) {
			if ( $cf->test_api_connection($params['auth_type']) ) {
				if ( ! $return ) {
					WP_CLI::success(__('Cloudflare API test passed.', 'servebolt-wp'));
				}
				return true;
			} else {
				$message = __('Cloudflare API test failed. Please check your credentials.', 'servebolt-wp');
				if ( $return ) {
					return $message;
				}
				WP_CLI::error($message);
			}
		}
		return true;
	}

	/**
	 * A confirm-prompt that is customizable and does not necessarily quit after you reply no (unlike WP-CLI's own confirm-prompt...).
	 * @param $text
	 *
	 * @return mixed
	 */
	private function confirm($text) {
		$result = $this->collect_parameter($text . ' ' . __('[y/n]', 'servebolt-wp') . ' ', __('Please reply with either "y" or "n".', 'servebolt-wp'), function($input) {
			switch ($input) {
				case 'y':
					return ['boolean' => true];
					break;
				case 'n':
					return ['boolean' => false];
					break;
			}
			return false;
		});
		return $result['boolean'];
	}

	/**
	 * Check whether the multisite contains a setup spanning over multiple domains.
	 *
	 * @param bool $only_check_top_domain
	 *
	 * @return bool
	 */
	private function multisite_has_multiple_domains($only_check_top_domain = false) {
		$domains = [];
        iterateSites(function($site) use (&$domains, $only_check_top_domain) {
			$site_url = get_site_url($site->blog_id);
			$url_parts = parse_url($site_url);
			$hostname = $url_parts['host'];
			if ( $only_check_top_domain ) {
				$hostname_parts = explode('.', $hostname);
				$hostname_part_count = count($hostname_parts);
				if ( $hostname_part_count > 2 ) {
					$hostname = implode('.', array_slice($hostname_parts, $hostname_part_count - 2));
				}
			}
			$domains[] = $hostname;
		});
		$domains = array_unique($domains);
		$domains = apply_filters('sb_optimizer_evaluate_multidomain_setup', $domains);
		return apply_filters('sb_optimizer_evaluate_multidomain_setup_conclusion', count($domains) > 1);
	}

	/**
	 * Interactive setup for Cloudflare.
	 *
	 * @param $params
	 */
	protected function cf_setup_interactive($params) {

		$api_connection_available = false;

		WP_CLI::line(__('Welcome!', 'servebolt-wp'));
		WP_CLI::line(__('This guide will set up the Cloudflare feature on your site.', 'servebolt-wp'));
		WP_CLI::line(__('This allows for automatic cache purge when the contents of your site changes.', 'servebolt-wp'));
		WP_CLI::line(__('Note that this will potentially overwrite any already existing configuration.', 'servebolt-wp'));
		if ( $params['affect_all_sites'] ) {
			WP_CLI::warning(__('This will affect all your site in the multisite-network', 'servebolt-wp'));
		}
		WP_CLI::line();
		WP_CLI::confirm(__('Do you want to continue?', 'servebolt-wp'));

		if ( is_multisite() && ! $params['affect_all_sites'] ) {
			WP_CLI::line(__('It looks like this is a multisite.', 'servebolt-wp'));
			$params['affect_all_sites'] = (boolean) $this->confirm(__('Do you want to setup Cloudflare on all sites in multisite-network?', 'servebolt-wp'));
		}

		$this->separator();

		WP_CLI::line(__('Okay, first we need to set up the API connection to Cloudflare.', 'servebolt-wp'));

		// Determine authentication type
		if ( $params['auth_type'] ) {
			if ( ! $this->auth_type_valid($params['auth_type']) ) {
				WP_CLI::error(sprintf(__('Invalid authentication type specified: "%s"', 'servebolt-wp'), $params['auth_type']));
			}
			WP_CLI::success(sprintf(__('Cloudflare API authentication type is already set to "%s"', 'servebolt-wp'), $params['auth_type']));
		} else {
			$params['auth_type'] = $this->collect_parameter(__('Select one of the options: ', 'servebolt-wp'), __('Invalid selection, please try again.', 'servebolt-wp'), function($input) {
				if ( empty($input) ) return false;
				return $this->auth_type_valid($input, false, true);
			}, function () {
				WP_CLI::line(__('How will you authenticate with the Cloudflare API?', 'servebolt-wp'));
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
					WP_CLI::success(sprintf(__('API token is already set to "%s"', 'servebolt-wp'), $params['api_token']));
				} else {
					$params['api_token'] = $this->collect_parameter(__('Specify Cloudflare API Token: ', 'servebolt-wp'), __('API cannot be empty.', 'servebolt-wp'));
				}

				$cf = $this->cf_create_cloudflare_cache_instance($params);
				if ( $this->test_api($cf, $params) ) {
					$api_connection_available = true;
				}

				break;
			case 'key':
			case 'API Keys':
				if ( $params['email'] ) {
					WP_CLI::success(sprintf(__('E-mail is already set to "%s"', 'servebolt-wp'), $params['email']));
				} else {
					$params['email'] = $this->collect_parameter(__('Specify Cloudflare username (e-mail): '), __('E-mail cannot be empty.', 'servebolt-wp'));
				}

				if ( $params['api_key'] ) {
					WP_CLI::success(sprintf(__('API key is already set to "%s"', 'servebolt-wp'), $params['api_key']));
				} else {
					$params['api_key'] = $this->collect_parameter(__('Specify Cloudflare API key: ', 'servebolt-wp'), __('API key cannot be empty.', 'servebolt-wp'));
				}

				$cf = $this->cf_create_cloudflare_cache_instance($params);
				if ( $this->test_api($cf, $params) ) {
					$api_connection_available = true;
				}

				break;
			default:
				WP_CLI::error(__('Invalid authentication type, please try again.', 'servebolt-wp'));
				break;
		}

		if ( $params['affect_all_sites'] ) {
			$this->separator();
		}

		$individual_zone_setup = $params['individual_zones'];
		if ( $params['affect_all_sites'] && $this->multisite_has_multiple_domains() && is_null($individual_zone_setup) ) {
			if ( $individual_zone_setup !== true ) {
				WP_CLI::line(__('Seems like your multisite has multiple domains.', 'servebolt-wp'));
				$individual_zone_setup = $this->confirm(__('Would you like to set an individual Zone ID for each site?', 'servebolt-wp'));
			} else {
				WP_CLI::line(__('Seems like your multisite has multiple domains. Please set an individual Zone ID for each site.', 'servebolt-wp'));
			}
		}

		if ( $params['affect_all_sites'] && $individual_zone_setup ) {

			if ( $params['zone'] ) {
				WP_CLI::warning(sprintf(__('Zone ID is already specified as "%s" via the arguments, but since Zone ID is individual for each site then this value will be ignored.', 'servebolt-wp'), $params['zone']));
			}

			WP_CLI::line('Please follow the guide below to set Zone ID for each site:');
			$params['zone'] = [];

			$first = true;
            iterateSites(function ($site) use ($api_connection_available, &$params, &$first) {
				$params['zone'][$site->blog_id] = $this->select_zone($api_connection_available, $params, $site->blog_id, ! $first);
				$first = false;
			});

		} else {
			$params['zone'] = $this->select_zone($api_connection_available, $params, false, null);
		}

		$this->separator();

		if ( $params['affect_all_sites'] ) {
			$result = [];
            iterateSites(function($site) use (&$result, $params, $individual_zone_setup) {
				if ( $individual_zone_setup ) {
					$zone = $params['zone'][$site->blog_id]['id'];
				} else {
					$zone = $params['zone']['id'];
				}
				$result[$site->blog_id] = $this->store_cf_configuration($params['auth_type'], $params, $zone, $site->blog_id) && sb_cf_cache()->cf_toggle_active(true, $site->blog_id);
			});
			$has_failed = in_array(false, $result, true);
			$all_failed = ! in_array(true, $result, true);
			if ( $has_failed ) {
				if ( $all_failed ) {
					WP_CLI::error(__('Could not set config on any sites.', 'servebolt-wp'));
				} else {
					$table = [];
					foreach($result as $key => $value) {
						$table[] = [
							__('Blod ID', 'servebolt-wp') => $key,
							__('Configuration', 'servebolt-wp') => $value ? __('Success', 'servebolt-wp') : __('Failed', 'servebolt-wp'),
						];
					}
					WP_CLI::warning(__('Action complete, but we failed to apply config to some sites:', 'servebolt-wp'));
					WP_CLI\Utils\format_items( 'table', $table, array_keys(current($table)));
				}
			} else {
				WP_CLI::success(__('Configuration on all sites!', 'servebolt-wp'));
			}
		} else {
			if ( $this->store_cf_configuration($params['auth_type'], $params, $params['zone']['id']) && sb_cf_cache()->cf_toggle_active(true) ) {
				WP_CLI::success(__('Configuration stored!', 'servebolt-wp'));
			} else {
				WP_CLI::error(__('Hmm, could not store configuration. Please try again and/or contact support.', 'servebolt-wp'));
			}
		}

		WP_CLI::success(__('Cloudflare feature successfully set up!', 'servebolt-wp'));

	}

	/**
	 * Display CLI separator output.
	 *
	 * @param int $length
	 */
	private function separator($length = 20) {
		WP_CLI::line(str_repeat('-', $length));
	}

	/**
	 * Display zone selector during interactive Cloudflare setup.
	 *
	 * @param $api_connection_available
	 * @param $params
	 * @param bool $blog_id
	 * @param bool $big_separator
	 *
	 * @return array|bool|string
	 */
	private function select_zone($api_connection_available, $params, $blog_id = false, $big_separator = true) {
		if ( is_bool($big_separator) ) {
			if ( $big_separator ) {
				$this->separator();
			}  else {
				$this->separator(5);
			}
		}
		$selected_zone = false;
		if ( $blog_id ) {
			//WP_CLI::line(sprintf(__('Select Zone ID for site: %s (%s)'), getBlogName($blog_id), get_site_url($blog_id)));
			WP_CLI::line(sprintf(__('%s (%s)'), getBlogName($blog_id), get_site_url($blog_id)));
		}

		// Determine which zone to use
		if ( $api_connection_available && $zones = $this->get_zones() ) {

			if ( ! $blog_id && $params['zone'] ) {
				$selected_zone = $this->zone_array($params['zone']);
				WP_CLI::success(sprintf(__('Zone ID is already specified to be "%s"', 'servebolt-wp'), $selected_zone['id']));
			} else {

				WP_CLI::line(__('Choose from the list below or specify your own Zone ID.', 'servebolt-wp'));
				$this->list_zones(true);

				$selected_zone = $this->collect_parameter( __('Cloudflare Zone ID: ', 'servebolt-wp'), __('Zone cannot be empty.', 'servebolt-wp'), function ( $input ) use ( $zones ) {
					if ( empty( $input ) ) {
						return false;
					}
					foreach ( $zones as $i => $zone ) {
						if ( $i + 1 == $input || $zone->id == $input || $zone->name == $input ) {
							return $this->zone_array( $zone->id, $zone->name );
						}
					}
					return $this->zone_array( $input );
				} );
			}

			if ( ! $params['disable_validation'] ) {
				$zone = sb_cf_cache()->get_zone_by_id($selected_zone['id']);
				if ( ! $zone ) {
					WP_CLI::error(__('Could not validate zone. Make sure it exists and that we have access to it.', 'servebolt-wp'));
				}
			}

		} else {
			if ( ! $blog_id && $params['zone'] ) {
				$selected_zone = $this->zone_array($params['zone']);
				WP_CLI::success(sprintf(__('Zone ID is already set to "%s"', 'servebolt-wp'), $selected_zone['id']));
			} else {
				$zone_id = $this->collect_parameter(__('Cloudflare Zone ID: ', 'servebolt-wp'), __('Zone cannot be empty.', 'servebolt-wp'));
				$selected_zone = $this->zone_array($zone_id);
			}
		}

		if ( $selected_zone['name'] ) {
			WP_CLI::line(sprintf(__('Selected zone %s (%s)'), $selected_zone['name'], $selected_zone['id']));
		} else {
			WP_CLI::line(sprintf(__('Selected zone %s', 'servebolt-wp'), $selected_zone['id']));
		}

		return $selected_zone;
	}

	/**
	 * Build array to contain zone information.
	 *
	 * @param $id
	 * @param bool $name
	 *
	 * @return array
	 */
	private function zone_array($id, $name = false) {
		return [
			'id'   => $id,
			'name' => $name,
		];
	}

	/**
	 * Store all Cloudflare configuration.
	 *
	 * @param $auth_type
	 * @param $credentials
	 * @param $zone_id
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	private function store_cf_configuration($auth_type, $credentials, $zone_id, $blog_id = false) {
		return sb_cf_cache()->store_credentials($auth_type, $credentials, $blog_id) && sb_cf_cache()->store_active_zone_id($zone_id, $blog_id);
	}

	/**
	 * Validate non-interactive Cloudflare setup.
	 *
	 * @param $params
	 *
	 * @return array|bool
	 */
	private function validate_non_interactive_setup_params($params) {
		$messages = [];
		$api_connection_available = false;

		if ( ! $this->auth_type_valid($params['auth_type'], true, false) ) {
			$messages[] = __('Authentication type invalid.', 'servebolt-wp');
		}

		switch ($params['auth_type']) {
			case 'key':

				if ( empty($params['email']) ) {
					$messages[] = __('E-mail must be specified.', 'servebolt-wp');
				}

				if ( empty($params['api_key']) ) {
					$messages[] = __('API key must be specified.', 'servebolt-wp');
				}

				$cf = $this->cf_create_cloudflare_cache_instance($params);
				$cf_check = $this->test_api($cf, $params, true);
				if ( $cf_check === true ) {
					$api_connection_available = true;
				} else {
					$messages[] = $cf_check;
				}

				break;

			case 'token':

				if ( empty($params['api_token']) ) {
					$messages[] = __('API token must be specified.', 'servebolt-wp');
				}

				$cf = $this->cf_create_cloudflare_cache_instance($params);
				$cf_check = $this->test_api($cf, $params, true);
				if ( $cf_check === true ) {
					$api_connection_available = true;
				} else {
					$messages[] = $cf_check;
				}

				break;
		}



		if ( empty($params['zone']) ) {
			$messages[] = __('Zone must be specified.', 'servebolt-wp');
		} elseif ( $api_connection_available && ! $params['disable_validation'] ) {
			$zone = sb_cf_cache()->get_zone_by_id($params['zone']);
			if ( ! $zone ) {
				$messages[] = __('Zone is invalid. Make sure it exists and that we have access to it.', 'servebolt-wp');
			}
		}

		return empty($messages) ? true : $messages;
	}

	/**
	 * Non-interactive setup for Cloudflare.
	 *
	 * @param $params
	 */
	protected function cf_setup_non_interactive($params) {

		// Validate data
		$validation = $this->validate_non_interactive_setup_params($params);
		if ( $validation !== true ) {
			WP_CLI::error_multi_line($validation);
			return;
		}

		if ( $params['affect_all_sites'] ) {
			$result = [];
            iterateSites(function($site) use (&$result, $params) {
				$result[$site->blog_id] = $this->store_cf_configuration($params['auth_type'], $params, $params['zone'], $site->blog_id);
			});
			$has_failed = in_array(false, $result, true);
			$all_failed = ! in_array(true, $result, true);
			if ( $has_failed ) {
				if ( $all_failed ) {
					WP_CLI::error(__('Could not set config on any sites.', 'servebolt-wp'));
				} else {
					$table = [];
					foreach($result as $key => $value) {
						$table[] = [
							__('Blod ID', 'servebolt-wp') => $key,
							__('Configuration', 'servebolt-wp') => $value ? __('Success', 'servebolt-wp') : __('Failed', 'servebolt-wp'),
						];
					}
					WP_CLI::warning(__('Action complete, but we failed to apply config to some sites:', 'servebolt-wp'));
					WP_CLI\Utils\format_items( 'table', $table, array_keys(current($table)));
				}
			} else {
				WP_CLI::success(__('Configuration on all sites!', 'servebolt-wp'));
			}
		} else {
			if ( $this->store_cf_configuration($params['auth_type'], $params, $params['zone']) ) {
				WP_CLI::success(__('Cloudflare configuration stored successfully.', 'servebolt-wp'));
			} else {
				WP_CLI::error(__('Could not store Cloudflare configuration.', 'servebolt-wp'));
			}
		}
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

		$failCount = 1;
		$maxFailCount = 5;
		set_param:

		// Call before prompt-function
		if ( is_callable($before_input_prompt) ) {
			$before_input_prompt();
		}

		if ( $failCount == $maxFailCount ) {
			echo '[Last attempt] ';
		}

		echo $prompt_message;
		$param = $this->user_input($validation);
		if ( ! $param ) {
			if ( $failCount >= $maxFailCount ) {
				WP_CLI::error('No input received, exiting.');
			}
			$failCount++;
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
			$this->zones = sb_cf_cache()->list_zones();
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
		if ( sb_cf_cache()->cf_switch_to_blog($blog_id) === false ) {

			// Could not switch to blog
			WP_CLI::error(sprintf(__('Could not execute cache purge on site %s', 'servebolt-wp'), get_site_url($blog_id)), false);
			return;

		}

		// Check if cron purge is active
		if ( ! sb_cf_cache()->cron_purge_is_active() ) {
			if ( $blog_id ) {
				WP_CLI::warning(sprintf(__('Note: cache purge via cron is not active on site %s.', 'servebolt-wp'), get_site_url($blog_id)));
			} else {
				WP_CLI::warning(__('Note: cache purge via cron is not active.', 'servebolt-wp'));
			}
		}

		// Check if we got items to purge, if so we purge them
		if ( sb_cf_cache()->has_items_to_purge() ) {
			sb_cf_cache()->clear_items_to_purge();
			if ( $blog_id ) {
				WP_CLI::success(sprintf(__('Cache purge queue cleared on site %s.', 'servebolt-wp'), get_site_url($blog_id)));
			} else {
				WP_CLI::success(__('Cache purge queue cleared.', 'servebolt-wp'));
			}

		} else {
			if ( $blog_id ) {
				WP_CLI::warning(sprintf(__('Cache purge queue already empty on site %s.', 'servebolt-wp'), get_site_url($blog_id)));
			} else {
				WP_CLI::warning(__('Cache purge queue already empty.', 'servebolt-wp'));
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
		if ( sb_cf_cache()->cf_switch_to_blog($blog_id) === false ) return false;

		// Get zone name
		$current_zone_id = sb_cf_cache()->get_active_zone_id();
		$current_zone = sb_cf_cache()->get_zone_by_id($current_zone_id);

		// Tell the user what we're doing
		if ( $blog_id ) {
			WP_CLI::line(sprintf(__('Purging all cache for zone %s on site %s', 'servebolt-wp'), $current_zone->name, get_site_url($blog_id)));
		} else {
			WP_CLI::line(sprintf(__('Purging all cache for zone %s', 'servebolt-wp'), $current_zone->name));
		}

		// Purge all cache
		return sb_cf_cache()->purge_all();
	}

	/**
	 * Activate/deactivate Cloudflare feature.
	 *
	 * @param bool $state
	 * @param bool $blog_id
	 */
	protected function cf_toggle_active(bool $state, $blog_id = false) {
		$state_string = booleanToStateString($state);
		$is_active = sb_cf_cache()->cf_is_active($blog_id);

		if ( $is_active === $state ) {
			if ( $blog_id ) {
				WP_CLI::warning(sprintf(__('Cloudflare feature is already set to %s on site %s', 'servebolt-wp'), $state_string, get_site_url($blog_id)));
			} else {
				WP_CLI::warning(sprintf(__('Cloudflare feature is already set to %s', 'servebolt-wp'), $state_string));
			}
			return;
		}

		if ( sb_cf_cache()->cf_toggle_active($state, $blog_id) ) {
			if ( $blog_id ) {
				WP_CLI::success(sprintf(__('Cloudflare feature was set to %s on site %s', 'servebolt-wp'), $state_string, get_site_url($blog_id)));
			} else {
				WP_CLI::success(sprintf(__('Cloudflare feature was set to %s', 'servebolt-wp'), $state_string));
			}
		} else {
			if ( $blog_id ) {
				WP_CLI::error(sprintf(__('Could not set Cloudflare feature to %s on site %s', 'servebolt-wp'), $state_string, get_site_url($blog_id)), false);
			} else {
				WP_CLI::error(sprintf(__('Could not set Cloudflare feature to %s', 'servebolt-wp'), $state_string), false);
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
			if ( ! $output_texts ) return false;
			$error_lines = [__('Could not retrieve any zones. This might be the reasons:', 'servebolt-wp')];
			$error_lines[] = '- ' . __('Check that you have configured the Cloudflare API credentials', 'servebolt-wp');
			switch( sb_cf_cache()->get_authentication_type() ) {
				case 'api_token':
					$error_lines[] = '- ' . __('Your API token might be limited to one or more zones.', 'servebolt-wp');
					$error_lines[] = '  ' . __('Listing zones does unfortunately only work when "Zone Resource" is set to "All Zones" (when creating the token in Cloudflare).');
					break;
				case 'api_key':
					$error_lines[] = '- ' . __('Your API keys belongs to a user which might lack permissions to list zones.', 'servebolt-wp');
					break;
			}
			WP_CLI::error_multi_line($error_lines);
			return false;
		}
		if ( $output_texts ) {
			WP_CLI::line(__('The following zones are available:', 'servebolt-wp'));
		}
		foreach ($zones as $i => $zone ) {
			if ( $include_numbers === true ) {
				WP_CLI::line(sprintf('[%s] %s (%s)', $i+1, $zone->name, $zone->id));
			} else {
				WP_CLI::line(sprintf('%s (%s)', $zone->name, $zone->id));
			}
		}
		return true;
	}

	/**
	 * Store zone without listing available zones.
	 *
	 * @param $zone_id
	 */
	protected function cf_store_zone_direct($zone_id) {
		$zone_object = sb_cf_cache()->get_zone_by_id($zone_id);
		$zone_identification = $zone_object ? $zone_object->name . ' (' . $zone_object->id . ')' : $zone_id;
		if ( ! $zone_object ) {
			WP_CLI::error_multi_line(['Could not find zone with the specified ID in Cloudflare. This might indicate:', '- the API connection is not working', '- that the zone does not exists', '- that we lack access to the zone']);
			WP_CLI::confirm(__('Do you still wish to set the zone?', 'servebolt-wp'));
		}
		sb_cf_cache()->store_active_zone_id($zone_id);
		WP_CLI::success(sprintf(__('Successfully selected zone %s', 'servebolt-wp'), $zone_identification));
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
		$is_cron_purge = sb_cf_cache()->cron_purge_is_active(true, $blog_id);
		if ( $blog_id ) {
			$arr = ['Blog' => $blog_id];
		} else {
			$arr = [];
		}

		if ( $extended ) {
			$arr['Cloudfare feature active'] = sb_cf_cache()->cf_is_active($blog_id) ? 'Active' : 'Inactive';
		}

		$arr['Purge type'] = $is_cron_purge ? 'Via cron' : 'Immediate purge';
		$purge_item_column_key = 'Purge queue' . ( $extended ? '' : ' (Post ID)' );

		if ($is_cron_purge) {
			$max_items = 50;
			$items_to_purge = sb_cf_cache()->get_items_to_purge(false, $blog_id);
			$items_to_purge_count = sb_cf_cache()->count_items_to_purge();

			if ( $extended ) {
				$posts = resolvePostIdsToTitleAndPostIdString($items_to_purge, $blog_id);
				$posts_string = formatArrayToCsv($posts, ', ');
			} else {
				$posts_string = formatArrayToCsv($items_to_purge);
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
	 * @param bool $output
	 *
	 * @return array
	 */
	protected function cf_get_zone($blog_id = false, $output = true) {
		$zone_id = sb_cf_cache()->get_active_zone_id($blog_id);
		if ( $zone_id && $zone_object = sb_cf_cache()->get_zone_by_id($zone_id, $blog_id) ) {
			$zone = $zone_object ? $zone_object->name . ' (' . $zone_object->id . ')' : $zone_id;
		} else {
			$zone = $zone_id;
		}
		if ( empty($zone) ) {
			if ( $output ) {
				if ( $blog_id ) {
					WP_CLI::warning(sprintf(__('Active zone is not set for site %s', 'servebolt-wp'), get_site_url($blog_id)));
				} else {
					WP_CLI::warning(__('Active zone is not set', 'servebolt-wp'));
				}
			} else {
				$arr = [];
				if ( $blog_id ) {
					$arr['Blog'] = getBlogName($blog_id);
					$arr['URL'] = get_site_url($blog_id);
				}
				$arr['Zone'] = 'Not set';
				return $arr;
			}
		} else {
			if ( $output ) {
				if ( $blog_id ) {
					WP_CLI::success( sprintf( __('Active zone is %s for site %s', 'servebolt-wp'), $zone, get_site_url( $blog_id ) ) );
				} else {
					WP_CLI::success( sprintf( __('Active zone is %s', 'servebolt-wp'), $zone ) );
				}
			} else {
				$arr = [];
				if ( $blog_id ) {
					$arr['Blog'] = getBlogName($blog_id);
					$arr['URL'] = get_site_url($blog_id);
				}
				$arr['Zone'] = $zone;
				return $arr;
			}
		}
	}

	/**
	 * Display Cloudflare config for a given site.
	 *
	 * @param bool $blog_id
	 *
	 * @return array
	 */
	protected function cf_get_config($blog_id = false) {

		if ( $blog_id ) sb_cf_cache()->cf_switch_to_blog($blog_id);

		$auth_type = sb_cf_cache()->get_authentication_type();
		$is_cron_purge = sb_cf_cache()->cron_purge_is_active(true);

		if ( $blog_id ) {
			$arr = ['Blog' => $blog_id];
		} else {
			$arr = [];
		}

		$zone_id = sb_cf_cache()->get_active_zone_id();
		$zone_obj = sb_cf_cache()->get_zone_by_id($zone_id);
		$zone = $zone_obj ? $zone_obj->name . ' (' . $zone_id . ')' : $zone_id;

		$arr = array_merge($arr, [
			'Status'     => sb_cf_cache()->cf_is_active() ? 'Active' : 'Inactive',
			'Zone'       => $zone,
			'Cache purge type' => $is_cron_purge ? 'Via cron' : 'Immediate purge',
		]);

		$arr['API authentication type'] = $auth_type;
		switch ($auth_type) {
			case 'key':
			case 'apiKey':
			case 'api_key':
				$arr['API key/token'] = sb_cf_cache()->get_credential('api_key');
				$arr['email'] = sb_cf_cache()->get_credential('email');
				break;
			case 'token':
			case 'apiToken':
			case 'api_token':
				$arr['API key/token'] = sb_cf_cache()->get_credential('api_token');
				break;
		}

		if ( $blog_id ) sb_cf_cache()->cf_restore_current_blog();

		return $arr;
	}

	/**
	 * Display Cloudflare API credentials for a specified site.
	 *
	 * @param bool $blog_id
	 *
	 * @return array
	 */
	protected function cf_get_credentials($blog_id = false) {
		$auth_type = sb_cf_cache()->get_authentication_type($blog_id);

		if ( $blog_id ) {
			$arr = ['Blog' => $blog_id];
		} else {
			$arr = [];
		}

		$arr['Cloudflare feature status'] = sb_cf_cache()->cf_is_active($blog_id) ? 'Active' : 'Inactive';

		$arr['API authentication type'] = $auth_type;
		switch ($auth_type) {
			case 'key':
			case 'apiKey':
			case 'api_key':
				$arr['API key/token'] = sb_cf_cache()->get_credential('api_key', $blog_id);
				$arr['email'] = sb_cf_cache()->get_credential('email', $blog_id);
				break;
			case 'token':
			case 'apiToken':
			case 'api_token':
				$arr['API key/token'] = sb_cf_cache()->get_credential('api_token', $blog_id);
				$arr['email'] = null;
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
				$new_item[$column] = arrayGet($column, $item, null);
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
            iterateSites(function ( $site ) use ($state) {
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
		if ( sb_cf_cache()->cf_switch_to_blog($blog_id) === false ) {

			// Could not switch to blog
			WP_CLI::error(sprintf(__('Could not get Cloudflare feature status on site %s', 'servebolt-wp'), get_site_url($blog_id)), false);
			return;

		}

		$current_state = sb_cf_cache()->cf_is_active();
		$cron_state_string = booleanToStateString($current_state);
		if ( $blog_id ) {
			WP_CLI::success(sprintf(__('Cloudflare feature is %s for site %s', 'servebolt-wp'), $cron_state_string, get_site_url($blog_id)));
		} else {
			WP_CLI::success(sprintf(__('Cloudflare feature is %s', 'servebolt-wp'), $cron_state_string));
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
		if ( sb_cf_cache()->cf_switch_to_blog($blog_id) === false ) {

			// Could not switch to blog
			WP_CLI::error(sprintf(__('Could not set cache purge type to "%s" on site %s', 'servebolt-wp'), $purge_type, get_site_url($blog_id)), false);
			return;

		}

		$current_state = sb_cf_cache()->cron_purge_is_active();
		if ( $current_state === $cron_state ) {
			if ( is_numeric($blog_id) ) {
				WP_CLI::warning(sprintf(__('Cache purge type is already "%s" on site %s', 'servebolt-wp'), $purge_type, get_site_url($blog_id)));
			} else {
				WP_CLI::warning(sprintf(__('Cache purge type is already "%s"', 'servebolt-wp'), $purge_type));
			}
		} else {
			sb_cf_cache()->cf_toggle_cron_active($cron_state);
			if ( is_numeric($blog_id) ) {
				WP_CLI::success(sprintf(__('Cache purge type is set to "%s" on site %s', 'servebolt-wp'), $purge_type, get_site_url($blog_id)));
			} else {
				WP_CLI::success(sprintf(__('Cache purge type is set to "%s"', 'servebolt-wp'), $purge_type));
			}
		}

		// Add / remove task from schedule
		( Servebolt_CF_Cache_Cron_Handle::get_instance() )->update_cron_state($blog_id);
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
				$email = arrayGet('email', $assoc_args);
				$api_key = arrayGet('api-key', $assoc_args);
				if ( ! $email || empty($email) || ! $api_key || empty($api_key) ) {
					WP_CLI::error(__('Please specify API key and email.', 'servebolt-wp'));
				}
				$type         = 'api_key';
				$verbose_type = 'API key';
				$credentials = compact('email', 'api_key');
				$verbose_credentials = implode(' / ', $credentials);
				return (object) compact('type', 'verbose_type', 'credentials', 'verbose_credentials');
				break;
			case 'token':
				$api_token = arrayGet('api-token', $assoc_args);
				if ( ! $api_token || empty($api_token) ) {
					WP_CLI::error(__('Please specify a token.', 'servebolt-wp'));
				}
				$type         = 'api_token';
				$verbose_type = 'API token';
				$credentials  = compact('api_token');
				$verbose_credentials = $api_token;
				return (object) compact('type', 'verbose_type', 'credentials', 'verbose_credentials');
				break;
		}

		WP_CLI::error(__('Could not set credentials. Please check the arguments and try again.', 'servebolt-wp'));
	}

	/**
	 * Clear active Cloudflare zone.
	 *
	 * @param bool $blog_id
	 */
	protected function cf_clear_zone($blog_id = false) {
		sb_cf_cache()->clear_active_zone_id($blog_id);
		if ( $blog_id ) {
			WP_CLI::success(sprintf(__('Successfully cleared zone on site %s.', 'servebolt-wp'), get_site_url($blog_id)));
		} else {
			WP_CLI::success(__('Successfully cleared zone.', 'servebolt-wp'));
		}
	}

	/**
	 * Clear all Cloudflare config.
	 *
	 * @param bool $blog_id
	 */
	protected function cf_clear_config($blog_id = false) {
		sb_cf_cache()->clear_credentials($blog_id);
		sb_cf_cache()->clear_active_zone_id($blog_id);
		sb_cf_cache()->cf_toggle_active(false, $blog_id);
		if ( $blog_id ) {
			WP_CLI::success(sprintf(__('Successfully cleared Cloudflare configuration on site %s.', 'servebolt-wp'), get_site_url($blog_id)));
		} else {
			WP_CLI::success(__('Successfully cleared Cloudflare configuration.', 'servebolt-wp'));
		}
	}

	/**
	 * Clear Cloudflare API credentials.
	 *
	 * @param bool $blog_id
	 */
	protected function cf_clear_credentials($blog_id = false) {
		sb_cf_cache()->clear_credentials($blog_id);
		if ( $blog_id ) {
			WP_CLI::success(sprintf(__('Successfully cleared API credentials on site %s.', 'servebolt-wp'), get_site_url($blog_id)));
		} else {
			WP_CLI::success(__('Successfully cleared API credentials.', 'servebolt-wp'));
		}
	}

	/**
	 * Test Cloudflare API connection on specified blog.
	 *
	 * @param bool $blog_id
	 */
	protected function cf_test_api_connection($blog_id = false) {

		// Switch context to blog
		if ( sb_cf_cache()->cf_switch_to_blog($blog_id) === false ) {

			// Could not switch to blog
			WP_CLI::error(sprintf(__('Could not test Cloudflare API connection on site %s', 'servebolt-wp'), get_site_url($blog_id)), false);
			return;

		}

		if ( sb_cf_cache()->test_api_connection() ) {
			if ( $blog_id ) {
				WP_CLI::success(sprintf(__('API connection passed on site %s', 'servebolt-wp'), get_site_url($blog_id)));
			} else {
				WP_CLI::success(__('API connection passed!', 'servebolt-wp'));
			}

			// Be sure to notify user if Zone ID is missing
			$active_zone = sb_cf_cache()->get_active_zone_id();
			if ( ! $active_zone || empty($active_zone) ) {
				WP_CLI::warning(__('Note that no Zone ID is set, so cache purge feature will not work.', 'servebolt-wp'));
			}

		} else {
			if ( $blog_id ) {
				WP_CLI::error(sprintf(__('Could not communicate with the API on site %s. Please check that API credentials are configured correctly and that we have the right permissions (%s).'), get_site_url($blog_id), sb_cf_cache()->api_permissions_needed()), false);
			} else {
				WP_CLI::error(sprintf(__('Could not communicate with the API. Please check that API credentials are configured correctly and that we have the right permissions (%s).'), sb_cf_cache()->api_permissions_needed()), false);
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
		if ( sb_cf_cache()->cf_switch_to_blog($blog_id) === false ) {

			// Could not switch to blog
			WP_CLI::error(sprintf(__('Could not set Cloudflare credentials on site %s', 'servebolt-wp'), get_site_url($blog_id)), false);
			return;

		}

		if ( sb_cf_cache()->store_credentials($credential_data->type, $credential_data->credentials) ) {
			if ( sb_cf_cache()->test_api_connection() ) {
				if ( $blog_id ) {
					WP_CLI::success(sprintf(__('Cloudflare credentials set on site %s using %s: %s', 'servebolt-wp'), get_site_url($blog_id), $credential_data->verbose_type, $credential_data->verbose_credentials));
					WP_CLI::success(sprintf(__('API connection test passed on site %s', 'servebolt-wp'), get_site_url($blog_id)));
				} else {
					WP_CLI::success(sprintf(__('Cloudflare credentials set using %s: %s', 'servebolt-wp'), $credential_data->verbose_type, $credential_data->verbose_credentials));
					WP_CLI::success(__('API connection test passed!', 'servebolt-wp'));
				}
			} else {
				if ( $blog_id ) {
					WP_CLI::warning(sprintf(__('Credentials were stored on site %s but the API connection test failed. Please check that the credentials are correct and have the correct permissions (%s).'), get_site_url($blog_id), sb_cf_cache()->api_permissions_needed()), false);
				} else {
					WP_CLI::warning(sprintf(__('Credentials were stored but the API connection test failed. Please check that the credentials are correct and have the correct permissions (%s).'), sb_cf_cache()->api_permissions_needed()), false);
				}
			}
		} else {
			if ( $blog_id ) {
				WP_CLI::error(sprintf(__('Could not set Cloudflare credentials on site %s using %s: %s', 'servebolt-wp'), get_site_url($blog_id), $credential_data->verbose_type, $credential_data->verbose_credentials), false);
			} else {
				WP_CLI::error(sprintf(__('Could not set Cloudflare credentials using %s: %s', 'servebolt-wp'), $credential_data->verbose_type, $credential_data->verbose_credentials), false);
			}
		}
	}

	/**
	 * Ensure that we are able to purge cache.
	 */
	protected function ensure_cache_purge_is_possible(): void
    {
        //if ( ! sb_cf_cache()->should_use_cf_feature() ) {
		if (!CachePurge::featureIsAvailable()) {
			WP_CLI::error(__('Cannot purge cache since Cloudflare feature is not fully configured. Make sure that you have added your Cloudflare API credentials and specified a Zone ID.', 'servebolt-wp'));
		}
	}

}
