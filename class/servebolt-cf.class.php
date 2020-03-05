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
	 * Cloudflare wrapper class.
	 *
	 * @var null
	 */
	private $cf = null;

	/**
	 * The default API authentication type.
	 *
	 * @var string
	 */
	private $defaultAuthenticationType = 'apiToken';

	/**
	 * Whether we successfully registered credentials for Cloudflare API class.
	 *
	 * @var bool
	 */
	private $credentialsOk = false;

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
	 * Instantiate CF class and pass authentication parameters.
	 *
	 * @return bool
	 */
	private function initCF()
	{
		if ( ! $this->registerCredentialsInClass() ) return false;
		$activeZone = $this->getActiveZoneId();
		if ( $activeZone ) $this->cf()->setZoneId($activeZone, false);
		return true;
	}

	/**
	 * Register action hooks.
	 */
	private function registerActions()
	{
		if ( ! $this->CFIsActive() ) return;
		add_action( 'save_post', [$this, 'purgePost'], 99 );
	}

	/**
	 * Register Cron job.
	 */
	private function registerCron()
	{
		if ( ! $this->CFIsActive() || ! $this->cronPurgeIsActive() ) return;
		$closure = [$this, 'purgeByCron'];
		/*
		if ( ! wp_next_scheduled( $closure ) ) {
			wp_schedule_event(time(), 'every_minute', $closure);
		}
		*/
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
	 * The Cloudflare API permissions required for this plugin.
	 *
	 * @param bool $humanReadable
	 *
	 * @return array|string
	 */
	public function APIPermissionsNeeded($humanReadable = true) {
		$permissions = ['Zone.Zone', 'Zone.Cache Purge'];
		if ( $humanReadable ) {
			return sb_natural_language_join($permissions);
		}
		return $permissions;
	}

	/**
	 * Test API connection.
	 *
	 * @return bool
	 */
	public function testAPIConnection()
	{
		try {
			$this->cf()->listZones();
			return true;
		} catch (Exception $e) {
			return false;
		}
		return false;
	}

	/**
	 * Check that we have credentials and have selected a zone.
	 *
	 * @return bool
	 */
	public function CFCacheFeatureAvailable()
	{
		return $this->credentialsOk() && $this->zoneOk();
	}

	/**
	 * Check if Cloudflare is used.
	 *
	 * @return bool
	 */
	public function CFIsActive()
	{
		return filter_var(sb_get_option('cf_switch'), FILTER_VALIDATE_BOOLEAN) === true;
	}

	/**
	 * Check if we got Cloudflare API credentials in place.
	 *
	 * @return bool
	 */
	public function credentialsOk()
	{
		return $this->credentialsOk === true;
	}

	/**
	 * Check that we have specified a zone.
	 *
	 * @return bool
	 */
	public function zoneOk()
	{
		$zone = $this->getActiveZoneId();
		return $zone !== false && ! is_null($zone);
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
	 * Clear the active zone.
	 */
	public function clearActiveZone()
	{
		sb_delete_option('cf_zone_id');
		sb_delete_option('cf_zone_id');
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
	 * List zones.
	 *
	 * @return mixed
	 */
	public function listZones()
	{
		return $this->cf()->listZones();
	}

	/**
	 * Get authentication type for Cloudflare.
	 *
	 * @return mixed|void
	 */
	public function getAuthenticationType()
	{
		return sb_get_option('cf_auth_type',  $this->defaultAuthenticationType);
	}

	/**
	 * Clear all credentials.
	 */
	public function clearCredentials()
	{
		foreach(['cf_auth_type', 'cf_api_token', 'cf_api_key', 'cf_email'] as $key) {
			sb_delete_option($key);
			sb_delete_option($key);
		}
	}

	/**
	 * Set credentials in Cloudflare class.
	 *
	 * @param $authType
	 * @param $credentials
	 *
	 * @return mixed
	 */
	public function setCredentialsInCFClass($authType, $credentials)
	{
		return $this->cf()->setCredentials($authType, $credentials);
	}

	/**
	 * Register credentials in class.
	 *
	 * @return bool
	 */
	private function registerCredentialsInClass()
	{
		switch ( $this->getAuthenticationType() ) {
			case 'apiToken':
				$apiToken = $this->getCredential('apiToken');
				if ( ! empty($apiToken) ) {
					$this->setCredentialsInCFClass('apiToken', compact('apiToken'));
					$this->credentialsOk = true;
				}
				break;
			case 'apiKey':
				$email = $this->getCredential('email');
				$apiKey = $this->getCredential('apiKey');
				if ( ! empty($email) && ! empty($apiKey) ) {
					$this->setCredentialsInCFClass('apiKey', compact('email', 'apiKey'));
					$this->credentialsOk = true;
				}
				break;
		}
		return $this->credentialsOk;
	}

	/**
	 * Get credential from DB.
	 *
	 * @param $key
	 *
	 * @return bool|mixed|void
	 */
	public function getCredential($key)
	{
		switch ($key) {
			case 'email':
				return sb_get_option('cf_email');
			case 'apiKey':
				return sb_get_option('cf_api_key');
			case 'apiToken':
				return sb_get_option('cf_api_token');
		}
		return false;
	}

	/**
	 * Store API credentials in DB.
	 *
	 * @param $credentials
	 * @param $type
	 *
	 * @return bool
	 */
	public function storeCredentials($credentials, $type)
	{
		switch ($type) {
			case 'apiKey':
				if ( sb_update_option('cf_auth_type', $type, true) && sb_update_option('cf_email', $credentials['email'], true) && sb_update_option('cf_api_key', $credentials['apiKey'], true) ) {
					$this->registerCredentialsInClass();
					return true;
				}
				break;
			case 'apiToken':
				if ( sb_update_option('cf_auth_type', $type, true) && sb_update_option('cf_api_token', $credentials, true) ) {
					$this->registerCredentialsInClass();
					return true;
				}
				break;
		}
		return false;
	}

	/**
	 * Add a post Id to the purge queue (by cron).
	 *
	 * @param $item
	 *
	 * @return bool
	 */
	public function addItemToPurgeQueue($item)
	{
		if ( empty($item) ) return false;
		$itemsToPurgeWithCron = $this->getItemsToPurge();
		$itemsToPurgeWithCron[] = $item;
		$itemsToPurgeWithCron = array_unique($itemsToPurgeWithCron);
		return $this->setItemsToPurge($itemsToPurgeWithCron);
	}

	/**
	 * Set the items to purge.
	 *
	 * @param array $itemsToPurge
	 *
	 * @return bool
	 */
	private function setItemsToPurge( array $itemsToPurge ) {
		return sb_update_option( 'cf_items_to_purge', $itemsToPurge );
	}

	/**
	 * Get the items to purge.
	 *
	 * @return array
	 */
	public function getItemsToPurge() {
		$items = sb_get_option('cf_items_to_purge');
		if ( ! is_array($items) ) return [];
		return $items;
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

		// Front page
		if ( $front_page_id = get_option( 'page_on_front' ) ) {
			array_push( $purgeUrls, get_permalink($front_page_id) );
		}

		// Posts page
		if ( $blog_id = get_option( 'page_for_posts' ) ) {
			array_push( $purgeUrls, get_permalink($blog_id) );
		}

		// The post
		if ( $permalink = get_permalink( $postId ) ) {
			array_push( $purgeUrls, $permalink );
		}

		// Archive page
		if ( $archiveUrl = get_post_type_archive_link( get_post_type( $postId ) ) ) {
			array_push( $purgeUrls, $archiveUrl );
		}

		// Prevent duplicates
		$purgeUrls = array_unique($purgeUrls);

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

		// If this is just a revision, don't purge anything.
		if ( ! $postId || wp_is_post_revision( $postId ) ) return false;

		// If cron purge is enabled, build the list of ids to purge by cron. If not active, just purge right away.
		if ( $this->cronPurgeIsActive() ) {
			return $this->addItemToPurgeQueue($postId);
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
		if ( $postId ) {
			return $this->purgePost($postId);
		} else {
			if ( $this->cronPurgeIsActive() ) {
				return $this->addItemToPurgeQueue($url);
			} else {
				return $this->cf()->purgeUrls([$url]);
			}
		}
	}

	/**
	 * Get all urls that are queued up for purging.
	 *
	 * @param $items
	 *
	 * @return array
	 */
	private function getPurgeUrlsByPostIds(array $items)
	{
		$urls = [];
		foreach ( $items as $item ) {
			if ( is_int($item) ) {
				$urls = array_merge($urls, $this->getPurgeUrlsByPostId($item));
			} else {
				$urls[] = $item;
			}
		}
		$urls = array_unique($urls);
		return $urls;
	}

	/**
	 * Check if we have overridden whether the Cron purge should be active or not.
	 *
	 * @return mixed
	 */
	private function cronActiveStateOverride()
	{
		if ( defined('SERVEBOLT_CF_PURGE_CRON') && is_bool(SERVEBOLT_CF_PURGE_CRON) ) {
			return SERVEBOLT_CF_PURGE_CRON;
		}
	}

	/**
	 * Check whether the Cron-based cache purger should be active.
	 *
	 * @param bool $respectOverride
	 *
	 * @return bool|mixed
	 */
	public function cronPurgeIsActive($respectOverride = true)
	{
		$activeStateOverride = $this->cronActiveStateOverride();
		if ( $respectOverride && is_bool($activeStateOverride) ) {
			return $activeStateOverride;
		}
		return filter_var(sb_get_option('cf_cron_purge'), FILTER_VALIDATE_BOOLEAN) === true;
	}

	/**
	 * Purging Cloudflare cache by cron using a list of IDs updated.
	 */
	public function purgeByCron()
	{
		$urls = $this->getPurgeUrlsByPostIds( $this->getItemsToPurge() );
		if ( ! empty( $urls ) ) {
			$this->cf()->purgeUrls( $urls );
			$this->clearItemsToPurge();
			return true;
		}
		return false;
	}

	/**
	 * Clear items to purge.
	 */
	private function clearItemsToPurge()
	{
		$this->setItemsToPurge([]);
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

}
Servebolt_CF::getInstance();
