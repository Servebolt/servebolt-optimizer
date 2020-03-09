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

// Exit if PHP_MAJOR_VERSION is less than 7
if ( defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION < 7 ) {
    require 'non-php7.php';
    return;
}

// Include Composer dependencies
if ( file_exists(__DIR__ . '/vendor/autoload.php') ) require 'vendor/autoload.php';

// Include general functions
require_once 'functions.php';

// Defines plugin path and URL
define( 'SERVEBOLT_PATH_URL', plugin_dir_url( __FILE__ ) );
define( 'SERVEBOLT_PATH', plugin_dir_path( __FILE__ ) );

// Disable CONCATENATE_SCRIPTS to get rid of some DDOS-attacks
if ( ! defined('CONCATENATE_SCRIPTS') ) {
	define('CONCATENATE_SCRIPTS', false);
}

// Hide the meta tag generator from head and RSS
add_filter('the_generator', '__return_empty_string');
remove_action('wp_head', 'wp_generator');

// Loads the class that sets the correct cache headers for full page cache
if ( ! class_exists('Servebolt_Nginx_FPC') ){
	require_once SERVEBOLT_PATH . 'classes/servebolt-nginx-fpc.class.php';
	$NginxFPC = sb_nginx_fpc();
    if ( $NginxFPC->FPCIsActive()) $NginxFPC->setup();
}

// Add settings-link in plugin list
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'sb_add_settings_link_to_plugin');
function sb_add_settings_link_to_plugin( $links ) {
	$links[] = sprintf('<a href="%s">%s</a>', admin_url( 'options-general.php?page=servebolt-wp' ), sb__('Settings'));
	return $links;
}

// Include the Servebolt Cloudflare class
require_once SERVEBOLT_PATH . 'classes/servebolt-cf.class.php';

// If the admin is loaded, load this plugins interface
if ( is_admin() ) {
	require_once SERVEBOLT_PATH . 'admin/admin-interface.php';
}

// Initialize CLI-commands
if ( class_exists( 'WP_CLI' ) ) {
    require_once __DIR__ . '/cli/cli.class.php';
	Servebolt_CLI::getInstance();
}
