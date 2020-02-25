<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once 'cli-extras.class.php';

/**
 * Class Servebolt_CLIs
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
	private function __construct() {}

	/**
	 * Run Servebolt Optimizer.
	 *
	 * Add database indexes and convert database tables to modern table types or delete transients.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp servebolt db optimize
	 *     Success: Successfully optimized.
	 */
	public function optimize() {
		require_once SERVEBOLT_PATH . 'admin/optimize-db/optimize-db.php';
		if ( ! ( Servebolt_Optimize_DB::instance() )->optimize_db(true) ) {
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
	 *     $ wp servebolt db analyze
	 */
	public function analyze_tables() {
		require_once SERVEBOLT_PATH . 'admin/optimize-db/optimize-db.php';
		if ( ! ( Servebolt_Optimize_DB::instance() )->analyze_tables(true) ) {
			WP_CLI::error('Could not analyze tables.');
		} else {
			WP_CLI::success('Analyzed tables.');
		}
	}

	/**
	 * Cloudflare zones.
	 *
	 * List available zones in Cloudflare.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp servebolt cf list-zones
	 */
	public function cf_list_zones($args) {
		list($includeNumbers) = $args;
		WP_CLI::line('The following zones are available:');
		foreach ( $this->listZones() as $i => $zone ) {
			if ( $includeNumbers ) {
				WP_CLI::line(sprintf('[%s] %s (%s)', $i+1, $zone->name, $zone->id));
			} else {
				WP_CLI::line(sprintf('%s (%s)', $zone->name, $zone->id));
			}
		}
	}

	/**
	 * Active Cloudflare zone.
	 *
	 * Store zone in DB.
	 *
	 * @param $args
	 */
	public function cf_set_zone($args) {

		WP_CLI::line('This will set/update which Cloudflare zone we are interacting with.');

		if ( $currentZoneId = Servebolt_CF::getInstance()->getActiveZoneId() ) {
			$currentZone = Servebolt_CF::getInstance()->getZoneById($currentZoneId);
			if ( $currentZone ) {
				WP_CLI::line(sprintf('Current zone is set to %s', $currentZone->name));
			}
		}

		$zones = $this->listZones();

		SelectZone:
		$currentZone->cf_list_zones(true);

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

		Servebolt_CF::getInstance()->storeActiveZoneId($foundZone->id);
		WP_CLI::success(sprintf('Successfully selected zone %s', $foundZone->name));

	}

	/**
	 * Cloudflare API credentials.
	 *
	 * Store credentials in DB.
	 *
	 * @param $args
	 */
	public function cf_set_credentials($args) {
		switch ( count($args) ) {
			case '2':
				list($email, $apiKey) = $args;
				$type        = 'apiKey';
				$verboseType = 'API key';
				$credentials = compact('email', 'apiKey');
				$verboseCredentials = implode(' / ', $credentials);
				break;
			case '1':
				list($token) = $args;
				$type        = 'apiToken';
				$verboseType = 'API token';
				$credentials = $token;
				$verboseCredentials = $credentials;
				break;
			default:
				WP_CLI::error('Could not set credentials.');
		}

		if ( Servebolt_CF::getInstance()->storeCredentials($credentials, $type) ) {
			WP_CLI::success(sprintf('Cloudflare credentials set using %s: %s', $verboseType, $verboseCredentials));
		} else {
			WP_CLI::error(sprintf('Could not set Cloudflare credentials set using %s: %s', $verboseType, $verboseCredentials));
		}

	}

	/**
	 * Activate the correct cache headers for Servebolt Full Page Cache
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
	 *     $ wp servebolt fpc activate --post_types=post,page
	 *
	 */
	public function nginx_activate( $args, $assoc_args ) {
		$this->servebolt_nginx_control('activate', $args, $assoc_args);
	}

	/**
	 * Deactivate the correct cache headers for Servebolt Full Page Cache
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
	 *     $ wp servebolt fpc deactivate --post_types=post,page
	 *
	 */
	public function nginx_deactivate( $args, $assoc_args ) {
		$this->servebolt_nginx_control('deactivate', $args, $assoc_args);
		if ( in_array('status', $assoc_args) ) $this->servebolt_nginx_status($args, $assoc_args);
	}

	/**
	 * Return status of the Servebolt Full Page Cache
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     # Return status of the Servebolt Full Page Cache
	 *     $ wp servebolt fpc status
	 *
	 */
	public function nginx_status( $args, $assoc_args  ) {
		$this->servebolt_nginx_status( $args, $assoc_args  );
	}

	public function cf_purge($args) {
		list($url) = $args;
		WP_CLI::line(sprintf('Purging cache for url %s', $url));
		if ( Servebolt_CF::getInstance()->purgeByUrl($url) ) {
			WP_CLI::success('Cache purged!');
		} else {
			WP_CLI::error('Could not purge cache.');
		}
	}

	public function cf_purge_all() {

		$currentZoneId = Servebolt_CF::getInstance()->getActiveZoneId();
		if ( $currentZoneId && $currentZone = Servebolt_CF::getInstance()->getZoneById($currentZoneId) ) {
			WP_CLI::line(sprintf('Purging all cache for zone %s', $currentZone->name));
		} else {
			WP_CLI::line('Purging all cache');
		}

		if ( Servebolt_CF::getInstance()->purgeAll() ) {
			WP_CLI::success('Cache purged!');
		} else {
			WP_CLI::error('Could not purge cache.');
		}
	}

}
