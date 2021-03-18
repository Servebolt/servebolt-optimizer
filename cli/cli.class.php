<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once __DIR__ . '/cli-extras.class.php';
require_once __DIR__ . '/classes/general.class.php';
require_once __DIR__ . '/classes/optimizations.class.php';
require_once __DIR__ . '/classes/fpc.class.php';
require_once __DIR__ . '/classes/cloudflare-cache.class.php';
require_once __DIR__ . '/classes/general-settings.class.php';
require_once __DIR__ . '/classes/cloudflare-image-resize.class.php';

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
     * CLI classes.
     *
     * @var
     */
    private $general, $optimizations, $fpc, $cf_cache, $cf_image_resize, $general_settings;

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
	    $this->initialize_cli_classes();
		$this->register_commands();
	}

    /**
     * Initialize the classes that executes the different CLI commands.
     */
	private function initialize_cli_classes() {
        $this->general          = new Servebolt_CLI_General;
        $this->optimizations    = new Servebolt_CLI_Optimizations;
        $this->fpc              = new Servebolt_CLI_FPC;
        $this->cf_cache         = new Servebolt_CLI_Cloudflare_Cache; // Legacy
        $this->general_settings = new Servebolt_CLI_General_Settings;
        $this->cf_image_resize  = new Servebolt_CLI_Cloudflare_Image_Resize;
    }

    public function cf_legacy_message() {
        WP_CLI::line('This command is outdated. Please use "wp servebolt cache" instead.');
    }

    public function cache_message() {
        WP_CLI::line('This command is WIP and will replace the "wp servebolt cf"-command.');
    }

	/**
	 * Register WP CLI commands.
	 */
	private function register_commands() {

		// General
		WP_CLI::add_command( 'servebolt delete-all-settings',        [$this->general, 'command_delete_all_settings'] );

		// Optimization
		WP_CLI::add_command( 'servebolt db optimize',                [$this->optimizations, 'command_optimize_database'] );
		WP_CLI::add_command( 'servebolt db fix',                     [$this->optimizations, 'command_fix'] );
		WP_CLI::add_command( 'servebolt db analyze',                 [$this->optimizations, 'command_analyze_tables'] );

		// Servebolt Full Page Cache
		WP_CLI::add_command( 'servebolt fpc activate',               [$this->fpc, 'command_nginx_fpc_enable'] );
		WP_CLI::add_command( 'servebolt fpc deactivate',             [$this->fpc, 'command_nginx_fpc_disable'] );
		WP_CLI::add_command( 'servebolt fpc status',                 [$this->fpc, 'command_nginx_fpc_status'] );

		WP_CLI::add_command( 'servebolt fpc post-types get',         [$this->fpc, 'command_nginx_fpc_get_cache_post_types'] );
		WP_CLI::add_command( 'servebolt fpc post-types set',         [$this->fpc, 'command_nginx_fpc_set_cache_post_types'] );
		WP_CLI::add_command( 'servebolt fpc post-types clear',       [$this->fpc, 'command_nginx_fpc_clear_cache_post_types'] );

		WP_CLI::add_command( 'servebolt fpc excluded-posts get',     [$this->fpc, 'command_nginx_fpc_get_excluded_posts'] );
		WP_CLI::add_command( 'servebolt fpc excluded-posts set',     [$this->fpc, 'command_nginx_fpc_set_excluded_posts'] );
		WP_CLI::add_command( 'servebolt fpc excluded-posts clear',   [$this->fpc, 'command_nginx_fpc_clear_excluded_posts'] );

		// Cache control
        WP_CLI::add_command( 'servebolt cache',                      [$this, 'cache_message'] );

		// Cloudflare Cache (legacy)
		WP_CLI::add_command( 'servebolt cf setup',                   [$this, 'cf_legacy_message'] );

		WP_CLI::add_command( 'servebolt cf status',                  [$this, 'cf_legacy_message'] );
		WP_CLI::add_command( 'servebolt cf activate',                [$this, 'cf_legacy_message'] );
		WP_CLI::add_command( 'servebolt cf deactivate',              [$this, 'cf_legacy_message'] );

		WP_CLI::add_command( 'servebolt cf config get',              [$this, 'cf_legacy_message'] );
		WP_CLI::add_command( 'servebolt cf config set',              [$this, 'cf_legacy_message'] );
		WP_CLI::add_command( 'servebolt cf config clear',            [$this, 'cf_legacy_message'] );

		WP_CLI::add_command( 'servebolt cf api test',                [$this, 'cf_legacy_message'] );
		WP_CLI::add_command( 'servebolt cf api credentials get',     [$this, 'cf_legacy_message'] );
		WP_CLI::add_command( 'servebolt cf api credentials set',     [$this, 'cf_legacy_message'] );
		WP_CLI::add_command( 'servebolt cf api credentials clear',   [$this, 'cf_legacy_message'] );

		WP_CLI::add_command( 'servebolt cf zone list',               [$this, 'cf_legacy_message'] );
		WP_CLI::add_command( 'servebolt cf zone get',                [$this, 'cf_legacy_message'] );
		WP_CLI::add_command( 'servebolt cf zone set',                [$this, 'cf_legacy_message'] );
		WP_CLI::add_command( 'servebolt cf zone clear',              [$this, 'cf_legacy_message'] );

		WP_CLI::add_command( 'servebolt cf purge type',              [$this, 'cf_legacy_message'] );
		WP_CLI::add_command( 'servebolt cf purge status',            [$this, 'cf_legacy_message'] );

		WP_CLI::add_command( 'servebolt cf purge queue',             [$this, 'cf_legacy_message'] );
		WP_CLI::add_command( 'servebolt cf purge clear-queue',       [$this, 'cf_legacy_message'] );
		WP_CLI::add_command( 'servebolt cf purge url',               [$this, 'cf_legacy_message'] );
		WP_CLI::add_command( 'servebolt cf purge post',              [$this, 'cf_legacy_message'] );
		WP_CLI::add_command( 'servebolt cf purge all',               [$this, 'cf_legacy_message'] );

        // General settings
        WP_CLI::add_command( 'servebolt general-settings list',      [$this->general_settings, 'command_general_settings_list'] );
        WP_CLI::add_command( 'servebolt general-settings get',       [$this->general_settings, 'command_general_settings_get'] );
        WP_CLI::add_command( 'servebolt general-settings set',       [$this->general_settings, 'command_general_settings_set'] );

        // Cloudflare Image Resize
        WP_CLI::add_command( 'servebolt cf-image-resize status',     [$this->cf_image_resize, 'command_cf_image_resize_status'] );
        WP_CLI::add_command( 'servebolt cf-image-resize activate',   [$this->cf_image_resize, 'command_cf_image_resize_enable'] );
        WP_CLI::add_command( 'servebolt cf-image-resize deactivate', [$this->cf_image_resize, 'command_cf_image_resize_disable'] );

	}

}
