<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once __DIR__ . '/cli-extras.class.php';
require_once __DIR__ . '/classes/general.class.php';
require_once __DIR__ . '/classes/cloudflare-cache.class.php';
//require_once __DIR__ . '/classes/cloudflare-image-resize.class.php';
require_once __DIR__ . '/classes/optimizations.class.php';
require_once __DIR__ . '/classes/fpc.class.php';

/**
 * Class Servebolt_CLI
 * @package Servebolt
 *
 * Does all the WP CLI handling.
 */
class Servebolt_CLI {

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
		$this->register_commands();
	}

	/**
	 * Register WP CLI commands.
	 */
	private function register_commands() {

		$general         = new Servebolt_CLI_General;
		$optimizations   = new Servebolt_CLI_Optimizations;
		$fpc             = new Servebolt_CLI_FPC;
		$cf_cache        = new Servebolt_CLI_Cloudflare_Cache;
		//$cf_image_resize = new Servebolt_CLI_Cloudflare_Image_Resize;

		// General
		WP_CLI::add_command( 'servebolt delete-all-settings',        [$general, 'command_delete_all_settings'] );

		// Optimization
		WP_CLI::add_command( 'servebolt db optimize',                [$optimizations, 'command_optimize_database'] );
		WP_CLI::add_command( 'servebolt db fix',                     [$optimizations, 'command_fix'] );
		WP_CLI::add_command( 'servebolt db analyze',                 [$optimizations, 'command_analyze_tables'] );

		// Servebolt Full Page Cache
		WP_CLI::add_command( 'servebolt fpc activate',               [$fpc, 'command_nginx_fpc_enable'] );
		WP_CLI::add_command( 'servebolt fpc deactivate',             [$fpc, 'command_nginx_fpc_disable'] );
		WP_CLI::add_command( 'servebolt fpc status',                 [$fpc, 'command_nginx_fpc_status'] );

		WP_CLI::add_command( 'servebolt fpc post-types get',         [$fpc, 'command_nginx_fpc_get_cache_post_types'] );
		WP_CLI::add_command( 'servebolt fpc post-types set',         [$fpc, 'command_nginx_fpc_set_cache_post_types'] );
		WP_CLI::add_command( 'servebolt fpc post-types clear',       [$fpc, 'command_nginx_fpc_clear_cache_post_types'] );

		WP_CLI::add_command( 'servebolt fpc excluded-posts get',     [$fpc, 'command_nginx_fpc_get_excluded_posts'] );
		WP_CLI::add_command( 'servebolt fpc excluded-posts set',     [$fpc, 'command_nginx_fpc_set_excluded_posts'] );
		WP_CLI::add_command( 'servebolt fpc excluded-posts clear',   [$fpc, 'command_nginx_fpc_clear_excluded_posts'] );


		// Cloudflare Cache
		WP_CLI::add_command( 'servebolt cf setup',                   [$cf_cache, 'command_cf_setup'] );

		WP_CLI::add_command( 'servebolt cf status',                  [$cf_cache, 'command_cf_status'] );
		WP_CLI::add_command( 'servebolt cf activate',                [$cf_cache, 'command_cf_enable'] );
		WP_CLI::add_command( 'servebolt cf deactivate',              [$cf_cache, 'command_cf_disable'] );

		WP_CLI::add_command( 'servebolt cf config get',              [$cf_cache, 'command_cf_get_config'] );
		WP_CLI::add_command( 'servebolt cf config set',              [$cf_cache, 'command_cf_set_config'] );
		WP_CLI::add_command( 'servebolt cf config clear',            [$cf_cache, 'command_cf_clear_config'] );

		WP_CLI::add_command( 'servebolt cf api test',                [$cf_cache, 'command_cf_test_api_connection'] );
		WP_CLI::add_command( 'servebolt cf api credentials get',     [$cf_cache, 'command_cf_get_credentials'] );
		WP_CLI::add_command( 'servebolt cf api credentials set',     [$cf_cache, 'command_cf_set_credentials'] );
		WP_CLI::add_command( 'servebolt cf api credentials clear',   [$cf_cache, 'command_cf_clear_credentials'] );

		WP_CLI::add_command( 'servebolt cf zone list',               [$cf_cache, 'command_cf_list_zones'] );
		WP_CLI::add_command( 'servebolt cf zone get',                [$cf_cache, 'command_cf_get_zone'] );
		WP_CLI::add_command( 'servebolt cf zone set',                [$cf_cache, 'command_cf_set_zone'] );
		WP_CLI::add_command( 'servebolt cf zone clear',              [$cf_cache, 'command_cf_clear_zone'] );

		WP_CLI::add_command( 'servebolt cf purge type',              [$cf_cache, 'command_cf_set_purge_type'] );
		WP_CLI::add_command( 'servebolt cf purge status',            [$cf_cache, 'command_cf_purge_status'] );

		WP_CLI::add_command( 'servebolt cf purge clear-queue',       [$cf_cache, 'command_cf_clear_cache_purge_queue'] );
		WP_CLI::add_command( 'servebolt cf purge url',               [$cf_cache, 'command_cf_purge_url'] );
		WP_CLI::add_command( 'servebolt cf purge post',              [$cf_cache, 'command_cf_purge_post'] );
		WP_CLI::add_command( 'servebolt cf purge all',               [$cf_cache, 'command_cf_purge_all'] );

		// Cloudflare Image Resize (WIP)
		//WP_CLI::add_command( 'servebolt cf-image status',            [$cf_image_resize, 'command_cf_status'] );
		//WP_CLI::add_command( 'servebolt cf-image activate',          [$cf_image_resize, 'command_cf_enable'] );
		//WP_CLI::add_command( 'servebolt cf-image deactivate',        [$cf_image_resize, 'command_cf_disable'] );

	}

}
