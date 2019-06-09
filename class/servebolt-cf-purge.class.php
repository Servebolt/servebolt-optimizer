<?php
/**
 * Undocumented class
 *
 *
 */
class Servebolt_cloudflare {


	/**
	 * Setup
	 */
	public static function setup() {
		$switch = get_option( 'servebolt_cf_switch' );
		switch ( $switch ) {
			default:
				return;
			case true:
				$cronpurge = get_option( 'servebolt_cf_cron_purge' );
				if ( true === $cronpurge ) {
					wp_schedule_event( time(), 'every_minute', __CLASS__ . '::purge_by_cron' );
				}
				add_action( 'save_post', __CLASS__ . '::purge_on_save', 99 );
				break;
		}
	}
	/**
	 * Purging Cloudflare on save
	 *
	 * @param integer $post_id The post ID.
	 * @return void
	 */
	public static function purge_on_save( int $post_id ) {

		// If this is just a revision, don't purge anything.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		/**
		 * If cron purge is enabled, build the list of ids to purge by cron. If not active, just purge.
		 */
		$cronpurge = get_option( 'servebolt_cf_cron_purge' );
		if ( true === $cronpurge ) {
			$cronpurge = get_option( 'servebolt_cf_ids_to_purge' ); // Get current IDs to purge.

			array_push( $purge_ids, $post_id );
			array_push( $cronpurge, $purge_ids ); // Push our new IDs to the array.

			update_option( 'servebolt_cf_ids_to_purge', $cronpurge );

		} else {
			$purge_urls = array();

			// Get the archive link, but don't push it to the array if it doesn't exist.
			$archiveurl = get_post_type_archive_link( get_post_type( $post_id ) );
			if ( false !== $archiveurl ) {
				array_push( $purge_urls, $archiveurl );
			}

			$this_post_url = get_permalink( $post_id );
			array_push( $purge_urls, $this_post_url );

			self::purge_url( $purge_urls );
		}
	}
	/**
	 * Purge Cloudflare by URL. Also checks for an archive to purge.
	 *
	 * @param string $url The URL to be purged.
	 * @return void
	 */
	public static function purge_by_url( string $url ) {
		$post_id = url_to_postid( $url );
		if ( true === defined( 'SERVEBOLT_CF_PURGE_CRON' ) && SERVEBOLT_CF_PURGE_CRON === true ) {
			array_push( $purge_ids, $post_id );
			update_option( 'servebolt_cf_ids_to_purge', $purge_ids );
		} else {
			$purge_urls = array();

			// Get the archive link, but don't push it to the array if it doesn't exist.
			$archiveurl = get_post_type_archive_link( get_post_type( $post_id ) );
			if ( false !== $archiveurl ) {
				array_push( $purge_urls, $archiveurl );
			}

			$this_post_url = $url;
			array_push( $purge_urls, $this_post_url );

			self::purge_url( $purge_urls );
			return $purge_urls;
		}
	}

	/**
	 * Purging Cloudflare by cron using a list of IDs updated
	 *
	 * @return void
	 */
	private static function purge_by_cron() {
		$ids = get_option( 'servebolt_cf_ids_to_purge' );
		$urls = array();
		if( !empty( $ids ) ) {
			foreach ( $ids as $id ) {
				$url = get_permalink( $id );

				// Get the archive link, but don't push it to the array if it doesn't exist.
				$archiveurl = get_post_type_archive_link( get_post_type( $id ) );
				if ( false !== $archiveurl ) {
					array_push( $urls, $archiveurl );
				}

				array_push( $urls, $url );
				unset( $ids[ $id ] );
			}
			self::purge_url( $urls );
		}
		update_option( 'servebolt_cf_ids_to_purge', array() );
	}

	private function purge_url( array $urls ) {
		$cfkey   	= new Cloudflare\API\Auth\APIKey( get_option( 'servebolt_cf_username' ), get_option( 'servebolt_cf_apikey' ) );
		$cfadapter	= new Cloudflare\API\Adapter\Guzzle( $cfkey );
		$cfzone		= new Cloudflare\API\Endpoints\Zones( $cfadapter );

		$zoneid = get_option( 'servebolt_cf_zoneid' );
		return $cfzone->cachePurge( $zoneid, $urls );
	}

	public function purge_all() {
		$cfkey   	= new Cloudflare\API\Auth\APIKey( get_option( 'servebolt_cf_username' ), get_option( 'servebolt_cf_apikey' ) );
		$cfadapter	= new Cloudflare\API\Adapter\Guzzle( $cfkey );
		$cfzone		= new Cloudflare\API\Endpoints\Zones( $cfadapter );

		$zoneid = get_option( 'servebolt_cf_zoneid' );
		return $cfzone->cachePurgeEverything( $zoneid );
	}
}


