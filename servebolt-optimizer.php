<?php
/*
Plugin Name: Servebolt Optimizer
Version: 1.4
Author: Servebolt
Author URI: https://servebolt.com
Description: A plugin that checks and implements Servebolt Performance best practises for WordPress.
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: servebolt-wp
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once 'admin/optimize-db/transients-cleaner.php';
require_once 'admin/security/wpvuldb.php';

register_activation_hook(__FILE__, 'servebolt_transient_cron');
register_activation_hook(__FILE__, 'servebolt_email_cronstarter');

define( 'SERVEBOLT_PATH_URL', plugin_dir_url( __FILE__ ) );
define( 'SERVEBOLT_PATH', plugin_dir_path( __FILE__ ) );

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

$nginx_switch = get_option('servebolt_fpc_switch');

/**
 * Loads the class that sets the correct cache headers for NGINX cache
 */
if(!class_exists('Servebolt_Nginx_Fpc') && $nginx_switch === 'on'){
	require_once 'class/servebolt-nginx-fpc.class.php';
	Servebolt_Nginx_Fpc::setup();
}

/**
 * If the admin is loaded, load this plugins interface
 */
if(is_admin()){
	require_once 'admin/admin-interface.php';
}

/**
 * We need weekly cron scheduling, so we're adding it!
 * See http://codex.wordpress.org/Plugin_API/Filter_Reference/cron_schedules
 */
add_filter( 'cron_schedules', 'servebolt_add_weekly_cron_schedule' );
function servebolt_add_weekly_cron_schedule( $schedules ) {
	$schedules['weekly'] = array(
		'interval' => 604800, // 1 week in seconds
		'display'  => __( 'Once Weekly' ),
	);

	return $schedules;
}

/**
 * Run Servebolt Optimizer.
 *
 * Add database indexes and convert database tables to modern table types or delete transients.
 *
 * ## EXAMPLES
 *
 *     $ wp servebolt optimize db
 *     Success: Successfully optimized.
 */
$servebolt_optimize_cmd = function( $args ) {
	list( $key ) = $args;

	require_once 'admin/optimize-db/optimize-db.php';

	if ( ! servebolt_optimize_db(TRUE) ) {
		WP_CLI::error( "Optimization failed." );
	} else {
		WP_CLI::success( "Everything OK." );
	}
};

$servebolt_delete_transients = function( $args ) {
	list( $key ) = $args;

	require_once 'admin/optimize-db/transients-cleaner.php';
	servebolt_transient_delete(TRUE);

	if ( ! servebolt_transient_delete(TRUE) ) {
		WP_CLI::error( "Could not delete transients." );
	} else {
		WP_CLI::success( "Deleted transients." );
	}
};

$servebolt_analyze_tables = function( $args ) {
	list( $key ) = $args;

	require_once 'admin/optimize-db/transients-cleaner.php';
	servebolt_analyze_tables( TRUE );

	if ( ! servebolt_analyze_tables(TRUE) ) {
		WP_CLI::error( "Could not analyze tables." );
	} else {
		WP_CLI::success( "Analyzed tables." );
	}
};

if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'servebolt db optimize', $servebolt_optimize_cmd );
	WP_CLI::add_command( 'servebolt db analyze', $servebolt_analyze_tables );
	WP_CLI::add_command( 'servebolt transients delete', $servebolt_delete_transients );
}


