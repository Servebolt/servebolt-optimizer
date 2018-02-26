<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function options_has_index(){
    $autoload_index = table_has_index('options', 'autoload');
    return output_index($autoload_index);
}

function postmeta_has_index(){
	$metavalue_index = table_has_index('postmeta', 'meta_value');
	return output_index($metavalue_index);
}

function output_index($state) {
    return ($state === false)
        ? '<img src="' . SERVEBOLT_PATH_URL . 'admin/assets/img/cancel.png" width="20"> '. __('Run Optimize to add the index')
        : '<img src="' . SERVEBOLT_PATH_URL . 'admin/assets/img/checked.png" width="20"> '. __('This table has an index');
}

/**
 * @return bool
 */
function table_has_index($wp_table, $index_name) {
    /* WPDB Docs: https://codex.wordpress.org/Class_Reference/wpdb */
    global $wpdb;
    $db_table = $wpdb->$wp_table;
    $indexes = $wpdb->get_results( "SHOW INDEX FROM {$db_table}" );
    foreach ( $indexes as $index ) {
	    if ( $index->Column_name == $index_name ) {
		    return true;
	    }
    }
	return false;
}

/**
 * @return null|object
 */
function get_myisam_tables(){
	global $wpdb;
	return $wpdb->get_results("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE engine = 'myisam' AND TABLE_NAME LIKE '{$wpdb->prefix}%'");
}

/**
 * @return bool
 */
function wp_cron_disabled(){
    return defined('DISABLE_WP_CRON') && DISABLE_WP_CRON === true;
}
