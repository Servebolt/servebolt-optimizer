<?php

function servebolt_analyze_tables( $cli = FALSE ){
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
	if ($cli) {
		return TRUE;
	}
}

add_action( 'servebolt_cron_analyze_tables', 'servebolt_analyze_tables' );


