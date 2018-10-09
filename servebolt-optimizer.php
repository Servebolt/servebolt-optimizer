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

if(is_admin()) require_once SERVEBOLT_PATH . 'admin/security/wpvuldb.php';

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
 * Loads the class that sets the correct cache headers for NGINX cache
 */
if(!class_exists('Servebolt_Nginx_Fpc') ){
    $nginx_switch = get_option('servebolt_fpc_switch');
	require_once SERVEBOLT_PATH . 'class/servebolt-nginx-fpc.class.php';
    if($nginx_switch === 'on') Servebolt_Nginx_Fpc::setup();
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


// TODO: All CLI should really be a class
/**
 * Run Servebolt Optimizer.
 *
 * Add database indexes and convert database tables to modern table types or delete transients.
 *
 * ## EXAMPLES
 *
 *     $ wp servebolt db optimize
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

/**
 * Activate the correct cache headers for Servebolt Full Page Cache
 *
 * ## OPTIONS
 *
 * [--all]
 * : Activate on all sites in multisite
 *
 * [--post_types=<post_types>]
 * : Comma separated list of post types to be activated
 * ---
 *
 * ## EXAMPLES
 *
 *     # Activate Servebolt Full Page Cache, but only for pages and posts
 *     $ wp servebolt fpc activate --post_types=post,page
 *
 */
$servebolt_cli_nginx_activate = function( $args, $assoc_args ) {
    servebolt_nginx_control('activate', $args, $assoc_args);
};

/**
 * Deactivate the correct cache headers for Servebolt Full Page Cache
 *
 * ## OPTIONS
 *
 * [--all]
 * : Deactivate on all sites in multisite
 *
 * [--post_types=<post_types>]
 * : Comma separated list of post types to be deactivated
 * ---
 *
 * ## EXAMPLES
 *
 *     # Deactivate Servebolt Full Page Cache, but only for pages and posts
 *     $ wp servebolt fpc deactivate --post_types=post,page
 *
 */
$servebolt_cli_nginx_deactivate = function( $args, $assoc_args ) {
        servebolt_nginx_control('deactivate', $args, $assoc_args);
};

/**
 * Return status of the Servebolt Full Page Cache
 *
 *
 * ## EXAMPLES
 *
 *     # Return status of the Servebolt Full Page Cache
 *     $ wp servebolt fpc status
 *
 */
$servebolt_cli_nginx_status = function( $args, $assoc_args  ) {
    servebolt_nginx_status( $args, $assoc_args  );
};


function servebolt_nginx_status( $args, $assoc_args ){
    // TODO: List post types on single sites
    if(is_multisite() && array_key_exists('all', $assoc_args)):
        $sites = get_sites();
        $sites_status = array();
        foreach ($sites as $site){
            $id = $site->blog_id;
            switch_to_blog($id);
            $status = get_option('servebolt_fpc_switch');
            $posttypes = get_option('servebolt_fpc_settings');

            $enabledTypes = [];
            foreach ($posttypes as $key => $value){
                if($value === 'on') $enabledTypes[$key] = 'on';
            }
            $posttypes_keys = array_keys($enabledTypes);
            $posttypes_string = implode(',',$posttypes_keys);

            if(empty($posttypes_string)):
                $posttypes_string = sprintf(__('Default [%s]', 'servebolt'), Servebolt_Nginx_Fpc::default_cacheable_post_types('csv'));
            elseif(array_key_exists('all', $posttypes)):
                $posttypes_string = __('All', 'servebolt');
            endif;

            if($status === 'on'):
                $status = 'activated';
            else:
                $status = 'deactivated';
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
        $posttypes = get_option('servebolt_fpc_settings');

        $enabledTypes = [];
        if(!empty($posttypes)) foreach ($posttypes as $key => $value){
            if($value === 'on') $enabledTypes[$key] = 'on';
        }
        $posttypes_keys = array_keys($enabledTypes);
        $posttypes_string = implode(',',$posttypes_keys);

        if(empty($posttypes_string)):
            $posttypes_string = __('Default', 'servebolt');
        elseif(array_key_exists('all', $posttypes)):
            $posttypes_string = __('All', 'servebolt');
        endif;

        if($status === 'on'):
            $status = 'activated';
        else:
            $status = 'deactivated';
        endif;

        WP_CLI::line(sprintf(__('Servebolt Full Page Cache cache is %s'), $status));
        WP_CLI::line(sprintf(__('Post types enabled for caching: %s'), $posttypes_string));
    endif;
}



function servebolt_nginx_control($state, $args, $assoc_args){
    $switch = '';
    if($state === 'activate') {
        $switch = 'on';
    }elseif($state === 'deactivate'){
        $switch = '';
    }
    if(is_multisite() && $state === 'deactivate' && array_key_exists('post_types', $assoc_args) && array_key_exists('all', $assoc_args)){
        $sites = get_sites();
        foreach ($sites as $site) {
            $id = $site->blog_id;
            switch_to_blog($id);
            if(array_key_exists('post_types',$assoc_args)) servebolt_nginx_set_posttypes(explode(',', $assoc_args['post_types']), $switch, $id);
            restore_current_blog();
        }
    }
    elseif(is_multisite() && array_key_exists('all', $assoc_args)){
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
            if(array_key_exists('post_types',$assoc_args)) servebolt_nginx_set_posttypes(explode(',', $assoc_args['post_types']), $switch, $id);
            restore_current_blog();
        }

    }
    elseif($state === 'deactivate' && array_key_exists('post_types', $assoc_args)){
        servebolt_nginx_set_posttypes(explode(',', $assoc_args['post_types']), $switch);
    }
    else{
        $status = get_option('servebolt_fpc_switch');
        if(array_key_exists('post_types',$assoc_args)) servebolt_nginx_set_posttypes(explode(',', $assoc_args['post_types']), $switch);
        if($status !== $switch):
            update_option('servebolt_fpc_switch', $switch);
            WP_CLI::success(sprintf(__('NGINX Cache %1$sd'), $state));
        elseif($status === $switch):
            WP_CLI::warning(sprintf(__('NGINX Cache already %1$sd'), $state));
        endif;

    }
}

function servebolt_nginx_set_posttypes($posttypes, $switch, $blogid = NULL){
    $posttype_setting = get_option('servebolt_fpc_settings');
    if($blogid !== NULL && is_multisite()) switch_to_blog($blogid);
    $postTypeChanged = array();

    if(array_key_exists('all', $posttypes)){
        $args = array(
            'public' => true
        );
        $AllTypes = get_post_types($args, 'objects');
        foreach ($AllTypes as $type) if($type !== 'all'){
            $posttype_setting[$type->name] = $switch;
            $postTypeChanged[] = $type->name;
        }
    }elseif(empty($switch)){
        $posttype_setting = array();
        $postTypeChanged = __('all');
        WP_CLI::warning(sprintf(__('Cache was not completely disabled, but restored to default settings [%s]. Use wp servebolt fpc deactivate [--all] to deactivate NGINX cache completely.', 'servebolt'),Servebolt_Nginx_Fpc::default_cacheable_post_types('csv')));
    }
    else{
        foreach ($posttypes as $posttype){
            $posttype_setting[$posttype] = $switch;
            $postTypeChanged = $posttype;
        }
    }
    update_option('servebolt_fpc_settings', $posttype_setting);
    $state = 'deactivate';
    if($switch === 'on') $state = 'activate';
    WP_CLI::success(sprintf(__('NGINX Cache %1$sd for %2$s post type(s)'), $state, $postTypeChanged));
    if($blogid !== NULL && is_multisite()) restore_current_blog();
}

if ( class_exists( 'WP_CLI' ) ) {
    WP_CLI::add_command( 'servebolt db optimize', $servebolt_optimize_cmd ); // TODO: Remove in v1.7
	WP_CLI::add_command( 'servebolt db fix', $servebolt_optimize_cmd );
	WP_CLI::add_command( 'servebolt db analyze', $servebolt_analyze_tables );
    WP_CLI::add_command( 'servebolt fpc activate', $servebolt_cli_nginx_activate );
    WP_CLI::add_command( 'servebolt fpc deactivate', $servebolt_cli_nginx_deactivate );
    WP_CLI::add_command( 'servebolt fpc status', $servebolt_cli_nginx_status );
}


