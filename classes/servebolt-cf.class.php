<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once 'cloudflare-wrapper.class.php';

/**
 * Class Servebolt_CF
 * @package Servebolt
 *
 * This class handles WordPress triggers and actions related to the Cloudflare cache feature.
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
	private $default_authentication_type = 'apiToken';

	/**
	 * Whether we successfully registered credentials for Cloudflare API class.
	 *
	 * @var bool
	 */
	private $credentials_ok = false;

	/**
	 * Instantiate class.
	 *
	 * @param bool $credentials
	 *
	 * @return Servebolt_CF|null
	 */
	public static function get_instance($credentials = false) {
		if ( self::$instance == null ) {
			self::$instance = new ServeboltCF($credentials);
		}
		return self::$instance;
	}

	/**
	 * Servebolt_CF constructor.
	 */
	private function __construct() {
		$this->init_cf();
		$this->register_actions();
		$this->register_cron();
	}

	/**
	 * Instantiate CF class and pass authentication parameters.
	 *
	 * @return bool
	 */
	private function init_cf() {
		if ( ! $this->register_credentials_in_class() ) return false;
		$activeZone = $this->get_active_zone_id();
		if ( $activeZone ) $this->cf()->set_zone_id($activeZone, false);
		return true;
	}

	/**
	 * Register action hooks.
	 */
	private function register_actions() {
		if ( ! $this->cf_is_active() ) return;
		add_action( 'save_post', [$this, 'purgePost'], 99 );
	}

	/**
	 * Register Cron job.
	 */
	private function register_cron() {
		if ( ! $this->cf_is_active() || ! $this->cron_purge_is_active() ) return;
		$closure = [$this, 'purge_by_cron'];
		/*
		if ( ! wp_next_scheduled( $closure ) ) {
			wp_schedule_event(time(), 'every_minute', $closure);
		}
		*/
	}

	/**
	 * Get Cloudflare instance.
	 *
	 * @return ServeboltCF|null
	 */
	private function cf() {
		if ( is_null($this->cf) ) {
			$this->cf = Cloudflare::get_instance();
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
	public function api_permissions_needed($humanReadable = true) {
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
	public function test_api_connection() {
		try {
			$this->cf()->list_zones();
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
	public function cf_cache_feature_available() {
		return $this->credentials_ok() && $this->zone_ok();
	}

	/**
	 * The option name/key we use to store the active state for the Cloudflare cache feature.
	 *
	 * @return string
	 */
	private function cf_active_option_key() {
		return 'cf_switch';
	}

	/**
	 * Check if Cloudflare cache feature is active.
	 *
	 * @return bool
	 */
	public function cf_is_active() {
		return sb_checkbox_true(sb_get_option($this->cf_active_option_key()));
	}

	/**
	 * Check if Cloudflare cache feature is active.
	 *
	 * @param bool $state
	 *
	 * @return bool|mixed
	 */
	public function cf_toggle_active(bool $state) {
		return sb_update_option($this->cf_active_option_key(), $state);
	}

	/**
	 * Check if we got Cloudflare API credentials in place.
	 *
	 * @return bool
	 */
	public function credentials_ok() {
		return $this->credentials_ok === true;
	}

	/**
	 * Check that we have specified a zone.
	 *
	 * @return bool
	 */
	public function zone_ok() {
		$zone = $this->get_active_zone_id();
		return $zone !== false && ! is_null($zone);
	}

	/**
	 * Get zone by Id from Cloudflare.
	 *
	 * @param $zoneId
	 *
	 * @return mixed
	 */
	public function get_zone_by_id($zoneId) {
		return $this->cf()->get_zone_by_id($zoneId);
	}

	/**
	 * Clear the active zone.
	 */
	public function clear_active_zone() {
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
	public function store_active_zone_id($zoneId) {
		return sb_update_option('cf_zone_id', $zoneId);
	}

	/**
	 * Get active zone Id.
	 *
	 * @return mixed|void
	 */
	public function get_active_zone_id() {
		return sb_get_option('cf_zone_id');
	}

	/**
	 * List zones.
	 *
	 * @return mixed
	 */
	public function list_zones() {
		return $this->cf()->list_zones();
	}

	/**
	 * Get authentication type for Cloudflare.
	 *
	 * @return mixed|void
	 */
	public function get_authentication_type() {
		return sb_get_option('cf_auth_type',  $this->default_authentication_type);
	}

	/**
	 * Clear all credentials.
	 */
	public function clear_credentials() {
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
	public function set_credentials_in_cf_class($authType, $credentials) {
		return $this->cf()->set_credentials($authType, $credentials);
	}

	/**
	 * Register credentials in class.
	 *
	 * @return bool
	 */
	private function register_credentials_in_class() {
		switch ( $this->get_authentication_type() ) {
			case 'apiToken':
				$apiToken = $this->get_credential('apiToken');
				if ( ! empty($apiToken) ) {
					$this->set_credentials_in_cf_class('apiToken', compact('apiToken'));
					$this->credentials_ok = true;
				}
				break;
			case 'apiKey':
				$email = $this->get_credential('email');
				$apiKey = $this->get_credential('apiKey');
				if ( ! empty($email) && ! empty($apiKey) ) {
					$this->set_credentials_in_cf_class('apiKey', compact('email', 'apiKey'));
					$this->credentials_ok = true;
				}
				break;
		}
		return $this->credentials_ok;
	}

	/**
	 * Get credential from DB.
	 *
	 * @param $key
	 *
	 * @return bool|mixed|void
	 */
	public function get_credential($key) {
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
	public function store_credentials($credentials, $type) {
		switch ($type) {
			case 'apiKey':
				if ( sb_update_option('cf_auth_type', $type, true) && sb_update_option('cf_email', $credentials['email'], true) && sb_update_option('cf_api_key', $credentials['apiKey'], true) ) {
					$this->register_credentials_in_class();
					return true;
				}
				break;
			case 'apiToken':
				if ( sb_update_option('cf_auth_type', $type, true) && sb_update_option('cf_api_token', $credentials, true) ) {
					$this->register_credentials_in_class();
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
	public function add_item_tp_purge_queue($item) {
		if ( empty($item) ) return false;
		$itemsToPurgeWithCron = $this->get_items_to_purge();
		$itemsToPurgeWithCron[] = $item;
		$itemsToPurgeWithCron = array_unique($itemsToPurgeWithCron);
		return $this->set_items_to_purge($itemsToPurgeWithCron);
	}

	/**
	 * Set the items to purge.
	 *
	 * @param array $itemsToPurge
	 *
	 * @return bool
	 */
	private function set_items_to_purge( array $itemsToPurge ) {
		return sb_update_option( 'cf_items_to_purge', $itemsToPurge );
	}

	/**
	 * Get the items to purge.
	 *
	 * @return array
	 */
	public function get_items_to_purge() {
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
	private function get_purge_urls_by_post_id( int $postId ) {
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
	public function purge_post( int $postId ) {

		// If this is just a revision, don't purge anything.
		if ( ! $postId || wp_is_post_revision( $postId ) ) return false;

		// If cron purge is enabled, build the list of ids to purge by cron. If not active, just purge right away.
		if ( $this->cron_purge_is_active() ) {
			return $this->add_item_to_purge_queue($postId);
		} else if ( $urlsToPurge = $this->get_purge_urls_by_post_id($postId) ) {
			return $this->cf()->purge_urls($urlsToPurge);
		}
	}

	/**
	 * Purge Cloudflare by URL. Also checks for an archive to purge.
	 *
	 * @param string $url The URL to be purged.
	 *
	 * @return bool|void
	 */
	public function purge_by_url( string $url ) {
		$postId = url_to_postid( $url );
		if ( $postId ) {
			return $this->purge_post($postId);
		} else {
			if ( $this->cron_purge_is_active() ) {
				return $this->add_item_to_purge_queue($url);
			} else {
				return $this->cf()->purge_urls([$url]);
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
	private function get_purge_urls_by_post_ids(array $items) {
		$urls = [];
		foreach ( $items as $item ) {
			if ( is_int($item) ) {
				$urls = array_merge($urls, $this->get_purge_urls_by_post_id($item));
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
	private function cron_active_state_override() {
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
	public function cron_purge_is_active($respectOverride = true) {
		$activeStateOverride = $this->cron_active_state_override();
		if ( $respectOverride && is_bool($activeStateOverride) ) {
			return $activeStateOverride;
		}
		return sb_checkbox_true(sb_get_option('cf_cron_purge'));
	}

	/**
	 * Purging Cloudflare cache by cron using a list of IDs updated.
	 */
	public function purge_by_cron() {
		$urls = $this->get_purge_urls_by_post_ids( $this->get_items_to_purge() );
		if ( ! empty( $urls ) ) {
			$this->cf()->purge_urls( $urls );
			$this->clear_items_to_purge();
			return true;
		}
		return false;
	}

	/**
	 * Clear items to purge.
	 */
	private function clear_items_to_purge() {
		$this->set_items_to_purge([]);
	}

	/**
	 * Purge all.
	 *
	 * @return mixed
	 */
	public function purge_all() {
		return $this->cf()->purge_all();
	}

}
Servebolt_CF::get_instance();
