<?php
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
 *
 * [--exclude=<ids>]
 * : Comma separated list of ids to exclude for full page caching
 *
 * [--status]
 * : Display status after control is executed
 *
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
 *
 * [--exclude=<ids>]
 * : Comma separated list of ids to exclude for full page caching
 *
 * [--status]
 * : Display status after control is executed
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
    if(in_array('status', $assoc_args)) servebolt_nginx_status($args, $assoc_args);
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

/**
 * @param $args
 * @param $assoc_args
 */
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
                $posttypes_string = sprintf(__('Default [%s]', 'servebolt-wp'), Servebolt_Nginx_Fpc::default_cacheable_post_types('csv'));
            elseif(array_key_exists('all', $posttypes)):
                $posttypes_string = __('All', 'servebolt-wp');
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
            $site_status['ACTIVE_POST_TYPES'] = $posttypes_string;
            $sites_status[] = $site_status;
            restore_current_blog();
        }
        WP_CLI\Utils\format_items( 'table', $sites_status , array('URL', 'STATUS', 'ACTIVE_POST_TYPES'));
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
            $posttypes_string = __('Default [post,page,product]', 'servebolt-wp');
        elseif(array_key_exists('all', $posttypes)):
            $posttypes_string = __('All', 'servebolt-wp');
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


/**
 * @param $state
 * @param $args
 * @param $assoc_args
 */
function servebolt_nginx_control($state, $args, $assoc_args){
    $switch = '';
    if($state === 'activate') {
        $switch = 'on';
    }elseif($state === 'deactivate'){
        $switch = 'off';
    }
    if(is_multisite() && $state === 'deactivate' && array_key_exists('post_types', $assoc_args) && array_key_exists('all', $assoc_args)){
        $sites = get_sites();
        foreach ($sites as $site) {
            $id = $site->blog_id;
            switch_to_blog($id);
            if(array_key_exists('post_types',$assoc_args)) servebolt_nginx_set_posttypes(explode(',', $assoc_args['post_types']), $state, $id);
            restore_current_blog();
        }
    }
    elseif(is_multisite() && array_key_exists('all', $assoc_args)){
        $sites = get_sites();

        foreach ($sites as $site) {
            $id = $site->blog_id;
            switch_to_blog($id);
            $url = get_site_url($id);
            $status = get_option('servebolt_fpc_switch');
            if($status !== $switch):
                update_option('servebolt_fpc_switch', $switch);
                WP_CLI::success(sprintf(__('Full Page Cache %1$sd on %2$s'), $state, esc_url($url)));
            elseif($status === $switch):
                WP_CLI::warning(sprintf(__('Full Page Cache already %1$sd on %2$s'), $state, esc_url($url)));
            endif;

            if(array_key_exists('post_types',$assoc_args)) servebolt_nginx_set_posttypes(explode(',', $assoc_args['post_types']), $state, $id);
            restore_current_blog();
        }

    }
    elseif($state === 'deactivate' && array_key_exists('post_types', $assoc_args)){
        servebolt_nginx_set_posttypes(explode(',', $assoc_args['post_types']), $state, get_current_blog_id());
    }
    else{
        $status = get_option('servebolt_fpc_switch');
        if(array_key_exists('post_types',$assoc_args)) servebolt_nginx_set_posttypes(explode(',', $assoc_args['post_types']), $state);
        if($status !== $switch):
            update_option('servebolt_fpc_switch', $switch);
            WP_CLI::success(sprintf(__('Full Page Cache %1$sd'), $state));
        elseif($status === $switch):
            WP_CLI::warning(sprintf(__('Full Page Cache already %1$sd'), $state));
        endif;
    }

    if(array_key_exists('exclude', $assoc_args)){
        servebolt_set_exclude_ids($assoc_args['exclude']);
    }

}

/**
 * @param $posttypes
 * @param $state
 */
function servebolt_nginx_set_posttypes( $posttypes , $state){
    $switch = '';
    if($state === 'activate') {
        $switch = 'on';
    }elseif($state === 'deactivate'){
        $switch = 'off';
    }

    $updateOption = get_option('servebolt_fpc_settings');

    // Get only publicly available post types
    $args = array(
        'public' => true
    );
    $AllTypes = get_post_types($args, 'objects');


    if(in_array('all', $posttypes)) {
        $CLIfeedback = sprintf(__('Cache deactivated for all posttypes on %s', 'servebolt-wp'), get_home_url());
        $success = true;

        if($switch === 'on'){

            foreach ($AllTypes as $type){
                $updateOption[$type->name] = $switch;
            }

            $CLIfeedback = sprintf(__('Cache activated for all posttypes on %s', 'servebolt-wp'), get_home_url());
            $success = true;

        }

    } else {

        foreach ($posttypes as $posttype) if(array_key_exists($posttype, $AllTypes)){
            $updateOption[$posttype] = $switch;
        }

        // remove everything that is off
        foreach ($updateOption as $key => $value){
            if($value === 'off') unset($updateOption[$key]);
        }

        $CLIfeedback = sprintf(__('Cache %sd for post type(s) %s on %s', 'servebolt-wp'),$state, implode(',', $posttypes) , get_home_url());
        $success = true;

    }

    if($success === true){
        WP_CLI::success($CLIfeedback);
    }elseif($success === false){
        WP_CLI::warning($CLIfeedback);
    }

    update_option('servebolt_fpc_settings', $updateOption);
}

function servebolt_set_exclude_ids($ids){
    $id_array = explode(',', $ids);

    $excluded = get_option('servebolt_fpc_exclude');

    if($excluded === false){
        $excluded = array();
    }

    $additions = array();
    foreach ($id_array as $id){
        if ( FALSE === get_post_status( $id ) ) {
            // The ID does not exist
        } else {
            // The ID exists
            $push_id = array($id);
            array_push($excluded, $push_id);
            array_push($additions, $push_id);
        }
    }
    if(!empty($additions)){
        $additions_s = implode(',', $additions);
        $clifeedback = sprintf(__('Added %s to the list of excluded ids'),$additions_s);
        WP_CLI::success($clifeedback);
    } else {
        $clifeedback = __('No valid ids found in --exclude parameter');
        WP_CLI::warning($clifeedback);
    }

    update_option('servebolt_fpc_exclude', $excluded);
}