<?php
/*
Plugin Name: Servebolt Optimizer
Version: 2.0.4
Author: Servebolt
Author URI: https://servebolt.com
Description: A plugin that implements Servebolt Security & Performance best practises for WordPress.
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: servebolt-wp
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Defines plugin path and URL
define( 'SERVEBOLT_BASENAME', plugin_basename(__FILE__) );
define( 'SERVEBOLT_PATH_URL', plugin_dir_url( __FILE__ ) );
define( 'SERVEBOLT_PATH', plugin_dir_path( __FILE__ ) );

// Abort and display admin notice if PHP_MAJOR_VERSION is less than 7
if ( defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION < 7 ) {
    require SERVEBOLT_PATH . 'non-php7.php';
    return;
}

// Check if we got composer files
if ( ! file_exists(SERVEBOLT_PATH . 'vendor/autoload.php') ) {
	require SERVEBOLT_PATH . 'composer-missing.php';
	return;
}

// Include general functions
require_once SERVEBOLT_PATH . 'functions.php';

// Add minor improvements
sb_generic_optimizations();

// We don't always need all files
if ( is_admin() || sb_is_cli() || sb_is_cron() ) {

	// Include Composer dependencies
	require SERVEBOLT_PATH . 'vendor/autoload.php';

	// Make sure we dont API credentials in clear text.
	require_once SERVEBOLT_PATH . 'classes/sb-option-encryption.php';

	// Include the Servebolt Cloudflare class
	require_once SERVEBOLT_PATH . 'classes/cloudflare-cache/sb-cf.php';

}

// Loads the class that sets the correct cache headers for full page cache
if ( ! class_exists('Servebolt_Nginx_FPC') ){
	require_once SERVEBOLT_PATH . 'classes/nginx-fpc/sb-nginx-fpc.php';
	sb_nginx_fpc()->setup();
}

// Initialize image resizing
if ( sb_feature_active('cf_image_resize') && ( sb_cf_image_resize_control() )->resizing_is_active() ) {
	require_once SERVEBOLT_PATH . 'classes/cloudflare-image-resize/cloudflare-image-resizing.php';
}

// Register cron schedule and cache purge event
require_once SERVEBOLT_PATH . 'classes/cloudflare-cache/sb-cf-cron.php';

if ( is_admin() ) {

	// Register cache purge event when saving post
	require_once SERVEBOLT_PATH . 'classes/cloudflare-cache/sb-cf-post-save-action.php';

	// Load this plugins interface
	require_once SERVEBOLT_PATH . 'admin/admin-interface.php';

}

// Initialize CLI-commands
if ( sb_is_cli() ) {
    require_once SERVEBOLT_PATH . 'cli/cli.class.php';
	Servebolt_CLI::get_instance();
}
