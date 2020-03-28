<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once __DIR__ . '/cloudflare-extra.class.php';

/**
 * Class Servebolt_CLI_Cloudflare
 */
class Servebolt_CLI_Cloudflare extends Servebolt_CLI_Cloudflare_Extra {

	/**
	 * Setup procedure for Cloudflare feature.
	 *
	 * ## OPTIONS
	 *
	 * [--interactive[=<interactive>]]
	 * : Whether to run interactive setup guide or not.
	 * ---
	 * default: true
	 * options:
	 *   - true
	 *   - false
	 * ---
	 *
	 * [--all]
	 * : Setup on all sites in multisite.
	 *
	 * [--auth-type[=<auth-type>]]
	 * : The way we want to authenticate with the Cloudflare API.
	 * ---
	 * default: token
	 * options:
	 *   - token
	 *   - key
	 * ---
	 *
	 * [--api-token=<api-token>]
	 * : Cloudflare API token.
	 *
	 * [--email=<email>]
	 * : Cloudflare e-mail.
	 *
	 * [--api-key=<api-key>]
	 * : Cloudflare API key.
	 *
	 * [--zone-id=<zone-id>]
	 * : Cloudflare Zone.
	 *
	 * [--disable-validation]
	 * : Whether to validate the input data or not.
	 *
	 * ## EXAMPLES
	 *
	 *     # Run interactive setup guide
	 *     wp servebolt cf setup
	 *
	 *     # Run interactive setup guide that will be applied for all sites in a multisite-network
	 *     wp servebolt cf setup --all
	 *
	 *     # Run interactive setup guide (using token) that will be applied for all sites in a multisite-network
	 *     wp servebolt cf setup --auth-type=token --api-token="your-api-token" --all
	 *
	 *     # Run setup non-interactive with arguments setting up API token and a zone
	 *     wp servebolt cf setup --interactive=false --auth-type=token --api-token="your-api-token" --zone-id="your-zone-id"
	 *
	 *     # Run setup non-interactive with arguments setting up API token and a zone with no validation
	 *     wp servebolt cf setup --interactive=false --auth-type=key --email="your@email.com" --api-key="your-api-key" --zone-id="your-zone-id" --disable-validation
	 *
	 */
	public function command_cf_setup($args, $assoc_args) {
		$interactive        = sb_array_get('interactive', $assoc_args) ? filter_var(sb_array_get('interactive', $assoc_args), FILTER_VALIDATE_BOOLEAN) : true;
		$affect_all_sites   = $this->affect_all_sites( $assoc_args );
		$auth_type          = sb_array_get('auth-type', $assoc_args);
		$api_token          = sb_array_get('api-token', $assoc_args);
		$email              = sb_array_get('email', $assoc_args);
		$api_key            = sb_array_get('api-key', $assoc_args);
		$zone               = sb_array_get('zone', $assoc_args);
		$disable_validation = array_key_exists( 'disable-validation', $assoc_args );
		$params             = compact('interactive', 'affect_all_sites', 'auth_type', 'api_token', 'email', 'api_key', 'zone', 'disable_validation');
		if ( $interactive ) {
			$this->cf_setup_interactive($params);
		} else {
			$this->cf_setup_non_interactive($params);
		}
	}

	/**
	 * Check if the Cloudflare-feature is active/inactive.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Check on all sites in multisite.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf status
	 *
	 */
	public function command_cf_status($args, $assoc_args) {
		if ( $this->affect_all_sites( $assoc_args ) ) {
			sb_iterate_sites(function ( $site ) {
				$this->cf_status($site->blog_id);
			});
		} else {
			$this->cf_status();
		}
	}

	/**
	 * Activate Cloudflare feature.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Activate Cloudflare feature on all sites in multisite-network.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf activate
	 *
	 */
	public function command_cf_enable($args, $assoc_args) {
		if ( $this->affect_all_sites( $assoc_args ) ) {
			sb_iterate_sites(function ( $site ) {
				$this->cf_toggle_active(true, $site->blog_id);
			});
		} else {
			$this->cf_toggle_active(true);
		}
	}

