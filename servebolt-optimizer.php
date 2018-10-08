<?php
/*
Plugin Name: Servebolt Optimizer
Version: 1.5.1
Author: Servebolt
Author URI: https://servebolt.com
Description: A plugin that implements Servebolt Security & Performance best practises for WordPress.
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: servebolt-wp
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'SERVEBOLT_PATH_URL', plugin_dir_url( __FILE__ ) );
define( 'SERVEBOLT_PATH', plugin_dir_path( __FILE__ ) );

require_once SERVEBOLT_PATH . 'admin/optimize-db/transients-cleaner.php';
require_once SERVEBOLT_PATH . 'admin/security/wpvuldb.php';


register_activation_hook(__FILE__, 'servebolt_transient_cron');
register_activation_hook(__FILE__, 'servebolt_email_cronstarter');


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
	require_once SERVEBOLT_PATH . 'class/servebolt-nginx-fpc.class.php';
	Servebolt_Nginx_Fpc::setup();
}

/**
 * If the admin is loaded, load this plugins interface
 */
if(is_admin()){
	require_once SERVEBOLT_PATH . 'admin/admin-interface.php';
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

	require_once SERVEBOLT_PATH . 'admin/optimize-db/optimize-db.php';

	if ( ! servebolt_optimize_db(TRUE) ) {
		WP_CLI::success( "Optimization done" );
	} else {
		WP_CLI::warning( "Everything OK. No optimization to do." );
	}
};

$servebolt_delete_transients = function( $args ) {
	list( $key ) = $args;

	require_once SERVEBOLT_PATH . 'admin/optimize-db/transients-cleaner.php';
	servebolt_transient_delete(TRUE);

	if ( ! servebolt_transient_delete(TRUE) ) {
		WP_CLI::error( "Could not delete transients." );
	} else {
		WP_CLI::success( "Deleted transients." );
	}
};

$servebolt_analyze_tables = function( $args ) {
	list( $key ) = $args;

	require_once SERVEBOLT_PATH . 'admin/optimize-db/transients-cleaner.php';
	servebolt_analyze_tables( TRUE );

	if ( ! servebolt_analyze_tables(TRUE) ) {
		WP_CLI::error( "Could not analyze tables." );
	} else {
		WP_CLI::success( "Analyzed tables." );
	}
};

$servebolt_cli_nginx = function( $args, $assoc_args ) {
    if($args[0] === 'status'):
        servebolt_nginx_status();
    elseif($args[0] === 'activate' || $args[0] === 'deactivate'):
        servebolt_nginx_control($args[0], $assoc_args);
    endif;

};

function servebolt_nginx_status(){
    if(is_multisite()):
        $sites = get_sites();
        $sites_status = array();
        foreach ($sites as $site){
            $id = $site->blog_id;
            switch_to_blog($id);
            $status = get_option('servebolt_fpc_switch');
            $posttypes = get_option('servebolt_fpc_settings');

            $posttypes_keys = array_keys($posttypes);
            $posttypes_string = implode(',',$posttypes_keys);

            if($status !== 'on'):
                $status = 'enabled';
            else:
                $status = 'disabled';
            endif;

            $url = get_site_url($id);
            $site_status = array();
            $site_status['URL'] = $url;
            $site_status['STATUS'] = $status;
            $site_status['POST_TYPES'] = $posttypes_string;
            $sites_status[] = $site_status;
            restore_current_blog();
        }
        WP_CLI\Utils\format_items( 'table', $sites_status , array('URL', 'STATUS', 'POST_TYPES'));
    else:
        $status = get_option('servebolt_fpc_switch');
        WP_CLI::success(sprintf(__('NGINX cache is %s'), $status));
    endif;
}

function servebolt_nginx_control($state, $assoc_args){
    $switch = '';
    if($state === 'activate') {
        $switch = 'on';
    }elseif($state === 'deactivate'){
        $switch = '';
    }

    if(is_multisite() && array_key_exists('all', $assoc_args)){
        $sites = get_sites();
        $sites_status = array();
        foreach ($sites as $site) {
            $id = $site->blog_id;
            switch_to_blog($id);
            $url = get_site_url($id);
            $status = get_option('servebolt_fpc_switch');
            if($status !== $switch):
                update_option('servebolt_fpc_switch', $switch);
                WP_CLI::success(sprintf(__('NGINX Cache %1$sd on %2$s'), $state, esc_url($url)));
            elseif($status === $switch):
                WP_CLI::warning(sprintf(__('NGINX Cache already %1$sd on %2$s'), $state, esc_url($url)));
            endif;
            if($assoc_args['posttypes']) servebolt_nginx_set_posttypes($assoc_args['posttypes'], $id);
            restore_current_blog();
        }
    }else{
        $status = get_option('servebolt_fpc_switch');
        if($status !== $switch):
            update_option('servebolt_fpc_switch', $switch);
            WP_CLI::success(sprintf(__('NGINX Cache %1$sd'), $state));
        elseif($status === $switch):
            WP_CLI::warning(sprintf(__('NGINX Cache already %1$sd'), $state));
        endif;
    }
}

function servebolt_nginx_set_posttypes($posttypes, $blogid = NULL){
    if(!empty($blogid)) $blogid = get_current_blog_id();
    $posttype_setting = array();
    foreach ($posttypes as $posttype){
        $posttype_setting[$posttype] = 'on';
    }
    update_option('servebolt_fpc_settings', $posttype_setting);
}

if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'servebolt db optimize', $servebolt_optimize_cmd );
	WP_CLI::add_command( 'servebolt db analyze', $servebolt_analyze_tables );
	WP_CLI::add_command( 'servebolt transients delete', $servebolt_delete_transients );
    WP_CLI::add_command( 'servebolt fpc', $servebolt_cli_nginx );
}


