<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once 'cloudflare-wrapper.class.php';

/**
 * Class Servebolt_CF
 * @package Servebolt
 */
class Servebolt_CF {

	/**
	 * Singleton instance.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * @var null
	 */
	private $cf = null;

	/**
	 * Instantiate class.
	 *
	 * @param bool $credentials
	 *
	 * @return Servebolt_CF|null
	 */
	public static function getInstance($credentials = false) {
		if ( self::$instance == null ) {
			self::$instance = new Servebolt_CF($credentials);
		}
		return self::$instance;
	}

	/**
	 * Servebolt_CF constructor.
	 */
	private function __construct() {
		$this->initCF();
		$this->registerActions();
		$this->registerCron();
	}

	/**
	 * Get Cloudflare instance.
	 *
	 * @return Servebolt_CF|null
	 */
	private function cf()
	{
		if ( is_null($this->cf) ) {
			$this->cf = Cloudflare::getInstance();
		}
		return $this->cf;
	}

	/**
	 * Instantiate CF class and pass authentication parameters.
	 *
	 * @return bool
	 */
	private function initCF()
	{
		if ( ! $this->registerCredentials() ) return false;
		$this->cf()->setZoneId($this->getActiveZoneId());
		return true;
	}

	/**
	 * Get zone by Id from Cloudflare.
	 *
	 * @param $zoneId
	 *
	 * @return mixed
	 */
	public function getZoneById($zoneId)
	{
		return $this->cf()->getZoneById($zoneId);
	}

	/**
	 * Store active zone Id.
	 *
	 * @param $zoneId
	 *
	 * @return bool
	 */
	public function storeActiveZoneId($zoneId)
	{
		return sb_update_option('cf_zone_id', $zoneId);
	}

	/**
	 * Get active zone Id.
	 *
	 * @return mixed|void
	 */
	public function getActiveZoneId()
	{
		return sb_get_option('cf_zone_id');
	}

	/**
	 * Get authentication type for Cloudflare.
	 *
	 * @return mixed|void
	 */
	private function getAuthenticationType()
	{
		$defaultValue = 'apiToken';
		return sb_get_option( 'cf_auth_type',  $defaultValue);
	}

	/**
	 * Set credentials in Cloudflare class.
	 *
	 * @param $credentials
	 *
	 * @return mixed
	 */
	public function setCredentials($credentials)
	{
		return $this->cf()->setCredentials($credentials);
	}

	/**
	 * Register credentials.
	 *
	 * @return bool
	 */
	private function registerCredentials()
	{
		switch ( $this->getAuthenticationType() ) {
			case 'apiToken':
				return $this->setCredentials( sb_get_option( 'cf_api_token' ) );
			case 'apiKey':
				return $this->setCredentials([
					'email'  => sb_get_option( 'cf_email' ),
					'apikey' => sb_get_option( 'cf_api_key' ),
				]);
		}
		return false;
	}

	public function storeCredentials($credentials, $type)
	{
		switch ($type) {
			case 'apiKey':
				return sb_update_option('cf_auth_type', $type, true) && sb_update_option('cf_email', $credentials['email'], true) && sb_update_option('cf_api_key', $credentials['apiKey'], true);
			case 'apiToken':
				return sb_update_option('cf_auth_type', $type, true) && sb_update_option('cf_api_token', $credentials, true);

		}
		return false;
	}

	/**
	 * Register cron job.
	 */
	private function registerCron()
	{
		if ( ! $this->CFIsActive() || ! $this->cronPurgeIsActive() ) return;
		wp_schedule_event( time(), 'every_minute', [$this, 'purgeByCron'] );
	}

	/**
	 * Register actions
	 */
	private function registerActions()
	{
		if ( ! $this->CFIsActive() ) return;
		add_action( 'save_post', [$this, 'purgePost'], 99 );
	}

