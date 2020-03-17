<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once 'cli-extras.class.php';

/**
 * Class Servebolt_CLI
 * @package Servebolt
 *
 * Does all the WP CLI handling.
 */
class Servebolt_CLI extends Servebolt_CLI_Extras {

	/**
	 * Singleton instance.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Instantiate class.
	 *
	 * @return Servebolt_CLI|null
	 */
	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new Servebolt_CLI;
		}
		return self::$instance;
	}

	/**
	 * Servebolt_CLI constructor.
	 */
	private function __construct() {
		$this->registerCommands();
	}

	/**
	 * Register WP CLI commands.
	 */
	private function registerCommands() {
		WP_CLI::add_command( 'servebolt clear-all-settings',       [$this, 'clear_all_settings'] );

		WP_CLI::add_command( 'servebolt db optimize',              [$this, 'optimize_database'] );
		WP_CLI::add_command( 'servebolt db fix',                   [$this, 'fix'] );
		WP_CLI::add_command( 'servebolt db analyze',               [$this, 'analyze_tables'] );

		WP_CLI::add_command( 'servebolt fpc enable',               [$this, 'nginx_fpc_enable'] );
		WP_CLI::add_command( 'servebolt fpc disable',              [$this, 'nginx_fpc_disable'] );
		//WP_CLI::add_command( 'servebolt fpc set-cache-post-types', [$this, 'nginx_fpc_set_cache_post_types'] );
		WP_CLI::add_command( 'servebolt fpc status',               [$this, 'nginx_fpc_status'] );

		WP_CLI::add_command( 'servebolt cf enable',                [$this, 'cf_enable'] );
		WP_CLI::add_command( 'servebolt cf disable',               [$this, 'cf_disable'] );
		WP_CLI::add_command( 'servebolt cf cron enable',           [$this, 'nginx_cf_cron_enable'] );
		WP_CLI::add_command( 'servebolt cf cron disable',          [$this, 'nginx_cf_cron_disable'] );
		WP_CLI::add_command( 'servebolt cf get-config',            [$this, 'cf_config_get'] );
		//WP_CLI::add_command( 'servebolt cf test-api-connection',   [$this, 'cf_test_api_connection'] );
		WP_CLI::add_command( 'servebolt cf list-zones',            [$this, 'cf_list_zones'] );
		WP_CLI::add_command( 'servebolt cf set-zone',              [$this, 'cf_set_zone'] );
		WP_CLI::add_command( 'servebolt cf clear-zone',            [$this, 'cf_clear_zone'] );
		WP_CLI::add_command( 'servebolt cf set-credentials',       [$this, 'cf_set_credentials'] );
		WP_CLI::add_command( 'servebolt cf clear-credentials',     [$this, 'cf_clear_credentials'] );
		WP_CLI::add_command( 'servebolt cf purge url',             [$this, 'cf_purge_url'] );
		WP_CLI::add_command( 'servebolt cf purge post',            [$this, 'cf_purge_post'] );
		WP_CLI::add_command( 'servebolt cf purge all',             [$this, 'cf_purge_all'] );
	}

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
	 *     wp servebolt cf enable
	 */
	public function cf_enable() {
		return sb_cf()->cf_toggle_active(true);
	}

	/**
	 * Deactivate Cloudflare features.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf disable
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
	/*
	public function cf_test_api_connection() {
		if ( sb_cf()->test_api_connection() ) {
			WP_CLI::success('API connection passed!');
		} else {
			WP_CLI::error(sprintf('Could not communicate with the API. Please check that API credentials are configured correctly and that we have the right permissions (%s).', sb_cf()->api_permissions_needed()));
		}
	}
	*/

	/**
	 * Select active Cloudflare zone.
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
				$type        = 'apiKey';
				$verbose_type = 'API key';
				$credentials = compact('email', 'apiKey');
				$verbose_credentials = implode(' / ', $credentials);
				break;
			case 'token':
				$token = array_key_exists('api-token', $assoc_args) ? $assoc_args['api-token'] : false;
				if ( ! $token || empty($token) ) {
					WP_CLI::error('Please specify a token.');
				}
				$type        = 'apiToken';
				$verbose_type = 'API token';
				$credentials = $token;
				$verbose_credentials = $credentials;
				break;
			default:
				WP_CLI::error('Could not set credentials.');
		}
		if ( sb_cf()->store_credentials($credentials, $type) ) {
			WP_CLI::success(sprintf('Cloudflare credentials set using %s: %s', $verbose_type, $verbose_credentials));
			/*
			if ( ! sb_cf()->test_api_connection() ) {
				WP_CLI::error(sprintf('Credentials were stored but the API connection test failed. Please check that the credentials are correct and have the correct permissions (%s).', sb_cf()->api_permissions_needed()), false);
			} else {
				WP_CLI::success(sprintf('Cloudflare credentials set using %s: %s', $verboseType, $verboseCredentials));
				WP_CLI::success('API connection test passed!');
			}
			*/
		} else {
			WP_CLI::error(sprintf('Could not set Cloudflare credentials set using %s: %s', $verbose_type, $verbose_credentials));
		}
	}

	/**
	 * Schedule the cron to execute queue-based cache purge.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf cron enable
	 *
	 */
	public function nginx_cf_cron_enable( $args, $assoc_args ) {
		$this->cf_cron_control(true, $assoc_args);
	}

	/**
	 * Un-schedule the cron cache purge,
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf cron disable
	 *
	 */
	public function nginx_cf_cron_disable( $args, $assoc_args ) {
		$this->cf_cron_control(false, $assoc_args);
	}

	/**
	 * Activate the correct cache headers for Servebolt Full Page Cache.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Activate on all sites in multisite
	 *
	 * [--post_types=<post_types>]
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
	 *     wp servebolt fpc enable --post_types=post,page
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
	 * [--all]
	 * : Deactivate on all sites in multisite
	 *
	 * [--post_types=<post_types>]
	 * : Comma separated list of post types to be deactivated
	 *
	 * [--exclude=<ids>]
	 * : Comma separated list of ids to exclude for full page caching
	 *
	 * [--status]
	 * : Display status after control is executed
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Deactivate Servebolt Full Page Cache, but only for pages and posts
	 *     wp servebolt fpc deactivate --post_types=post,page
	 *
	 */
	public function nginx_fpc_disable( $args, $assoc_args ) {
		$this->nginx_fpc_control(false, $assoc_args);
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
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf purge url <URL>
	 *
	 */
	public function cf_purge_url($args) {
		list($url) = $args;
		WP_CLI::line(sprintf('Purging cache for url %s', $url));
		if ( sb_cf()->purgeByUrl($url) ) {
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
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf purge post <post ID>
	 *
	 */
	public function cf_purge_post($args) {
		list($post_id) = $args;
		WP_CLI::line(sprintf('Purging cache for post %s', $post_id));
		if ( sb_cf()->purgePost($post_id) ) {
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
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf get-config
	 */
	public function cf_config_get() {
		$cf = sb_cf();
		$auth_type = $cf->get_authentication_type();
		$is_cron_purge = $cf->cron_purge_is_active(true);

		$arr = [
			'Status'     => $cf->cf_is_active() ? 'Active' : 'Inactive',
			'Zone Id'    => $cf->get_ative_zone_id(),
			'Purge type' => $is_cron_purge ? 'Via cron' : 'Immediate purge',
		];

		if ($is_cron_purge) {
			$arr['Ids to purge (queue for Cron)'] = $cf->get_items_to_purge();
		}

		$arr['API authentication type'] = $auth_type;
		switch ($auth_type) {
			case 'apiKey':
				$arr['API key'] = $cf->get_credential('apiKey');
				$arr['email'] = $cf->get_credential('email');
				break;
			case 'apiToken':
				$arr['API token'] = $cf->getCredential('apiToken');
				break;
		}

		WP_CLI\Utils\format_items('table', [ $arr ], array_keys($arr));
	}

}
