<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_CLI_Commands
 * @package Servebolt
 *
 * This class contains the callback methods for all commands.
 */
abstract class Servebolt_CLI_Commands extends Servebolt_CLI_Extras {

	/**
	 * Clear all settings related to this plugin.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt clear-all-settings
	 */
	public function command_clear_all_settings() {
		sb_clear_all_settings();
		WP_CLI::success(sb__('All settings cleared!'));
	}

	/**
	 * Alias of "wp servebolt db optimize". Add database indexes and convert database tables to modern table types or delete transients.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt db fix
	 */
	public function command_fix() {
		$this->optimize_database();
	}

	/**
	 * Add database indexes and convert database tables to modern table types or delete transients.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt db optimize
	 *
	 */
	public function command_optimize_database() {
		if ( ! sb_optimize_db()->optimize_db(true) ) {
			WP_CLI::success(sb__('Optimization done'));
		} else {
			WP_CLI::warning(sb__('Everything OK. No optimization to do.'));
		}
	}

	/**
	 * Analyze tables.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt db analyze
	 *
	 */
	public function command_analyze_tables() {
		if ( ! sb_optimize_db()->analyze_tables(true) ) {
			WP_CLI::error(sb__('Could not analyze tables.'));
		} else {
			WP_CLI::success(sb__('Analyzed tables.'));
		}
	}

	/**
	 * Set the post types that should be cached with Servebolt Full Page Cache.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Set post types on all sites in multisite
	 *
	 * [--all-post-types]
	 * : Set cache for all post types
	 *
	 * [--post-types]
	 * : The post types we would like to cache.
	 *
	 * ## EXAMPLES
	 *
	 *     # Activate cache for given post types on all sites
	 *     wp servebolt fpc set-post-types --post-types=page,post --all
	 *
	 *     # Activate cache for all post types on current site
	 *     wp servebolt fpc set-post-types --all-post-types
	 *
	 */
	public function command_nginx_fpc_set_cache_post_types($args, $assoc_args) {
		$all_post_types = array_key_exists('all-post-types', $assoc_args);
		$post_types_string = sb_array_get('post-types', $assoc_args, '');
		$post_types = sb_format_comma_string($post_types_string);

		if ( $all_post_types ) {
			$post_types = ['all'];
		}

		if ( empty($post_types) ) {
			WP_CLI::error(sb__('No post types specified'));
		}

		if ( $this->affect_all_sites($assoc_args) ) {
			sb_iterate_sites(function ( $site ) use ($post_types) {
				$this->nginx_set_post_types($post_types, $site->blog_id);
			});
		} else {
			$this->nginx_set_post_types($post_types);
		}
	}

	/**
	 * Set the post that should be excluded from the cache.
	 *
	 * ## OPTIONS
	 *
	 * <post_ids>
	 * : The posts to exclude from cache.
	 *
	 * ## EXAMPLES
	 *
	 *     # Exclude cache for posts with ID 1, 2 and 3
	 *     wp servebolt fpc set-excluded-posts 1,2,3
	 *
	 */
	public function command_nginx_fpc_set_excluded_posts($args, $assoc_args) {
		list($post_ids_raw) = $args;
		$this->nginx_set_exclude_ids($post_ids_raw);
	}

	/**
	 * Activatae Servebolt Full Page Cache.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Activate on all sites in multisite
	 *
	 * [--post-types=<post_types>]
	 * : Comma separated list of post types to be activated
	 *
	 * [--exclude=<ids>]
	 * : Comma separated list of ids to exclude for full page caching
	 *
	 * [--status]
	 * : Display status after control is executed
	 *
	 * ## EXAMPLES
	 *
	 *     # Activate Servebolt Full Page Cache, but only for pages and posts
	 *     wp servebolt fpc activate --post-types=post,page
	 *
	 *     # Activate Servebolt Full Page Cache for all public post types on all sites in multisite-network
	 *     wp servebolt fpc activate --post-types=all --all
	 *
	 */
	public function command_nginx_fpc_enable($args, $assoc_args) {
		$this->nginx_fpc_control(true, $assoc_args);
		if ( in_array('status', $assoc_args) ) $this->get_nginx_fpc_status($assoc_args, false);
	}

	/**
	 * Deactivate Servebolt Full Page Cache.
	 *
	 * ## OPTIONS
	 *
	 * [--status]
	 * : Display status after command is executed
	 *
	 * ## EXAMPLES
	 *
	 *     # Deactivate Servebolt Full Page Cache
	 *     wp servebolt fpc deactivate
	 *
	 */
	public function command_nginx_fpc_disable($args, $assoc_args) {
		$this->nginx_fpc_control(false);
		if ( in_array('status', $assoc_args) ) $this->get_nginx_fpc_status($assoc_args, false);
	}

