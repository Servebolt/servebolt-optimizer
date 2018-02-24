<?php



function servebolt_transient_delete() {

	global $_wp_using_ext_object_cache;

	if ( !$_wp_using_ext_object_cache ) {


		global $wpdb;
		$time = time();

			// Delete transients from options table
			$sql_single_transients = "
				DELETE
					a, b
				FROM
					{$wpdb->options} a, {$wpdb->options} b
				WHERE
					a.option_name LIKE '_transient_%' AND
					a.option_name NOT LIKE '_transient_timeout_%' AND
					b.option_name = CONCAT(
						'_transient_timeout_',
						SUBSTRING(
							a.option_name,
							CHAR_LENGTH('_transient_') + 1
						)
					)
				AND b.option_value < {$time}
			";

			$wpdb -> query( $sql_single_transients );

			// Delete site transients if they are present in the options table
			$sql_site_transients = "
					DELETE
						a, b
					FROM
						{$wpdb->options} a, {$wpdb->options} b
					WHERE
						a.option_name LIKE '_site_transient_%' AND
						a.option_name NOT LIKE '_site_transient_timeout_%' AND
						b.option_name = CONCAT(
							'_site_transient_timeout_',
							SUBSTRING(
								a.option_name,
								CHAR_LENGTH('_site_transient_') + 1
							)
						)
					AND b.option_value < {$time}
				";
			$wpdb -> query( $sql_site_transients );

			// Delete transients from multisite
			if ( is_multisite() ) {

				$sql_multi = "
					DELETE
						a, b
					FROM
						{$wpdb->sitemeta} a, {$wpdb->sitemeta} b
					WHERE
						a.meta_key LIKE '_site_transient_%' AND
						a.meta_key NOT LIKE '_site_transient_timeout_%' AND
						b.meta_key = CONCAT(
							'_site_transient_timeout_',
							SUBSTRING(
								a.meta_key,
								CHAR_LENGTH('_site_transient_') + 1
							)
						)
					AND b.meta_value < UNIX_TIMESTAMP()
				";
				$wpdb -> query( $sql_multi );
			}
		}

	return 'Cleaned transients';
}


function servebolt_analyze_tables(){
	global $wpdb;

	$wpdb -> query( "ANALYZE TABLE $wpdb->posts" );
	$wpdb -> query( "ANALYZE TABLE $wpdb->postmeta" );
	$wpdb -> query( "ANALYZE TABLE $wpdb->options" );

	if (is_multisite()){

		$wpdb -> query( "ANALYZE TABLE $wpdb->sitemeta" );

		$site_blog_ids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM {$wpdb->blogs} where blog_id > 1"));

		foreach ($site_blog_ids AS $blog_id) {
			switch_to_blog( $blog_id );
			$wpdb -> query( "ANALYZE TABLE $wpdb->posts" );
			$wpdb -> query( "ANALYZE TABLE $wpdb->postmeta" );
			$wpdb -> query( "ANALYZE TABLE $wpdb->options" );
		}
	}
}

function servebolt_show_transients(){
	global $wpdb;
	$total_transients = $wpdb -> get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'" );
	$total_timed_transients = $wpdb -> get_var( "SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_%'" );
	if ( is_multisite() ) {
		$total_transients .= $wpdb -> get_var( "SELECT COUNT(*) FROM $wpdb->sitemeta WHERE meta_key LIKE '_site_transient_%'" );
		$total_timed_transients .= $wpdb -> get_var( "SELECT COUNT(*) FROM $wpdb->sitemeta WHERE meta_key LIKE '_site_transient_timeout_%'" );
	}
	$transient_number = $total_transients - $total_timed_transients;

	$text =  sprintf( __( 'There are currently %s transients (%s records) in the database.', 'artiss-transient-cleaner' ), $transient_number, $total_transients );

	if ( 0 <= $total_transients ) {

		$expired_transients = $wpdb -> get_var( "SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_%' AND option_value < UNIX_TIMESTAMP()" );
		if ( is_multisite() ) { $expired_transients .= $wpdb -> get_var( "SELECT COUNT(*) FROM $wpdb->sitemeta WHERE meta_key LIKE '_transient_timeout_%' AND meta_value < UNIX_TIMESTAMP()" ); }
		$text .= ' ';
		if ( 1 == $expired_transients ) {
			$text .= sprintf( __( '%s transient has expired.', 'artiss-transient-cleaner' ), $expired_transients );
		} else {
			$text .= sprintf( __( '%s transients have expired.', 'artiss-transient-cleaner' ), $expired_transients );
		}
	}
	echo '<p>' . $text . '</p>';
}

function servebolt_transient_cron(){
	/*
	 * Add deletion of transients to wp cron at a random time between 0100 and 0700 every Sunday
	 */
	if ( ! wp_next_scheduled( 'servebolt_cron_delete_transients' ) ) {
		$crontime = strtotime("last sunday 01:00") + rand(0, 21400);
		wp_schedule_event( $crontime, 'weekly', 'servebolt_cron_delete_transients' );
	}

	/*
	 * Add Analyzing of tables to keep the index up to date to wp cron at a random time between 0100 and 0700 every Sunday
	 */

	if ( ! wp_next_scheduled( 'servebolt_cron_analyze_tables' ) ) {
		$crontime = strtotime("last sunday 01:00") + rand(0, 21600);
		wp_schedule_event( $crontime, 'weekly', 'servebolt_cron_analyze_tables' );
	}
}
add_action( 'servebolt_cron_delete_transients', 'servebolt_transient_delete' );
add_action( 'servebolt_cron_analyze_tables', 'servebolt_analyze_tables' );


