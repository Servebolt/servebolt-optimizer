<?php
/*
Plugin Name: Servebolt Optimizer
Version: 1.6.4-dev
Author: Servebolt
Author URI: https://servebolt.com
Description: A plugin that implements Servebolt Security & Performance best practises for WordPress.
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: servebolt-wp
*/


if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Exit if PHP_MAJOR_VERSION is less than 7
if (defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION < 7) {
    require 'non-php7.php';
    exit;
}

define( 'SERVEBOLT_PATH_URL', plugin_dir_url( __FILE__ ) );
define( 'SERVEBOLT_PATH', plugin_dir_path( __FILE__ ) );

require 'vendor/autoload.php';

// Disable CONCATENATE_SCRIPTS to get rid of some ddos attacks
if(! defined( 'CONCATENATE_SCRIPTS')) {
	define( 'CONCATENATE_SCRIPTS', false);
}

// hide the meta tag generator from head and rss
function servebolt_optimizer_disable_version() {
	return '';
}
add_filter('the_generator','servebolt_optimizer_disable_version');
remove_action('wp_head', 'wp_generator');



/**
 * Loads the class that sets the correct cache headers for Full Page Cache. Do not load if this is a rest request.
 */
$nginx_switch = get_option('servebolt_fpc_switch');
if( !class_exists( 'Servebolt_Nginx_Fpc' ) && defined( 'REST_REQUEST' ) {
	require_once SERVEBOLT_PATH . 'class/servebolt-nginx-fpc.class.php';
    if( $nginx_switch === 'on' ) Servebolt_Nginx_Fpc::setup();
}

/**
 * Require the Class handling our Cloudflare stuff
 */
require_once SERVEBOLT_PATH . 'class/servebolt-cf-purge.class.php';

/**
 * If the admin is loaded, load this plugins interface and Cloudflare integration
 */
require_once SERVEBOLT_PATH . 'admin/admin-interface.php';
Servebolt_cloudflare::setup();

/**
 * We need weekly cron scheduling, so we're adding it!
 * See http://codex.wordpress.org/Plugin_API/Filter_Reference/cron_schedules
 */
add_filter( 'cron_schedules', 'servebolt_add_cron_schedule' );
function servebolt_add_cron_schedule( $schedules ) {
	$schedules['weekly'] = array(
		'interval' => 604800, // 1 week in seconds
		'display'  => __( 'Once Weekly' ),
	);
	$schedules['every_minute'] = array(
		'interval' => 60, // 1 minute in seconds
		'display'  => __( 'Once a minute' ),
	);
	return $schedules;
}

if ( class_exists( 'WP_CLI' ) ) {
    require_once ('cli.php');
    WP_CLI::add_command( 'servebolt db optimize', $servebolt_optimize_cmd ); // TODO: Remove in v1.7
	WP_CLI::add_command( 'servebolt db fix', $servebolt_optimize_cmd );
	WP_CLI::add_command( 'servebolt db analyze', $servebolt_analyze_tables );
    WP_CLI::add_command( 'servebolt fpc activate', $servebolt_cli_nginx_activate );
    WP_CLI::add_command( 'servebolt fpc deactivate', $servebolt_cli_nginx_deactivate );
	WP_CLI::add_command( 'servebolt fpc status', $servebolt_cli_nginx_status );
	WP_CLI::add_command( 'servebolt cf purge', $servebolt_cli_nginx_status );
	WP_CLI::add_command( 'servebolt cf config set', $servebolt_cli_cf_config_set );
	WP_CLI::add_command( 'servebolt cf config get', $servebolt_cli_cf_config_get );
	WP_CLI::add_command( 'servebolt cf purge', $servebolt_cli_cf_purge );
}


