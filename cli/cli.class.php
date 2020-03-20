<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once 'cli-methods.class.php';
require_once 'cli-extras.class.php';


/**
 * Class Servebolt_CLI
 * @package Servebolt
 *
 * Does all the WP CLI handling.
 */
class Servebolt_CLI extends Servebolt_CLI_Commands {

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
		WP_CLI::add_command( 'servebolt cf test-api-connection',   [$this, 'cf_test_api_connection'] );
		WP_CLI::add_command( 'servebolt cf list-zones',            [$this, 'cf_list_zones'] );
		WP_CLI::add_command( 'servebolt cf set-zone',              [$this, 'cf_set_zone'] );
		WP_CLI::add_command( 'servebolt cf clear-zone',            [$this, 'cf_clear_zone'] );
		WP_CLI::add_command( 'servebolt cf set-credentials',       [$this, 'cf_set_credentials'] );
		WP_CLI::add_command( 'servebolt cf clear-credentials',     [$this, 'cf_clear_credentials'] );
		WP_CLI::add_command( 'servebolt cf purge url',             [$this, 'cf_purge_url'] );
		WP_CLI::add_command( 'servebolt cf purge post',            [$this, 'cf_purge_post'] );
		WP_CLI::add_command( 'servebolt cf purge all',             [$this, 'cf_purge_all'] );
	}

}