	/**
	 * Deactivate Cloudflare feature.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Deactivate Cloudflare feature on all sites in multisite-network.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf deactivate
	 *
	 */
	public function command_cf_disable($args, $assoc_args) {
		if ( $this->affect_all_sites( $assoc_args ) ) {
			sb_iterate_sites(function ( $site ) {
				$this->cf_toggle_active(false, $site->blog_id);
			});
		} else {
			$this->cf_toggle_active(false);
		}
	}

	/**
	 * Display all config parameters for Cloudflare.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Display config for all sites.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf get-config
	 *
	 */
	public function command_cf_get_config($args, $assoc_args) {
		$arr = [];
		if ( $this->affect_all_sites($assoc_args) ) {
			sb_iterate_sites(function ( $site ) use (&$arr) {
				$arr[] = $this->cf_get_config($site->blog_id);
			});
			$arr = $this->cf_ensure_config_array_integrity($arr); // Make sure each item has the same column-structure
		} else {
			$arr[] = $this->cf_get_config();
		}
		WP_CLI\Utils\format_items('table', $arr, array_keys(current($arr)));
	}

	/**
	 * Test the Cloudflare API connection.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Test Cloudflare API connection on all sites in multisite-network.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf api test
	 *
	 */
	public function command_cf_test_api_connection($args, $assoc_args) {
		if ( $this->affect_all_sites( $assoc_args ) ) {
			sb_iterate_sites(function ( $site ) {
				$this->cf_test_api_connection($site->blog_id);
			});
		} else {
			$this->cf_test_api_connection();
		}
	}

	/**
	 * Get Cloudflare API credentials.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Get Cloudflare API credentials on all sites in multisite-network.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf api credentials get
	 *
	 */
	public function command_cf_get_credentials($args, $assoc_args) {
		$arr = [];
		if ( $this->affect_all_sites($assoc_args) ) {
			sb_iterate_sites(function ( $site ) use (&$arr) {
				$arr[] = $this->cf_get_credentials($site->blog_id);
			});
		} else {
			$arr[] = $this->cf_get_credentials();
		}
		WP_CLI\Utils\format_items('table', $arr, array_keys(current($arr)));
	}

	/**
	 * Store Cloudflare API credentials.
	 *
	 * ## OPTIONS
	 *
	 * <api_auth_type>
	 * : Choose between API authentication methods: "key" or "token"
	 *
	 * [--api-token=<token>]
	 * : Cloudflare API token. Only required when using API authentication method "token"
	 *
	 * [--api-key=<key>]
	 * : Cloudflare API key. Only required when using API authentication method "key"
	 *
	 * [--email=<email>]
	 * : Cloudflare API username. Only required when using API authentication method "key"
	 *
	 * [--all]
	 * : Set Cloudflare API credentials on all sites in multisite-network.
	 *
	 * ## EXAMPLES
	 *
	 *     # Set credentials using email and API key.
	 *     wp servebolt cf api credentials set key --api-key="your-api-key" --email="person@example.com"
	 *
	 *     # Set credentials using API token.
	 *     wp servebolt cf api credentials set token --api-token="your-api-token"
	 *
	 */
	public function command_cf_set_credentials($args, $assoc_args) {
		list($auth_type) = $args;

		$affect_all_sites = $this->affect_all_sites($assoc_args);
		$credential_data = $this->cf_prepare_credentials($auth_type, $assoc_args);

		if ( $affect_all_sites ) {
			sb_iterate_sites(function ( $site ) use ($credential_data) {
				$this->cf_set_credentials($credential_data, $site->blog_id);
			});
		} else {
			$this->cf_set_credentials($credential_data);
		}
	}

