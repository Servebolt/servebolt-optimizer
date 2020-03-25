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
	public function clear_all_settings() {
		sb_clear_all_settings();
		WP_CLI::success('All settings cleared!');
	}

	/**
	 * Alias of "wp servebolt db optimize". Add database indexes and convert database tables to modern table types or delete transients.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt db fix
	 */
	public function fix() {
		$this->optimize_database();
	}

	/**
	 * Add database indexes and convert database tables to modern table types or delete transients.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt db optimize
	 */
	public function optimize_database() {
		if ( ! sb_optimize_db()->optimize_db(true) ) {
			WP_CLI::success('Optimization done');
		} else {
			WP_CLI::warning('Everything OK. No optimization to do.');
		}
	}

	/**
	 * Analyze tables.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt db analyze
	 */
	public function analyze_tables() {
		if ( ! sb_optimize_db()->analyze_tables(true) ) {
			WP_CLI::error('Could not analyze tables.');
		} else {
			WP_CLI::success('Analyzed tables.');
		}
	}

	/**
	 * Activate Cloudflare features.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf activate
	 */
	public function cf_enable() {
		return sb_cf()->cf_toggle_active(true);
	}

	/**
	 * Deactivate Cloudflare features.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf deactivate
	 */
	public function cf_disable() {
		return sb_cf()->cf_toggle_active(false);
	}

	/**
	 * List available zones in Cloudflare.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf list-zones
	 */
	public function cf_list_zones() {
		$this->list_zones();
	}

	/**
	 * Clear active Cloudflare zone so that we do not interact with it more.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf clear-zone
	 */
	public function cf_clear_zone() {
		sb_cf()->clear_active_zone();
		WP_CLI::success('Successfully cleared zone.');
	}

	/**
	 * Clear Cloudflare credentials.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf clear-credentials
	 */
	public function cf_clear_credentials() {
		sb_cf()->clear_credentials();
		WP_CLI::success('Successfully cleared credentials.');
	}

	/**
	 * Check that we can communicate with the Cloudflare API.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf test-api-connection
	 */
	public function cf_test_api_connection() {
		if ( sb_cf()->test_api_connection() ) {
			WP_CLI::success('API connection passed!');
		} else {
			WP_CLI::error(sprintf('Could not communicate with the API. Please check that API credentials are configured correctly and that we have the right permissions (%s).', sb_cf()->api_permissions_needed()));
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
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Set zone by attempting to fetch available zones from Cloudflare. Might not always work when using tokens that are limited to specific zones.
	 *     wp servebolt cf set-zone
	 *
	 *     # Set zone without any integrity check.
	 *     wp servebolt cf set-zone --zone-id="zone-id"
	 *
	 *
	 */
	public function cf_set_zone($args, $assoc_args) {

		// Allow to set zone without listing out available zones.
		$zone_id = ( array_key_exists('zone-id', $assoc_args) && ! empty($assoc_args['zone-id']) ) ? $assoc_args['zone-id'] : false;
		if ( $zone_id ) {
			$this->store_zone_direct($zone_id);
			return;
		}

		WP_CLI::line('This will set/update which Cloudflare zone we are interacting with.');

		if ( $current_zone_id = sb_cf()->get_active_zone_id() ) {
			$current_zone = sb_cf()->get_zone_by_id($current_zone_id);
			if ( $current_zone ) {
				WP_CLI::line(sprintf('Current zone is set to %s (%s)', $current_zone->name, $current_zone->id));
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
			WP_CLI::error('Invalid selection, please try again.', false);
			goto select_zone;
		}

		// Notify user if the zone domain name does not match in the blog URL.
		$home_url = get_home_url();
		if ( strpos($home_url, $found_zone->name) === false ) {
			WP_CLI::warning(sprintf('Selected zone (%s) does not match with the URL fo the current blog (%s). This will most likely inhibit cache purge to work.', $found_zone->name, $home_url));
		}

		sb_cf()->store_active_zone_id($found_zone->id);
		WP_CLI::success(sprintf('Successfully selected zone %s', $found_zone->name));

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
	 * ---
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
	public function cf_set_credentials($args, $assoc_args) {
		list($auth_type) = $args;
		switch ( $auth_type ) {
			case 'key':
				$email = array_key_exists('email', $assoc_args) ? $assoc_args['email'] : false;
				$api_key = array_key_exists('api-key', $assoc_args) ? $assoc_args['api-key'] : false;
				if ( ! $email || empty($email) || ! $api_key || empty($api_key) ) {
					WP_CLI::error('Please specify API key and email.');
				}
				$type         = 'api_key';
				$verbose_type = 'API key';
				$credentials = compact('email', 'api_key');
				$verbose_credentials = implode(' / ', $credentials);
				break;
			case 'token':
				$token = array_key_exists('api-token', $assoc_args) ? $assoc_args['api-token'] : false;
				if ( ! $token || empty($token) ) {
					WP_CLI::error('Please specify a token.');
				}
				$type         = 'api_token';
				$verbose_type = 'API token';
				$credentials = $token;
				$verbose_credentials = $credentials;
				break;
			default:
				WP_CLI::error('Could not set credentials.');
		}
		if ( sb_cf()->store_credentials($credentials, $type) ) {
			if ( ! sb_cf()->test_api_connection() ) {
				WP_CLI::error(sprintf('Credentials were stored but the API connection test failed. Please check that the credentials are correct and have the correct permissions (%s).', sb_cf()->api_permissions_needed()), false);
			} else {
				WP_CLI::success(sprintf('Cloudflare credentials set using %s: %s', $verbose_type, $verbose_credentials));
				WP_CLI::success('API connection test passed!');
			}
		} else {
			WP_CLI::error(sprintf('Could not set Cloudflare credentials set using %s: %s', $verbose_type, $verbose_credentials));
		}
	}

	/**
	 * Schedule the cron to execute queue-based cache purge.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf cron activate
	 *
	 */
	public function cf_cron_enable( $args, $assoc_args ) {
		$this->cf_cron_control(true, $assoc_args);
	}

	/**
	 * Un-schedule the cron cache purge,
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf cron deactivate
	 *
	 */
	public function cf_cron_disable( $args, $assoc_args ) {
		$this->cf_cron_control(false, $assoc_args);
	}

	/**
	 * Clear the cache purge queue.
	 *
	 * ## OPTIONS
	 *
	 * [--all-blogs]
	 * : Clear queue on all sites in multisite
	 *
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf clear-cache-purge-queue
	 *
	 */
	public function cf_clear_cache_purge_queue( $args, $assoc_args ) {
		$affect_all_blogs = array_key_exists('all-blogs', $assoc_args);
		if ( ! sb_cf()->cron_purge_is_active() ) {
			WP_CLI::warning(sb__('Note: cache purge via cron is not active.'));
		}
		if ( sb_cf()->has_items_to_purge() ) {
			sb_cf()->clear_items_to_purge();
			WP_CLI::success(sb__('Cache purge queue cleared.'));
		} else {
			WP_CLI::warning('Cache purge queue already empty.');
		}
	}

	/**
	 * Set the post types that should be cached with Nginx Full Page Cache.
	 *
	 * ## OPTIONS
	 *
	 * [--all-blogs]
	 * : Set post types on all sites in multisite
	 *
	 * [--all]
	 * : Set cache for all post types
	 *
	 * [--post-types]
	 * : The post types we would like to cache.
	 *
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Activate cache for given post types on all blogs
	 *     wp servebolt fpc set-post-types --post-types=page,post --all-blogs
	 *
	 *     # Activate cache for all post types on current blog
	 *     wp servebolt fpc set-post-types --all
	 *
	 */
	public function nginx_fpc_set_cache_post_types($args, $assoc_args) {
		$post_types_string = array_key_exists('post-types', $assoc_args) ? $assoc_args['post-types'] : '';
		$post_types = sb_format_comma_string($post_types_string);
		$all_post_types = array_key_exists('all', $assoc_args);
		$affect_all_blogs = array_key_exists('all-blogs', $assoc_args);

		if ( $all_post_types ) {
			$post_types = ['all'];
		}

		if ( empty($post_types) ) {
			WP_CLI::error(sb__('No post types specified'));
		}

		if ( ! $all_post_types ) {
			foreach($post_types as $post_type) {
				if ( $post_type !== 'all' && ! get_post_type_object($post_type) ) {
					WP_CLI::error(sb__(sprintf('Post type "%s" does not exist', $post_type)));
				}
			}
		}

		if ( is_multisite() && $affect_all_blogs ) {
			foreach (get_sites() as $site) {
				$this->nginx_set_post_types($post_types, $site->blog_id);
			}
		} else {
			$this->nginx_set_post_types($post_types);
		}
	}

	/**
	 * Set the post types that should be cached with Nginx Full Page Cache.
	 *
	 * ## OPTIONS
	 *
	 * [--all-blogs]
	 * : Set post types on all sites in multisite
	 *
	 * [--all]
	 * : Set cache for all post types
	 *
	 * [--post-types]
	 * : The post types we would like to cache.
	 *
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Activate cache for given post types on all blogs
	 *     wp servebolt fpc set-post-types --post-types=page,post --all-blogs
	 *
	 *     # Activate cache for all post types on current blog
	 *     wp servebolt fpc set-post-types --all
	 *
	 */
	public function nginx_fpc_set_excluded_posts($args, $assoc_args) {
		// TODO: Complete this
	}

	/**
	 * Enable Nginx Full Page Cache.
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
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Activate Servebolt Full Page Cache, but only for pages and posts
	 *     wp servebolt fpc activate --post-types=post,page
	 *
	 */
	public function nginx_fpc_enable( $args, $assoc_args ) {
		$this->nginx_fpc_control(true, $assoc_args);
		if ( in_array('status', $assoc_args) ) $this->get_nginx_fpc_status($assoc_args, false);
	}

	/**
	 * Disable Nginx Full Page Cache.
	 *
	 * ## OPTIONS
	 *
	 * [--status]
	 * : Display status after command is executed
	 *
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Deactivate Servebolt Full Page Cache
	 *     wp servebolt fpc deactivate
	 *
	 */
	public function nginx_fpc_disable( $args, $assoc_args ) {
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
	public function nginx_fpc_status( $args, $assoc_args  ) {
		$this->get_nginx_fpc_status($assoc_args);
	}

	/**
	 * Clear Cloudflare cache by URL.
	 *
	 * ## OPTIONS
	 *
	 * <URL>
	 * : The URL that should be cleared cache for.
	 *
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf purge url <URL>
	 *
	 */
	public function cf_purge_url($args) {
		list($url) = $args;
		WP_CLI::line(sprintf('Purging cache for url %s', $url));
		if ( sb_cf()->purge_by_url($url) ) {
			WP_CLI::success('Cache purged!');
		} else {
			WP_CLI::error('Could not purge cache.');
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
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf purge post <post ID>
	 *
	 */
	public function cf_purge_post($args) {
		list($post_id) = $args;
		WP_CLI::line(sprintf('Purging cache for post %s', $post_id));
		if ( sb_cf()->purge_post($post_id) ) {
			WP_CLI::success('Cache purged!');
		} else {
			WP_CLI::error('Could not purge cache.');
		}
	}

	/**
	 * Clear all Cloudflare cache.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf purge all
	 *
	 */
	public function cf_purge_all() {
		$current_zone_id = sb_cf()->get_active_zone_id();
		if ( $current_zone_id && $current_zone = sb_cf()->get_zone_by_id($current_zone_id) ) {
			WP_CLI::line(sprintf('Purging all cache for zone %s', $current_zone->name));
		} else {
			WP_CLI::line('Purging all cache');
		}

		if ( sb_cf()->purge_all() ) {
			WP_CLI::success('Cache purged!');
		} else {
			WP_CLI::error('Could not purge cache.');
		}
	}

	/**
	 * Display config parameters for Cloudflare.
	 *
	 * ## OPTIONS
	 *
	 * [--all-blogs]
	 * : Set post types on all sites in multisite
	 *
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf get-config
	 */
	public function cf_get_config($args, $assoc_args) {
		$affect_all_blogs = array_key_exists('all-blogs', $assoc_args);
		$arr = [];
		if ( is_multisite() && $affect_all_blogs ) {
			foreach (get_sites() as $site) {
				$arr[] = $this->cf_get_config_for_blog($site->blog_id);
			}
			$arr = $this->cf_ensure_array_integrity($arr);
		} else {
			$arr[] = $this->cf_get_config_for_blog();
		}
		WP_CLI\Utils\format_items('table', $arr, array_keys(current($arr)));
	}

}
