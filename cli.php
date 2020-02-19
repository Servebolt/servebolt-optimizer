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

    if ( ! ( Servebolt_Optimize_DB::instance() )->optimize_db(true) ) {
        WP_CLI::success('Optimization done');
    } else {
        WP_CLI::warning('Everything OK. No optimization to do.');
    }
};

$servebolt_analyze_tables = function() {
	require_once SERVEBOLT_PATH . 'admin/optimize-db/optimize-db.php';
    if ( ! ( Servebolt_Optimize_DB::instance() )->analyze_tables(true) ) {
        WP_CLI::error('Could not analyze tables.');
    } else {
        WP_CLI::success('Analyzed tables.');
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
        $sites_status = [];
        foreach ($sites as $site){
            $id = $site->blog_id;
            switch_to_blog($id);
            $status = sb_get_option('fpc_switch');
            $post_types = sb_get_option('fpc_settings');

            $enabledTypes = [];
            foreach ($post_types as $key => $value){
                if($value === 'on') $enabledTypes[$key] = 'on';

            }
            $post_types_keys = array_keys($enabledTypes);
            $post_types_string = implode(',',$post_types_keys);

            if(empty($post_types_string)):
                $post_types_string = sprintf(sb__('Default [%s]'), Servebolt_Nginx_Fpc::default_cacheable_post_types('csv'));
            elseif(array_key_exists('all', $post_types)):
                $post_types_string = sb__('All');
            endif;

            if($status === 'on'):
                $status = 'activated';
            else:
                $status = 'deactivated';
            endif;

            $url = get_site_url($id);
            $site_status = [];
            $site_status['URL'] = $url;
            $site_status['STATUS'] = $status;
            $site_status['ACTIVE_POST_TYPES'] = $post_types_string;
            $sites_status[] = $site_status;
            restore_current_blog();
        }
        WP_CLI\Utils\format_items( 'table', $sites_status , ['URL', 'STATUS', 'ACTIVE_POST_TYPES']);
    else:
        $status = sb_get_option('fpc_switch');
        $post_types = sb_get_option('fpc_settings');

        $enabledTypes = [];
        if(!empty($post_types)) foreach ($post_types as $key => $value){
            if($value === 'on') $enabledTypes[$key] = 'on';
        }
        $post_types_keys = array_keys($enabledTypes);
        $post_types_string = implode(',',$post_types_keys);

        if(empty($post_types_string)):
            $post_types_string = sb__('Default [post,page,product]');
        elseif(array_key_exists('all', $post_types)):
            $post_types_string = sb__('All');
        endif;

        if($status === 'on'):
            $status = 'activated';
        else:
            $status = 'deactivated';
        endif;

        WP_CLI::line(sprintf(sb__('Servebolt Full Page Cache cache is %s'), $status));
        WP_CLI::line(sprintf(sb__('Post types enabled for caching: %s'), $post_types_string));
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
            if(array_key_exists('post_types',$assoc_args)) servebolt_nginx_set_post_types(explode(',', $assoc_args['post_types']), $state, $id);
            restore_current_blog();
        }
    }
    elseif(is_multisite() && array_key_exists('all', $assoc_args)){
        $sites = get_sites();

        foreach ($sites as $site) {
            $id = $site->blog_id;
            switch_to_blog($id);
            $url = get_site_url($id);
            $status = sb_get_option('fpc_switch');
            if($status !== $switch):
	            sb_update_option('fpc_switch', $switch);
                WP_CLI::success(sprintf(sb__('Full Page Cache %1$sd on %2$s'), $state, esc_url($url)));
            elseif($status === $switch):
                WP_CLI::warning(sprintf(sb__('Full Page Cache already %1$sd on %2$s'), $state, esc_url($url)));
            endif;

            if(array_key_exists('post_types',$assoc_args)) servebolt_nginx_set_post_types(explode(',', $assoc_args['post_types']), $state, $id);
            restore_current_blog();
        }

    }
    elseif($state === 'deactivate' && array_key_exists('post_types', $assoc_args)){
        servebolt_nginx_set_post_types(explode(',', $assoc_args['post_types']), $state, get_current_blog_id());
    }
    else{
        $status = sb_get_option('fpc_switch');
        if(array_key_exists('post_types',$assoc_args)) servebolt_nginx_set_post_types(explode(',', $assoc_args['post_types']), $state);
        if($status !== $switch):
	        sb_update_option('fpc_switch', $switch);
            WP_CLI::success(sprintf(sb__('Full Page Cache %1$sd'), $state));
        elseif($status === $switch):
            WP_CLI::warning(sprintf(sb__('Full Page Cache already %1$sd'), $state));
        endif;
    }

    if(array_key_exists('exclude', $assoc_args)){
        servebolt_set_exclude_ids($assoc_args['exclude']);
    }

}

/**
 * Set post types to cache.
 *
 * @param $post_types
 * @param $state
 */
function servebolt_nginx_set_post_types( $post_types , $state){
    $switch = '';
    if($state === 'activate') {
        $switch = 'on';
    }elseif($state === 'deactivate'){
        $switch = 'off';
    }

    $updateOption = sb_get_option('fpc_settings');

    // Get only publicly available post types
    $args = [
        'public' => true
    ];
    $AllTypes = get_post_types($args, 'objects');


    if(in_array('all', $post_types)) {
        $CLIfeedback = sprintf(sb__('Cache deactivated for all post types on %s'), get_home_url());
        $success = true;

        if ( $switch === 'on' ) {

            foreach ( $AllTypes as $type ) {
                $newOption[$type->name] = $switch;
            }
            $updateOption = $newOption;

            $CLIfeedback = sprintf(sb__('Cache activated for all post types on %s'), get_home_url());
            $success = true;

        }

    } else {

        foreach ($post_types as $post_type) if(array_key_exists($post_type, $AllTypes)){
            $updateOption[$post_type] = $switch;
        }

        // remove everything that is off
        foreach ($updateOption as $key => $value){
            if($value === 'off') unset($updateOption[$key]);
        }

        $CLIfeedback = sprintf(sb__('Cache %sd for post type(s) %s on %s'),$state, implode(',', $post_types) , get_home_url());
        $success = true;

    }

    if($success === true){
        WP_CLI::success($CLIfeedback);
    }elseif($success === false){
        WP_CLI::warning($CLIfeedback);
    }

	sb_update_option('fpc_settings', $updateOption);
}

/**
 * Set exclude Ids.
 *
 * @param $ids
 */
function servebolt_set_exclude_ids($ids){
    $id_array = explode(',', $ids);

    $excluded = sb_get_option('fpc_exclude');

    $additions = [];
    foreach ($id_array as $id){
        if ( FALSE === get_post_status( $id ) ) {
            // The ID does not exist
        } else {
            // The ID exists
            $push_id = [$id];
            array_push($excluded, $push_id);
            array_push($additions, $push_id);
        }
    }
    if(!empty($additions)){
        $additions_s = implode(',', $additions);
        $clifeedback = sprintf(sb__('Added %s to the list of excluded ids'), $additions_s);
        WP_CLI::success($clifeedback);
    } else {
        $clifeedback = sb__('No valid ids found in --exclude parameter');
        WP_CLI::warning($clifeedback);
    }

	sb_update_option('fpc_exclude', $excluded);
}
