<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function tables_to_have_index(){
	global $wpdb;

	$tables = array(
		'options' => 'autoload',
		'postmeta' => 'meta_value'
	);

	if(is_multisite() === true):
		$sites = get_all_tables();
		foreach ($sites as $key => $value){
			foreach ($tables as $table => $index){
				switch_to_blog($key);
					$blogprefix = $wpdb->prefix;
					$a_table['site_id'] = $key;
					$a_table['name'] = implode([$blogprefix,$table]);
					$a_table['index'] = $index;

					$db_table = $wpdb->$table;
					$indexes = $wpdb->get_results( "SHOW INDEX FROM {$db_table}" );

					foreach ( $indexes as $index ) {
						if ( $index->Column_name == $a_table['index'] ) {
							$a_table['has_index'] = true;
						}else{
							$a_table['has_index'] = false;
						}
					}
					$tables_to_have_index[] = $a_table;
				restore_current_blog();
			}
		}
	else:
		foreach ($tables as $table => $index){
			$a_table['site_id'] = $key;
			$a_table['name'] = implode([$blogprefix,$table]);
			$a_table['index'] = $index;

			$db_table = $wpdb->$table;
			$indexes = $wpdb->get_results( "SHOW INDEX FROM {$db_table}" );

			foreach ( $indexes as $index ) {
				if ( $index->Column_name == $a_table['index'] ) {
					$a_table['has_index'] = true;
				}else{
					$a_table['has_index'] = false;
				}
			}
			$tables_to_have_index[] = $a_table;
		}
	endif;
	return $tables_to_have_index;
}

function get_all_tables(){
	global $wpdb;

	if(is_multisite() === true){
		$sites = get_sites();
		$tables = array();

		foreach ($sites as $site){
			$id = $site->blog_id;
			switch_to_blog($id);
			$siteTables = $wpdb->tables;
			$tables[$id] = array_flip($siteTables);
			restore_current_blog();
		}

	}else{
		$tables = $wpdb->tables;
	}
	return $tables;
}

/**
 * @return bool
 */
function table_has_index($wp_table, $index_name, $siteid = NULL) {
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