	/**
	 * @return bool
	 */
	private function cronPurgeIsActive()
	{
		return filter_var(sb_get_option( 'cf_cron_purge' ), FILTER_VALIDATE_BOOLEAN) === true;
	}

	/**
	 * Check if Cloudflare is used.
	 *
	 * @return bool
	 */
	private function CFIsActive()
	{
		return filter_var(sb_get_option( 'cf_switch' ), FILTER_VALIDATE_BOOLEAN) === true;
	}

	/**
	 * Add a post Id to the purge queue (by cron).
	 *
	 * @param int $postId
	 *
	 * @return bool
	 */
	private function addIdToPurgeQueue(int $postId)
	{
		$idsToPurgeWithCron = $this->getIdsToPurge();
		array_push( $idsToPurgeWithCron, $postId );
		return $this->setIdsToPurge($idsToPurgeWithCron);
	}

	/**
	 * Set the post Ids to purge.
	 *
	 * @param array $idsToPurge
	 *
	 * @return bool
	 */
	private function setIdsToPurge( array $idsToPurge ) {
		return sb_update_option( 'cf_ids_to_purge', $idsToPurge );
	}

	/**
	 * Get the post Ids to purge.
	 *
	 * @return array
	 */
	private function getIdsToPurge() {
		return (array) sb_get_option( 'cf_ids_to_purge', [] );
	}

	/**
	 * Get all URL's to purge for a post.
	 *
	 * @param $postId
	 *
	 * @return array
	 */
	private function getPurgeUrlsByPostId( int $postId )
	{
		$purgeUrls = [];
		if ( $permalink = get_permalink( $postId ) ) {
			array_push( $purgeUrls, $permalink );
		}
		if ( $archiveUrl = get_post_type_archive_link( get_post_type( $postId ) ) ) {
			array_push( $purgeUrls, $archiveUrl );
		}
		return $purgeUrls;
	}

	/**
	 * Purging Cloudflare on save.
	 *
	 * @param int $postId The post ID.
	 *
	 * @return bool|void
	 */
	public function purgePost( int $postId ) {

		// Check if cron purging is active
		if ( ! $this->cronPurgeIsActive() ) return;

		// If this is just a revision, don't purge anything.
		if ( wp_is_post_revision( $postId ) ) return;

		// If cron purge is enabled, build the list of ids to purge by cron. If not active, just purge right away.
		if ( $this->cronPurgeIsActive() ) {
			return $this->addIdToPurgeQueue($postId);
		} else if ( $urlsToPurge = $this->getPurgeUrlsByPostId($postId) ) {
			return $this->cf()->purgeUrls($urlsToPurge);
		}
	}

	/**
	 * Purge Cloudflare by URL. Also checks for an archive to purge.
	 *
	 * @param string $url The URL to be purged.
	 *
	 * @return bool|void
	 */
	public function purgeByUrl( string $url ) {
		$postId = url_to_postid( $url );
		if ( ! $postId ) return false;
		return $this->purgePost($postId);
	}

	/**
	 * Get all urls that are queued up for purging.
	 *
	 * @param $postIds
	 *
	 * @return array
	 */
	private function getPurgeUrlsByPostIds(array $postIds)
	{
		$urls = [];
		foreach ( $postIds as $postId ) {
			$urls = array_merge($urls, $this->getPurgeUrlsByPostId($postId));
		}
		$urls = array_unique($urls);
		return $urls;
	}

	/**
	 * Purge all.
	 *
	 * @return mixed
	 */
	public function purgeAll()
	{
		return $this->cf()->purgeAll();
	}

	public function listZones()
	{
		return $this->cf()->listZones();
	}

	/**
	 * Purging Cloudflare cache by cron using a list of IDs updated.
	 */
	public function purgeByCron() {
		$urls = $this->getPurgeUrlsByPostIds( $this->getIdsToPurge() );
		if ( ! empty( $urls ) ) {
			$this->cf()->purgeUrls( $urls );
			$this->setIdsToPurge([]);
			return true;
		}
		return false;
	}

}