	/**
	 * Clear Cloudflare API credentials.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Clear Cloudflare API credentials on all sites in multisite-network.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf api credentials clear
	 *
	 */
	public function command_cf_clear_credentials($args, $assoc_args) {
		if ( $this->affect_all_sites( $assoc_args ) ) {
			sb_iterate_sites(function ( $site ) {
				$this->cf_clear_credentials( $site->blog_id );
			});
		} else {
			$this->cf_clear_credentials();
		}
	}

	/**
	 * List available zones in Cloudflare.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf zone list
	 *
	 */
	public function command_cf_list_zones() {
		$this->list_zones();
	}

	/**
	 * Get active Cloudflare zone.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Show zone on all sites in multisite-network.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt zone get
	 *
	 */
	public function command_cf_get_zone($args, $assoc_args) {
		if ( $this->affect_all_sites( $assoc_args ) ) {
			sb_iterate_sites(function ( $site ) {
				$this->cf_get_zone($site->blog_id);
			});
		} else {
			$this->cf_get_zone();
		}
	}

	/**
	 * Select active Cloudflare zone.
	 *
	 * ## OPTIONS
	 *
	 * [--zone-id=<zone>]
	 * : Cloudflare Zone id.
	 *
	 * ## EXAMPLES
	 *
	 *     # Set zone by attempting to fetch available zones from Cloudflare. Might not always work when using tokens that are limited to specific zones.
	 *     wp servebolt cf zone set
	 *
	 *     # Set zone without any integrity check.
	 *     wp servebolt cf zone set --zone-id="zone-id"
	 *
	 */
	public function command_cf_set_zone($args, $assoc_args) {

		// Allow to set zone without listing out available zones.
		$zone_id = ( array_key_exists('zone-id', $assoc_args) && ! empty($assoc_args['zone-id']) ) ? $assoc_args['zone-id'] : false;
		if ( $zone_id ) {
			$this->cf_store_zone_direct($zone_id);
			return;
		}

		WP_CLI::line(sb__('This will set/update which Cloudflare zone we are interacting with.'));

		if ( $current_zone_id = sb_cf()->get_active_zone_id() ) {
			$current_zone = sb_cf()->get_zone_by_id($current_zone_id);
			if ( $current_zone ) {
				WP_CLI::line(sprintf(sb__('Current zone is set to %s (%s)'), $current_zone->name, $current_zone->id));
			}
		}

		$zones = $this->get_zones();
		select_zone:
		$this->list_zones(true);

		sb_e('Select which zone you would like to set up: ');
		$zone = $this->user_input(function($input) use ($zones) {
			foreach ( $zones as $i => $zone ) {
				if ( $i+1 == $input || $zone->id == $input || $zone->name == $input ) {
					return $zone;
				}
			}
			return false;
		});

		if ( ! $zone ) {
			WP_CLI::error(sb__('Invalid selection, please try again.'), false);
			goto select_zone;
		}

		// Notify user if the zone domain name does not match in the site URL.
		$home_url = get_home_url();
		if ( strpos($home_url, $zone->name) === false ) {
			WP_CLI::warning(sprintf(sb__('Selected zone (%s) does not match with the URL fo the current site (%s). This will most likely inhibit cache purge to work.'), $zone->name, $home_url));
		}

		sb_cf()->store_active_zone_id($zone->id);
		WP_CLI::success(sprintf(sb__('Successfully selected zone %s'), $zone->name));

	}

	/**
	 * Clear active Cloudflare zone so that we do not interact with it more.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Clear Cloudflare zone on all sites in multisite-network.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf zone clear
	 *
	 */
	public function command_cf_clear_zone($args, $assoc_args) {
		if ( $this->affect_all_sites( $assoc_args ) ) {
			sb_iterate_sites(function ( $site ) {
				$this->cf_clear_zone( $site->blog_id );
			});
		} else {
			$this->cf_clear_zone();
		}
	}


