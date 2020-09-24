<?php
/*
Plugin Name: Servebolt Optimizer
Version: 2.1
Author: Servebolt
Author URI: https://servebolt.com
Description: A plugin that implements Servebolt Security & Performance best practises for WordPress.
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: servebolt-wp
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Defines plugin paths and URLs
define( 'SERVEBOLT_BASENAME', plugin_basename(__FILE__) );
define( 'SERVEBOLT_PATH_URL', plugin_dir_url( __FILE__ ) );
define( 'SERVEBOLT_PATH', plugin_dir_path( __FILE__ ) );
define( 'SERVEBOLT_OPTIMIZER_USE_COMPOSER', false ); // Currently not in use doe to lack of need for dependencies

// Abort and display WP admin notice if PHP_MAJOR_VERSION is less than 7
if ( defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION < 7 ) {
    require SERVEBOLT_PATH . 'non-php7.php';
    return;
}

// Check if we got composer files, bail if not
if ( SERVEBOLT_OPTIMIZER_USE_COMPOSER === true && ! file_exists(SERVEBOLT_PATH . 'vendor/autoload.php') ) {
	require SERVEBOLT_PATH . 'composer-missing.php';
	return;
}

// Include general functions
require_once SERVEBOLT_PATH . 'functions.php';

// Register events for activation and deactivation of this plugin
register_activation_hook(__FILE__, 'sb_activate_plugin');
register_deactivation_hook(__FILE__, 'sb_deactivate_plugin');

// Add various improvements/optimizations
sb_generic_optimizations();

// We don't always need all files - only in WP Admin, in CLI-mode or when running the WP Cron.
if ( is_admin() || sb_is_cli() || sb_is_cron() ) {

	if ( SERVEBOLT_OPTIMIZER_USE_COMPOSER === true ) {
		// Include Composer dependencies
		require SERVEBOLT_PATH . 'vendor/autoload.php';
	}

	// Make sure we dont API credentials in clear text.
	require_once SERVEBOLT_PATH . 'classes/sb-option-encryption.php';

	// Include the Servebolt Cloudflare class
	require_once SERVEBOLT_PATH . 'classes/cloudflare-cache/sb-cf-cache.php';

}

// Loads the class that sets the correct cache headers for the Servebolt full page cache
if ( ! class_exists('Servebolt_Nginx_FPC') ){
	require_once SERVEBOLT_PATH . 'classes/nginx-fpc/sb-nginx-fpc.php';
	sb_nginx_fpc()->setup();
}

// Initialize image resizing
if ( sb_feature_active('cf_image_resize') ) {
	require_once SERVEBOLT_PATH . 'classes/cloudflare-image-resize/cloudflare-image-resizing.php';
	( new Cloudflare_Image_Resize )->init();
}

// Register cron schedule and cache purge event
require_once SERVEBOLT_PATH . 'classes/cloudflare-cache/sb-cf-cache-cron-handle.php';

// Register cache purge event for various hooks
require_once SERVEBOLT_PATH . 'classes/cloudflare-cache/sb-cf-cache-purge-actions.php';

// Load this admin bar interface
require_once SERVEBOLT_PATH . 'admin/admin-bar-interface.php';

// Load admin assets (some admin assets is needed front-end when logged in, for the admin bar)
require_once SERVEBOLT_PATH . 'admin/admin-assets.php';

// Only load the plugin interface in WP Admin
if ( is_admin() ) {

	// Load this plugins interface
	require_once SERVEBOLT_PATH . 'admin/admin-interface.php';

}

// Only front-end
if ( ! is_admin() ) {

    // Response for when we query how many pages is needed in an archive.
    sb_max_num_pages_query_callback();

    // Feature to automatically version all enqueued script/style-tags
    if ( sb_feature_active('sb_asset_auto_version') ) {
        require_once SERVEBOLT_PATH . 'classes/sb-asset-auto-version.class.php';
    }

}

// Initialize CLI-commands
if ( sb_is_cli() ) {
    require_once SERVEBOLT_PATH . 'cli/cli.class.php';
	Servebolt_CLI::get_instance();
}
