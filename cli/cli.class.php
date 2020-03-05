<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once 'cli-extras.class.php';

/**
 * Class Servebolt_CLI
 * @package Servebolt
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
	public static function getInstance() {
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
		WP_CLI::add_command( 'servebolt clear-settings',         [$this, 'clear_all_settings'] );

		WP_CLI::add_command( 'servebolt db optimize',            [$this, 'optimize'] ); // TODO: Remove in v1.7
		WP_CLI::add_command( 'servebolt db fix',                 [$this, 'optimize'] );
		WP_CLI::add_command( 'servebolt db analyze',             [$this, 'analyze_tables'] );

		WP_CLI::add_command( 'servebolt fpc activate',           [$this, 'nginx_activate'] );
		WP_CLI::add_command( 'servebolt fpc deactivate',         [$this, 'nginx_deactivate'] );
		WP_CLI::add_command( 'servebolt fpc status',             [$this, 'nginx_status'] );

		WP_CLI::add_command( 'servebolt cf activate',            [$this, 'cf_activate'] );
		WP_CLI::add_command( 'servebolt cf deactivate',          [$this, 'cf_deactivate'] );
		WP_CLI::add_command( 'servebolt cf get-config',          [$this, 'cf_config_get'] );
		WP_CLI::add_command( 'servebolt cf test-api-connection', [$this, 'cf_test_api_connection'] );
		WP_CLI::add_command( 'servebolt cf list-zones',          [$this, 'cf_list_zones'] );
		WP_CLI::add_command( 'servebolt cf set-zone',            [$this, 'cf_set_zone'] );
		WP_CLI::add_command( 'servebolt cf clear-zone',          [$this, 'cf_clear_zone'] );
		WP_CLI::add_command( 'servebolt cf set-credentials',     [$this, 'cf_set_credentials'] );
		WP_CLI::add_command( 'servebolt cf clear-credentials',   [$this, 'cf_clear_credentials'] );
		WP_CLI::add_command( 'servebolt cf purge-url',           [$this, 'cf_purge_url'] );
		WP_CLI::add_command( 'servebolt cf purge-post',          [$this, 'cf_purge_post'] );
		WP_CLI::add_command( 'servebolt cf purge-all',           [$this, 'cf_purge_all'] );
	}

	/**
	 * Clear all settings related to this plugin.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt clear-settings
	 */
	public function clear_all_settings() {
		sb_clear_all_settings();
		WP_CLI::success('All settings cleared!');
	}

	/**
	 * Add database indexes and convert database tables to modern table types or delete transients.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt db optimize
	 */
	public function optimize() {
		require_once SERVEBOLT_PATH . 'admin/optimize-db/optimize-db.php';
		if ( ! ( Servebolt_Optimize_DB::getInstance() )->optimize_db(true) ) {
			WP_CLI::success('Optimization done');
		} else {
			WP_CLI::warning('Everything OK. No optimization to do.');
		}
	}

	/**
	 * Display config parameters for Cloudflare.
	 */
	public function cf_config_get() {
		$cf = sb_cf();
		$authType = $cf->getAuthenticationType();
		$isCronPurge = $cf->cronPurgeIsActive(true);

		$arr = [
			'Status'     => $cf->CFIsActive() ? 'Active' : 'Inactive',
			'Zone Id'    => $cf->getActiveZoneId(),
			'Purge type' => $isCronPurge ? 'Via cron' : 'Immediate purge',
		];

		if ($isCronPurge) {
			$arr['Ids to purge (queue for Cron)'] = $cf->getItemsToPurge();
		}

		$arr['API authentication type'] = $authType;
		switch ($authType) {
			case 'apiKey':
				$arr['API key'] = $cf->getCredential('apiKey');
				$arr['email'] = $cf->getCredential('email');
				break;
			case 'apiToken':
				$arr['API token'] = $cf->getCredential('apiToken');
				break;
		}

		WP_CLI\Utils\format_items('table', [ $arr ], array_keys($arr));
	}

	/**
	 * Analyze tables.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt db analyze
	 */
	public function analyze_tables() {
		require_once SERVEBOLT_PATH . 'admin/optimize-db/optimize-db.php';
		if ( ! ( Servebolt_Optimize_DB::getInstance() )->analyze_tables(true) ) {
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
	public function cf_activate() {
		return sb_update_option('cf_switch', true);
	}

	/**
	 * Deactivate Cloudflare features.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf deactivate
	 */
	public function cf_deactivate() {
		return sb_update_option('cf_switch', true);
	}

	/**
	 * List available zones in Cloudflare.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf list-zones
	 */
	public function cf_list_zones() {
		$this->listZones();
	}

	/**
	 * Clear active Cloudflare zone so that we do not interact with it more.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf clear-zone
	 */
	public function cf_clear_zone() {
		sb_cf()->clearActiveZone();
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
		sb_cf()->clearCredentials();
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
		if ( sb_cf()->testAPIConnection() ) {
			WP_CLI::success('API connection passed!');
		} else {
			WP_CLI::error(sprintf('Could not communicate with the API. Please check that API credentials are configured correctly and that we have the right permissions (%s).', sb_cf()->APIPermissionsNeeded()));
		}
	}

	/**
	 * Select active Cloudflare zone.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf set-zone
	 */
	public function cf_set_zone() {

		WP_CLI::line('This will set/update which Cloudflare zone we are interacting with.');

		if ( $currentZoneId = sb_cf()->getActiveZoneId() ) {
			$currentZone = sb_cf()->getZoneById($currentZoneId);
			if ( $currentZone ) {
				WP_CLI::line(sprintf('Current zone is set to %s', $currentZone->name));
			}
		}

		$zones = $this->getZones();

		SelectZone:
		$this->listZones(true);

		echo 'Select which zone you would like to set up: ';
		$handle = fopen ('php://stdin', 'r');
		$line = trim(fgets($handle));

		$foundZone = false;
		foreach ( $zones as $i => $zone ) {
			if ( $i+1 == $line || $zone->id == $line || $zone->name == $line ) {
				$foundZone = $zone;
				break;
			}
		}
		fclose($handle);
		if ( ! $foundZone ) {
			WP_CLI::error('Invalid selection, please try again.', false);
			goto SelectZone;
		}

		// Notify user if the zone domain name does not match in the blog URL.
		$homeUrl = get_home_url();
		if ( strpos($homeUrl, $foundZone->name) === false ) {
			WP_CLI::warning(sprintf('Selected zone (%s) does not match with the URL fo the current blog (%s). This will most likely inhibit cache purge to work.', $foundZone->name, $homeUrl));
		}

		sb_cf()->storeActiveZoneId($foundZone->id);
		WP_CLI::success(sprintf('Successfully selected zone %s', $foundZone->name));

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
	 *     wp servebolt cf set-credentials key --api-key=your-api-key --email=person@example.com
	 *
	 *     # Set credentials using API token.
	 *     wp servebolt cf set-credentials token --api-token=your-api-token
	 *
	 */
	public function cf_set_credentials($args, $assoc_args) {
		list($authType) = $args;
		switch ( $authType ) {
			case 'key':
				$email = array_key_exists('email', $assoc_args) ? $assoc_args['email'] : false;
				$apiKey = array_key_exists('api-key', $assoc_args) ? $assoc_args['api-key'] : false;
				if ( ! $email || empty($email) || ! $apiKey || empty($apiKey) ) {
					WP_CLI::error('Please specify API key and email.');
				}
				$type        = 'apiKey';
				$verboseType = 'API key';
				$credentials = compact('email', 'apiKey');
				$verboseCredentials = implode(' / ', $credentials);
				break;
			case 'token':
				$token = array_key_exists('api-token', $assoc_args) ? $assoc_args['api-token'] : false;
				if ( ! $token || empty($token) ) {
					WP_CLI::error('Please specify a token.');
				}
				$type        = 'apiToken';
				$verboseType = 'API token';
				$credentials = $token;
				$verboseCredentials = $credentials;
				break;
			default:
				WP_CLI::error('Could not set credentials.');
		}
		if ( sb_cf()->storeCredentials($credentials, $type) ) {

			if ( ! sb_cf()->testAPIConnection() ) {
				WP_CLI::error(sprintf('Credentials were stored but the API connection test failed. Please check that the credentials are correct and have the correct permissions (%s).', sb_cf()->APIPermissionsNeeded()), false);
			} else {
				WP_CLI::success(sprintf('Cloudflare credentials set using %s: %s', $verboseType, $verboseCredentials));
				WP_CLI::success('API connection test passed!');
			}
		} else {
			WP_CLI::error(sprintf('Could not set Cloudflare credentials set using %s: %s', $verboseType, $verboseCredentials));
		}

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
	 *     wp servebolt fpc activate --post_types=post,page
	 *
	 */
	public function nginx_activate( $args, $assoc_args ) {
		$this->servebolt_nginx_control('activate', $args, $assoc_args);
	}

	/**
	 * Deactivate the correct cache headers for Servebolt Full Page Cache.
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
	public function nginx_deactivate( $args, $assoc_args ) {
		$this->servebolt_nginx_control('deactivate', $args, $assoc_args);
		if ( in_array('status', $assoc_args) ) $this->servebolt_nginx_status($args, $assoc_args);
	}

	/**
	 * Return status of the Servebolt Full Page Cache.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt fpc status
	 *
	 */
	public function nginx_status( $args, $assoc_args  ) {
		$this->servebolt_nginx_status( $args, $assoc_args  );
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
	 *     wp servebolt cf purge-url <URL>
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
	 * <post Id>
	 * : The post ID of the post that should be cleared cache for.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt cf purge-post <post ID>
	 *
	 */
	public function cf_purge_post($args) {
		list($postId) = $args;
		WP_CLI::line(sprintf('Purging cache for post %s', $postId));
		if ( sb_cf()->purgePost($postId) ) {
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
	 *     wp servebolt cf purge-all
	 *
	 */
	public function cf_purge_all() {
		$currentZoneId = sb_cf()->getActiveZoneId();
		if ( $currentZoneId && $currentZone = sb_cf()->getZoneById($currentZoneId) ) {
			WP_CLI::line(sprintf('Purging all cache for zone %s', $currentZone->name));
		} else {
			WP_CLI::line('Purging all cache');
		}

		if ( sb_cf()->purgeAll() ) {
			WP_CLI::success('Cache purged!');
		} else {
			WP_CLI::error('Could not purge cache.');
		}
	}

}
