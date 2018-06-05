<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action('wp_ajax_servebolt_optimize_db', 'servebolt_optimize_db');

function servebolt_optimize_db($cli = FALSE) {
	global $wpdb; // this is how you get access to the database

	if(is_multisite()) $sites = get_sites();

	// Check indexes for postmeta table
	$postmeta = [];
	if(is_multisite()){
		$sitepostmeta = [];
		foreach ($sites as $site){
			$id = $site->blog_id;
			switch_to_blog($id);
			$postmetaquery = $wpdb->get_results("SHOW INDEX FROM {$wpdb->prefix}postmeta");
			$sitepostmeta[$id] = $postmetaquery;
			restore_current_blog();
		}
		$postmeta = $sitepostmeta;
	}else{
		$postmeta[1] = $wpdb->get_results("SHOW INDEX FROM {$wpdb->prefix}postmeta");
	}

	foreach ($postmeta as $indexes) {
		foreach ($indexes as $index){
			if ($index->Column_name == 'meta_value') {
				$metavalue_index[$index->Table] = true;
			}else{
				$metavalue_index[$index->Table] = false;
			}
		}
	}

	// Add index to postmeta
	if (is_multisite()) {
		$meta_value = false;
		foreach ($sites as $site){
			$id = $site->blog_id;
			switch_to_blog($id);
			if(array_key_exists($wpdb->postmeta, $metavalue_index) && $metavalue_index[$wpdb->postmeta] !== true):
				$wpdb->query("ALTER TABLE {$wpdb->postmeta} ADD INDEX `sbpmv` (`meta_value`(10))");
				echo "Added index to site ID ".$id." postmeta \n";
				$meta_value = true;
			endif;
			restore_current_blog();
		}
	}else{
		if($metavalue_index[$wpdb->postmeta] === false){
			$wpdb->query("ALTER TABLE {$wpdb->postmeta} ADD INDEX `sbpmv` (`meta_value`(10))");
			echo "Added index to postmeta \n";
			$meta_value = true;
		}
	}


	// Check indexes for options table
	if(is_multisite()){
		$siteoptions = [];
		foreach ($sites as $site){
			$id = $site->blog_id;
			switch_to_blog($id);
			$optionsquery = $wpdb->get_results("SHOW INDEX FROM {$wpdb->prefix}options");
			$siteoptions[$id] = $optionsquery;
			restore_current_blog();
		}
	}else{
		$siteoptions[1] = $wpdb->get_results("SHOW INDEX FROM {$wpdb->prefix}options");
	}

	foreach ($siteoptions as $indexes) {
		foreach ($indexes as $index){
			if ($index->Column_name == 'autoload') {
				$options_indexes[$index->Table] = true;
			}else{
				$options_indexes[$index->Table] = false;
			}
		}
	}

	// Add index to postmeta
	if (is_multisite()) {
		foreach ($sites as $site){
			$id = $site->blog_id;
			switch_to_blog($id);
			$autoload = false;
			if(array_key_exists($wpdb->options, $options_indexes) && $options_indexes[$wpdb->options] !== true):
				$wpdb->query("ALTER TABLE {$wpdb->options} ADD INDEX(autoload)");
				echo "Added index to site ID ".$id." options \n";
				$autoload = true;
			endif;
			restore_current_blog();
		}
	}else{
		if($options_indexes[$wpdb->options] === false){
			$wpdb->query("ALTER TABLE {$wpdb->options} ADD INDEX(autoload)");
			echo "Added index to options \n";
			$autoload = true;
		}
	}

	// Convert all non-InnoDB tables to InnoDB
	$tables = $wpdb->get_results("SELECT *, table_name FROM INFORMATION_SCHEMA.TABLES WHERE engine != 'innodb' and TABLE_NAME like '{$wpdb->prefix}%'");
	if(array_key_exists('0', $tables)) {
		foreach ( $tables as $obj ) {
			$wpdb->query( "ALTER TABLE {$obj->table_name} ENGINE = InnoDB" );
			echo "Converted " . $obj->table_name . " to InnoDB \n";
			$innoDB = true;
		}
	}else{
		$innoDB = false;
	}


	// Echo a message if there is nothing to do
	if($innoDB !== true && $autoload !== true && $meta_value !== true){
		echo __('Database looks healthy, everything is good!️' . "\n", 'servebolt-wp');
		if ($cli) {
			return TRUE;
		}
	}
	if ($cli) {
		return FALSE;
	}
	wp_die();
}