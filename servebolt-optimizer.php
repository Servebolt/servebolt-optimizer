<?php
/*
Plugin Name: Servebolt Optimizer
Version: 2.0
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

// Exit if PHP_MAJOR_VERSION is less than 7
if ( defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION < 7 ) {
    require SERVEBOLT_PATH . 'non-php7.php';
    return;
}

// Include Composer dependencies
if ( ! file_exists(SERVEBOLT_PATH . 'vendor/autoload.php') ) {
	require SERVEBOLT_PATH . 'composer-missing.php';
	return;
}
require SERVEBOLT_PATH . 'vendor/autoload.php';

// Include general functions
require_once SERVEBOLT_PATH . 'functions.php';

// Disable CONCATENATE_SCRIPTS to get rid of some DDOS-attacks
if ( ! defined('CONCATENATE_SCRIPTS') ) {
	define('CONCATENATE_SCRIPTS', false);
}

// Hide the meta tag generator from head and RSS
add_filter('the_generator', '__return_empty_string');
remove_action('wp_head', 'wp_generator');

// Make sure we dont API credentials in clear text.
require_once SERVEBOLT_PATH . 'classes/sb-option-encryption.php';
new SB_Option_Encryption;

// Loads the class that sets the correct cache headers for full page cache
if ( ! class_exists('Servebolt_Nginx_FPC') ){
	require_once SERVEBOLT_PATH . 'classes/sb-nginx-fpc.php';
    if ( sb_nginx_fpc()->fpc_is_active()) {
	    sb_nginx_fpc()->setup();
    }
}

// Invoke the Servebolt Cloudflare class
require_once SERVEBOLT_PATH . 'classes/sb-cf.php';
sb_cf();

if ( is_admin() ) {

	// Register cache actions (cache queue, cache purge trigger)
	require_once SERVEBOLT_PATH . 'classes/sb-cf-cache-action.php';
	new CF_Cache_Action;

	// Load this plugins interface
	require_once SERVEBOLT_PATH . 'admin/admin-interface.php';

}

// Initialize CLI-commands
if ( class_exists( 'WP_CLI' ) ) {
    require_once SERVEBOLT_PATH . 'cli/cli.class.php';
	Servebolt_CLI::get_instance();
}
