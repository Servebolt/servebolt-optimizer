<?php
add_action('wp_ajax_sb_optimize', 'servebolt_optimize_db');

function servebolt_optimize_db() {
	global $wpdb; // this is how you get access to the database
	$innoDB = true;
	$autoload = true;
	$meta_value = true;

	// Check indexes for postmeta table
	$postmeta = $wpdb->get_results("SHOW INDEX FROM {$wpdb->prefix}postmeta");
	$metavalue_index = false;
	foreach ($postmeta as $index) {
		if ($index->Column_name == 'meta_value') {
			$metavalue_index = $index->Key_name;
		}
	}
	// Add index to postmeta
	if ($metavalue_index === false) {
		$wpdb->query("ALTER TABLE {$wpdb->postmeta} ADD INDEX `sbpmv` (`meta_value`(10))");
		echo "Added index to postmeta \n";
	} else{
		$meta_value = false;
	}


	// Check indexes for options table
	$options_indexes = $wpdb->get_results("SHOW INDEX FROM {$wpdb->prefix}options");
	$autoload_index = false;
	foreach ($options_indexes as $index) {
		if ($index->Column_name === 'autoload') {
			$autoload_index = $index->Key_name;
		}
	}
	// Add index to options table
	if ($autoload_index === false) {
		$wpdb->query("ALTER TABLE {$wpdb->options} ADD INDEX(autoload)");
		echo "Added index to options \n";
	} else {
		$autoload = false;
	}

	// Convert all non-InnoDB tables to InnoDB
	$tables = $wpdb->get_results("SELECT *, table_name FROM INFORMATION_SCHEMA.TABLES WHERE engine != 'innodb' and TABLE_NAME like '{$wpdb->prefix}%'");
	if($tables > 0) {
		foreach ( $tables as $obj ) {
			$wpdb->query( "ALTER TABLE {$obj->table_name} ENGINE = InnoDB" );
			echo "Converted " . $obj->table_name . " \n";
		}
	} else {
		$innoDB = false;
	}

	// Echo a message if there is nothing to do
	if($innoDB === false && $autoload === false && $meta_value === false){
		echo __('Database looks healthy, everything is good! ⚡️', 'servebolt-wp');
	}

	exit();
}