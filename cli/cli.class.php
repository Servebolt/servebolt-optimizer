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
		WP_CLI::add_command( 'servebolt delete-all-settings',         [$this, 'command_delete_all_settings'] );

		// Optimization
		WP_CLI::add_command( 'servebolt db optimize',                [$this, 'command_optimize_database'] );
		WP_CLI::add_command( 'servebolt db fix',                     [$this, 'command_fix'] );
		WP_CLI::add_command( 'servebolt db analyze',                 [$this, 'command_analyze_tables'] );

		// Servebolt Full Page Cache
		WP_CLI::add_command( 'servebolt fpc activate',               [$this, 'command_nginx_fpc_enable'] );
		WP_CLI::add_command( 'servebolt fpc deactivate',             [$this, 'command_nginx_fpc_disable'] );
		WP_CLI::add_command( 'servebolt fpc status',                 [$this, 'command_nginx_fpc_status'] );

		WP_CLI::add_command( 'servebolt fpc post-types get',         [$this, 'command_nginx_fpc_get_cache_post_types'] );
		WP_CLI::add_command( 'servebolt fpc post-types set',         [$this, 'command_nginx_fpc_set_cache_post_types'] );
		WP_CLI::add_command( 'servebolt fpc post-types clear',       [$this, 'command_nginx_fpc_clear_cache_post_types'] );

		WP_CLI::add_command( 'servebolt fpc excluded-posts get',     [$this, 'command_nginx_fpc_get_excluded_posts'] );
		WP_CLI::add_command( 'servebolt fpc excluded-posts set',     [$this, 'command_nginx_fpc_set_excluded_posts'] );
		WP_CLI::add_command( 'servebolt fpc excluded-posts clear',   [$this, 'command_nginx_fpc_clear_excluded_posts'] );

		// Cloudflare
		//WP_CLI::add_command( 'servebolt cf setup',                   [$this, 'command_cf_setup'] ); // TODO: Make interactive setup command
		WP_CLI::add_command( 'servebolt cf status',                  [$this, 'command_cf_status'] );
		WP_CLI::add_command( 'servebolt cf activate',                [$this, 'command_cf_enable'] );
		WP_CLI::add_command( 'servebolt cf deactivate',              [$this, 'command_cf_disable'] );
		WP_CLI::add_command( 'servebolt cf get-config',              [$this, 'command_cf_get_config'] );

		WP_CLI::add_command( 'servebolt cf api test',                [$this, 'command_cf_test_api_connection'] );
		WP_CLI::add_command( 'servebolt cf api credentials get',     [$this, 'command_cf_get_credentials'] );
		WP_CLI::add_command( 'servebolt cf api credentials set',     [$this, 'command_cf_set_credentials'] );
		WP_CLI::add_command( 'servebolt cf api credentials clear',   [$this, 'command_cf_clear_credentials'] );

		WP_CLI::add_command( 'servebolt cf zone list',               [$this, 'command_cf_list_zones'] );
		WP_CLI::add_command( 'servebolt cf zone get',                [$this, 'command_cf_get_zone'] );
		WP_CLI::add_command( 'servebolt cf zone set',                [$this, 'command_cf_set_zone'] );
		WP_CLI::add_command( 'servebolt cf zone clear',              [$this, 'command_cf_clear_zone'] );

		WP_CLI::add_command( 'servebolt cf purge type',              [$this, 'command_cf_set_purge_type'] );
		WP_CLI::add_command( 'servebolt cf purge status',            [$this, 'command_cf_purge_status'] );

		WP_CLI::add_command( 'servebolt cf purge clear-queue',       [$this, 'command_cf_clear_cache_purge_queue'] );
		WP_CLI::add_command( 'servebolt cf purge url',               [$this, 'command_cf_purge_url'] );
		WP_CLI::add_command( 'servebolt cf purge post',              [$this, 'command_cf_purge_post'] );
		WP_CLI::add_command( 'servebolt cf purge all',               [$this, 'command_cf_purge_all'] );

		/*
		WP_CLI::add_command( 'servebolt cf purge cron status',       [$this, 'command_cf_cron_status'] );
		WP_CLI::add_command( 'servebolt cf purge cron activate',     [$this, 'command_cf_cron_enable'] );
		WP_CLI::add_command( 'servebolt cf purge cron deactivate',   [$this, 'command_cf_cron_disable'] );
		*/
	}

}
