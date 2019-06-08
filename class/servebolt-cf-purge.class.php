<?php
/**
 * Undocumented class
 * 
 * Defines:
 * SERVEBOLT_CF_PURGE_CRON bool
 * 
 */
class Servebolt_cloudflare {
	

	/**
	 * Setup
	 */
	public static function setup() {
		$cronpurge = get_option( 'servebolt_cf_cron_purge' );
		if ( $cronpurge === true ) {
			wp_schedule_event( time(), 'every_minute', __CLASS__ . '::purge_by_cron' );
		}
		add_action( 'save_post', __CLASS__ . '::purge_on_save', 99 );
	}
	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public static function purge_on_save() {
		global $post_id;

		/**
		 * If cron purge is enabled, build the list of ids to purge by cron. If not active, just purge.
		 */
		if ( true === defined( 'SERVEBOLT_CF_PURGE_CRON' ) && SERVEBOLT_CF_PURGE_CRON === true ) {
			$purge_ids = get_option( 'servebolt_cf_ids_to_purge' );
			array_push( $purge_ids, $post_id );
			update_option( 'servebolt_cf_ids_to_purge', $purge_ids );
		} else {
			$url = get_permalink( $post_id );
			return self::purge_url( $url );
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
				array_push( $urls, $url );
			}
			self::purge_url( $urls );
		}
		update_option( 'servebolt_cf_ids_to_purge', array() );
	}

	private function purge_url( array $urls ) {
		$cfkey   	= new Cloudflare\API\Auth\APIKey( get_option( 'servebolt_cf_username' ), get_option( 'servebolt_cf_apikey' ) );
		$cfadapter	= new Cloudflare\API\Adapter\Guzzle( $cfkey );
		$cfzone		= new Cloudflare\API\Endpoints\Zones( $cfadapter );

		if ( ! is_array( $urls ) ) {
			$urls = array( $urls );
		}
		return $cfzone->cachePurge( get_option( 'servebolt_cf_zoneid' ), $urls );
	}
}


