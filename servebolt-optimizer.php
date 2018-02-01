<?php
/*
Plugin Name: Servebolt Optimizer
Version: 1.2.3
Author: Servebolt
Author URI: https://servebolt.com
Description: A plugin that checks and implements Servebolt Performance best practises for WordPress.
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: servebolt-wp
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'SERVEBOLT_PATH', plugin_dir_url( __FILE__ ) );

/**
 * Loads the class that sets the correct cache headers for NGINX cache
 */
if(!class_exists('Servebolt_Nginx_Fpc')){
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
 * Run Servebolt Optimizer.
 *
 * Add database indexes and convert database tables to modern table types.
 *
 * ## EXAMPLES
 *
 *     $ wp servebolt optimize
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
if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'servebolt optimize', $servebolt_optimize_cmd );
}