	/**
	 * Return status of the Servebolt Full Page Cache.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt fpc status
	 *
	 */
	public function command_nginx_fpc_status($args, $assoc_args) {
		$this->get_nginx_fpc_status($assoc_args);
	}

	/**
	 * Activate Cloudflare feature.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Activate Cloudflare API feature on all sites in multisite-network.
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
	 * : Deactivate Cloudflare API feature on all sites in multisite-network.
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
	 * List available zones in Cloudflare.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf list-zones
	 *
	 */
	public function command_cf_list_zones() {
		$this->list_zones();
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
	 *     wp servebolt cf clear-zone
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
	 * Clear Cloudflare API credentials.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Clear Cloudflare API credentials on all sites in multisite-network.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf clear-credentials
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
	 * Test the Cloudflare API connection.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Test Cloudflare API connection on all sites in multisite-network.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf test-api-connection
	 *
	 */
	public function command_cf_test_api_connection($args, $assoc_args) {
		if ( $this->affect_all_sites( $assoc_args ) ) {
			sb_iterate_sites(function ( $site ) {
				$this->cf_test_api_connection( $site->blog_id );
			});
		} else {
			$this->cf_test_api_connection();
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
	 *     wp servebolt cf set-zone
	 *
	 *     # Set zone without any integrity check.
	 *     wp servebolt cf set-zone --zone-id="zone-id"
	 *
	 */
	public function command_cf_set_zone($args, $assoc_args) {

		// Allow to set zone without listing out available zones.
		$zone_id = ( array_key_exists('zone-id', $assoc_args) && ! empty($assoc_args['zone-id']) ) ? $assoc_args['zone-id'] : false;
		if ( $zone_id ) {
			$this->store_zone_direct($zone_id);
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

		echo 'Select which zone you would like to set up: ';
		$handle = fopen ('php://stdin', 'r');
		$line = trim(fgets($handle));

		$found_zone = false;
		foreach ( $zones as $i => $zone ) {
			if ( $i+1 == $line || $zone->id == $line || $zone->name == $line ) {
				$found_zone = $zone;
				break;
			}
		}
		fclose($handle);
		if ( ! $found_zone ) {
			WP_CLI::error(sb__('Invalid selection, please try again.'), false);
			goto select_zone;
		}

		// Notify user if the zone domain name does not match in the site URL.
		$home_url = get_home_url();
		if ( strpos($home_url, $found_zone->name) === false ) {
			WP_CLI::warning(sprintf(sb__('Selected zone (%s) does not match with the URL fo the current site (%s). This will most likely inhibit cache purge to work.'), $found_zone->name, $home_url));
		}

		sb_cf()->store_active_zone_id($found_zone->id);
		WP_CLI::success(sprintf(sb__('Successfully selected zone %s'), $found_zone->name));

	}

	/**
	 * Store Cloudflare API credentials.
	 *
	 * ## OPTIONS
	 *
	 * <apiAuthType>
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
	 *     wp servebolt cf set-credentials key --api-key="your-api-key" --email="person@example.com"
	 *
	 *     # Set credentials using API token.
	 *     wp servebolt cf set-credentials token --api-token="your-api-token"
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
	 * Check if the Cloudflare-feature is active/inactive.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Check on all sites in multisite
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
	 * Check if the Cloudflare cron cache purge-feature is active/inactive.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Check on all sites in multisite
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf cron status
	 *
	 */
	public function command_cf_cron_status($args, $assoc_args) {
		if ( $this->affect_all_sites( $assoc_args ) ) {
			sb_iterate_sites(function ( $site ) {
				$this->cf_cron_status($site->blog_id);
			});
		} else {
			$this->cf_cron_status();
		}
	}

	/**
	 * Schedule the cron to execute queue-based cache purge.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Schedule cron on all sites in multisite
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf cron activate
	 *
	 */
	public function command_cf_cron_enable($args, $assoc_args) {
		$this->cf_cron_toggle( true, $assoc_args );
	}

	/**
	 * Un-schedule the cron to execute queue-based cache purge.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Un-schedule cron on all sites in multisite
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf cron deactivate
	 *
	 */
	public function command_cf_cron_disable($args, $assoc_args) {
		$this->cf_cron_toggle( false, $assoc_args );
	}

	/**
	 * Clear the cache purge queue.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Clear queue on all sites in multisite
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf clear-cache-purge-queue
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
	 * <URL>
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
	 * <post ID>
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
		if ( $this->affect_all_sites($assoc_args) ) {
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

	/**
	 * Display config parameters for Cloudflare.
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

}
