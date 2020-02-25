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

// Disable CONCATENATE_SCRIPTS to get rid of some ddos attacks
if ( ! defined('CONCATENATE_SCRIPTS') ) {
	define('CONCATENATE_SCRIPTS', false);
}

// Hide the meta tag generator from head and RSS
add_filter('the_generator', '__return_empty_string');
remove_action('wp_head', 'wp_generator');

// Loads the class that sets the correct cache headers for Full Page Cache
if ( ! class_exists('Servebolt_Nginx_Fpc') ){
	require_once SERVEBOLT_PATH . 'class/servebolt-nginx-fpc.class.php';
	$nginx_switch = sb_get_option('fpc_switch');
    if ( $nginx_switch === 'on' ) Servebolt_Nginx_Fpc::setup();
}

// Include the Servebolt Cloudflare class.
require_once SERVEBOLT_PATH . 'class/servebolt-cf.class.php';

// If the admin is loaded, load this plugins interface
if ( is_admin() ) {
	require_once SERVEBOLT_PATH . 'admin/admin-interface.php';
}

/**
 * We need weekly cron scheduling, so we're adding it!
 * See http://codex.wordpress.org/Plugin_API/Filter_Reference/cron_schedules
 */
add_filter('cron_schedules', function( $schedules ) {
	$schedules['weekly'] = [
		'interval' => 604800, // 1 week in seconds
		'display'  => sb__('Once Weekly'),
	];
	return $schedules;
});

// Initialize CLI-commands.
if ( class_exists( 'WP_CLI' ) ) {
    require_once __DIR__ . '/cli/cli.class.php';
	$s = Servebolt_CLI::getInstance();
	WP_CLI::add_command( 'servebolt db optimize',           [$s, 'optimize'] ); // TODO: Remove in v1.7
	WP_CLI::add_command( 'servebolt db fix',                [$s, 'optimize'] );
	WP_CLI::add_command( 'servebolt db analyze',            [$s, 'analyze_tables'] );

    WP_CLI::add_command( 'servebolt fpc activate',          [$s, 'nginx_activate'] );
    WP_CLI::add_command( 'servebolt fpc deactivate',        [$s, 'nginx_deactivate'] );
    WP_CLI::add_command( 'servebolt fpc status',            [$s, 'nginx_status'] );

	WP_CLI::add_command( 'servebolt cf list-zones',         [$s, 'cf_list_zones'] );
	WP_CLI::add_command( 'servebolt cf set-zone',           [$s, 'cf_set_zone'] );
	WP_CLI::add_command( 'servebolt cf set-credentials',    [$s, 'cf_set_credentials'] );
	WP_CLI::add_command( 'servebolt cf purge',              [$s, 'cf_purge'] );
	WP_CLI::add_command( 'servebolt cf purge-all',          [$s, 'cf_purge_all'] );
}
