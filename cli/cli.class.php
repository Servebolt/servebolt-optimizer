<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once 'cli-extras.class.php';
require_once 'cli-commands.class.php';

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

		// General
		WP_CLI::add_command( 'servebolt clear-all-settings',         [$this, 'command_clear_all_settings'] );

		// Optimization
		WP_CLI::add_command( 'servebolt db optimize',                [$this, 'command_optimize_database'] );
		WP_CLI::add_command( 'servebolt db fix',                     [$this, 'command_fix'] );
		WP_CLI::add_command( 'servebolt db analyze',                 [$this, 'command_analyze_tables'] );

		// Servebolt Full Page Cache
		WP_CLI::add_command( 'servebolt fpc enable',                 [$this, 'command_nginx_fpc_enable'] );
		WP_CLI::add_command( 'servebolt fpc activate',               [$this, 'command_nginx_fpc_enable'] );
		WP_CLI::add_command( 'servebolt fpc disable',                [$this, 'command_nginx_fpc_disable'] );
		WP_CLI::add_command( 'servebolt fpc deactivate',             [$this, 'command_nginx_fpc_disable'] );
		WP_CLI::add_command( 'servebolt fpc set-post-types',         [$this, 'command_nginx_fpc_set_cache_post_types'] );
		WP_CLI::add_command( 'servebolt fpc set-excluded-posts',     [$this, 'command_nginx_fpc_set_excluded_posts'] );
		WP_CLI::add_command( 'servebolt fpc status',                 [$this, 'command_nginx_fpc_status'] );

		// Cloudflare
		WP_CLI::add_command( 'servebolt cf status',                  [$this, 'command_cf_status'] );
		WP_CLI::add_command( 'servebolt cf activate',                [$this, 'command_cf_enable'] );
		WP_CLI::add_command( 'servebolt cf deactivate',              [$this, 'command_cf_disable'] );

		WP_CLI::add_command( 'servebolt cf cron status',             [$this, 'command_cf_cron_status'] );
		WP_CLI::add_command( 'servebolt cf cron activate',           [$this, 'command_cf_cron_enable'] );
		WP_CLI::add_command( 'servebolt cf cron deactivate',         [$this, 'command_cf_cron_disable'] );

		WP_CLI::add_command( 'servebolt cf get-config',              [$this, 'command_cf_get_config'] );
		WP_CLI::add_command( 'servebolt cf test-api-connection',     [$this, 'command_cf_test_api_connection'] );

		WP_CLI::add_command( 'servebolt cf list-zones',              [$this, 'command_cf_list_zones'] );
		WP_CLI::add_command( 'servebolt cf set-zone',                [$this, 'command_cf_set_zone'] );
		WP_CLI::add_command( 'servebolt cf clear-zone',              [$this, 'command_cf_clear_zone'] );

		WP_CLI::add_command( 'servebolt cf set-credentials',         [$this, 'command_cf_set_credentials'] );
		WP_CLI::add_command( 'servebolt cf clear-credentials',       [$this, 'command_cf_clear_credentials'] );

		WP_CLI::add_command( 'servebolt cf clear-cache-purge-queue', [$this, 'command_cf_clear_cache_purge_queue'] );
		WP_CLI::add_command( 'servebolt cf purge url',               [$this, 'command_cf_purge_url'] );
		WP_CLI::add_command( 'servebolt cf purge post',              [$this, 'command_cf_purge_post'] );
		WP_CLI::add_command( 'servebolt cf purge all',               [$this, 'command_cf_purge_all'] );
	}

}