	/**
	 * Decide how to purge the cache - immediately or via WP cron.
	 *
	 * ## OPTIONS
	 *
	 * <purge_type>
	 * : Can be either "cron" or "immediately".
	 *
	 * [--all]
	 * : Schedule cron on all sites in multisite
	 *
	 * ## EXAMPLES
	 *
	 *     # Use WP cron to purge cache queue each minute
	 *     servebolt cf purge type cron
	 *
	 *     # Purge cron immediately when a post is updated
	 *     servebolt cf purge type immediately
	 *
	 */
	public function command_cf_set_purge_type($args, $assoc_args) {
		list($purge_type) = $args;
		switch($purge_type) {
			case 'cron':
				$this->cf_cron_toggle( true, $assoc_args );
				return;
			case 'immediately':
				$this->cf_cron_toggle( false, $assoc_args );
				return;
		}
		WP_CLI::error(sb__('Invalid purge type - please use either "cron" or "immediately"'));
	}

	/**
	 * Display cache purge status.
	 *
	 * ## OPTIONS
	 *
	 * [--extended]
	 * : Display more details about the purge items.
	 *
	 * [--all]
	 * : Check on all sites in multisite.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf purge status
	 *
	 */
	public function command_cf_purge_status($args, $assoc_args) {
		$arr = [];
		$extended = array_key_exists('extended', $assoc_args);
		if ( $this->affect_all_sites( $assoc_args ) ) {
			sb_iterate_sites(function ( $site ) use (&$arr, $extended) {
				$arr[] = $this->cf_purge_status($site->blog_id, $extended);
			});
		} else {
			$arr[] = $this->cf_purge_status(false, $extended);
		}
		WP_CLI\Utils\format_items('table', $arr, array_keys(current($arr)));
	}

	/**
	 * Clear the cache purge queue.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Clear queue on all sites in multisite.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf purge clear-queue.
	 *
	 */
	public function command_cf_clear_cache_purge_queue($args, $assoc_args ) {
		if ( $this->affect_all_sites( $assoc_args ) ) {
			sb_iterate_sites(function ( $site ) {
				$this->cf_clear_cache_purge_queue($site->blog_id);
			});
		} else {
			$this->cf_clear_cache_purge_queue();
		}
	}

	/**
	 * Clear Cloudflare cache by URL.
	 *
	 * ## OPTIONS
	 *
	 * <url>
	 * : The URL that should be cleared cache for.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf purge url <URL>
	 *
	 */
	public function command_cf_purge_url($args) {
		list($url) = $args;
		WP_CLI::line(sprintf(sb__('Purging cache for url %s'), $url));
		if ( sb_cf()->purge_by_url($url) ) {
			WP_CLI::success(sb__('Cache purged!'));
		} else {
			WP_CLI::error(sb__('Could not purge cache.'));
		}
	}

	/**
	 * Clear Cloudflare cache by post Id.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : The post ID of the post that should be cleared cache for.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf purge post <post ID>
	 *
	 */
	public function command_cf_purge_post($args) {
		list($post_id) = $args;
		WP_CLI::line(sprintf(sb__('Purging cache for post %s'), $post_id));
		if ( sb_cf()->purge_post($post_id) ) {
			WP_CLI::success(sb__('Cache purged!'));
		} else {
			WP_CLI::error(sb__('Could not purge cache.'));
		}
	}

	/**
	 * Clear all Cloudflare cache.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Display config for all sites.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf purge all
	 *
	 */
	public function command_cf_purge_all($args, $assoc_args) {
		if ( $this->affect_all_sites( $assoc_args ) ) {
			sb_iterate_sites(function ( $site ) {
				if ( $this->cf_purge_all($site->blog_id) ) {
					WP_CLI::success(sprintf(sb__('All cache was purged for %s'), get_site_url($site->blog_id)), false);
				} else {
					WP_CLI::error(sprintf(sb__('Could not purge cache for %s'), get_site_url($site->blog_id)), false);
				}
			});
		} else {
			if ( $this->cf_purge_all() ) {
				WP_CLI::success(sb__('All cache was purged.'));
			} else {
				WP_CLI::error(sb__('Could not purge cache.'));
			}
		}
	}

}
