<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once SERVEBOLT_PATH . '/classes/sb-cloudflare-sdk/sb-cloudflare-sdk.class.php';

/**
 * Class Servebolt_CF_Cache
 * @package Servebolt
 *
 * This class handles works as a bridge between WordPress and the Cloudflare API SDK.
 */
class Servebolt_CF_Cache {

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
	 * The key to be used when scheduling CF cache purge.
	 *
	 * @var string
	 */
	private $cron_key = 'servebolt_cron_hook_purge_by_cron';

	/**
	 * The default API authentication type.
	 *
	 * @var string
	 */
	private $default_authentication_type = 'api_token';

	/**
	 * Whether we successfully registered credentials for Cloudflare API class.
	 *
	 * @var bool
	 */
	private $credentials_ok = false;

	/**
	 * Blog context - used only in multisite context.
	 *
	 * @var bool
	 */
	private $blog_id = false;

	/**
	 * Instantiate class.
	 *
	 * @return Servebolt_CF_Cache|null
	 */
	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new Servebolt_CF_Cache(true);
		}
		return self::$instance;
	}

	/**
	 * Servebolt_CF_Cache constructor.
	 *
	 * @param bool $init
	 */
	public function __construct($init = false) {
		if ($init) $this->cf_init();
	}

	/**
	 * Instantiate CF class by passing authentication and zone parameters.
	 *
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function cf_init($blog_id = false) {
		$this->blog_id = $blog_id;
		if ( ! $this->register_credentials() ) return false;
		if ( $active_zone = $this->get_active_zone_id() ) $this->cf()->set_zone_id($active_zone, false);
		return true;
	}

	/**
	 * Switch API credentials and zone to the specified blog.
	 *
	 * @param bool $blog_id
	 *
	 * @return bool|null
	 */
	public function cf_switch_to_blog($blog_id = false) {
		if ( $blog_id === false ) {
			return true;
		}
		if ( is_numeric($blog_id) ) {
			$this->cf_init($blog_id);
			return true;
		}
		return null;
	}

	/**
	 * Switch API credentials and zone back to the current blog.
	 */
	public function cf_restore_current_blog() {
		return $this->cf_init(false);
	}

	/**
	 * Get cron key used for scheduling Cloudflare cache purge.
	 *
	 * @return string
	 */
	public function get_cron_key() {
		return $this->cron_key;
	}

	/**
	 * Check whether we should execute cron purge with cron or not. This can be used to only schedule purges but not execute them in a system.
	 *
	 * @return bool
	 */
	public function should_purge_cache_queue() {
		if ( defined('SERVEBOLT_CF_PURGE_CRON_PARSE_QUEUE') && SERVEBOLT_CF_PURGE_CRON_PARSE_QUEUE === false ) return false;
		return true;
	}

	/**
	 * Get Cloudflare instance.
	 *
	 * @return Servebolt_CF_Cache|null
	 */
	public function cf() {
		if ( is_null($this->cf) ) {
			$this->cf = SB_CF_SDK::get_instance();
		}
		return $this->cf;
	}

	/**
	 * The Cloudflare API permissions required for this plugin.
	 *
	 * @param bool $human_readable
	 *
	 * @return array|string
	 */
	public function api_permissions_needed($human_readable = true) {
		$permissions = ['Zone.Zone', 'Zone.Cache Purge'];
		if ( $human_readable ) {
			return sb_natural_language_join($permissions);
		}
		return $permissions;
	}

	/**
	 * Test API connection.
	 *
	 * @param bool $auth_type
	 *
	 * @return bool
	 */
	public function test_api_connection($auth_type = false) {
		try {
			if ( ! $auth_type ) {
				$auth_type = $this->get_authentication_type();
			}
			$auth_type = $this->ensure_auth_type_integrity($auth_type);
			switch ( $auth_type ) {
				case 'api_token':
					return $this->cf()->verify_token();
					break;
				case 'api_key':
					return $this->cf()->verify_user();
					break;
			}
			return false;
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Check if we should use Cloudflare feature.
	 *
	 * @return bool
	 */
	public function should_use_cf_feature() {
		return $this->cf_is_active() && $this->cf_cache_feature_available();
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
	 * Get the blog context with possibility for override.
	 *
	 * @param bool $override
	 *
	 * @return bool
	 */
	private function get_blog_id($override = false) {
		if ( is_numeric($override) ) return $override;
		return $this->blog_id;
	}

	/**
	 * Check if Cloudflare cache feature is active.
	 *
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function cf_is_active($blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		if ( is_numeric( $blog_id ) ) {
			return sb_checkbox_true(sb_get_blog_option($blog_id, $this->cf_active_option_key()));
		} else {
			return sb_checkbox_true(sb_get_option($this->cf_active_option_key()));
		}
	}

	/**
	 * Check if Cloudflare cache feature is active.
	 *
	 * @param bool $state
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function cf_toggle_active(bool $state, $blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		if ( is_numeric($blog_id) ) {
			return sb_update_blog_option($blog_id, $this->cf_active_option_key(), $state);
		} else {
			return sb_update_option($this->cf_active_option_key(), $state);
		}
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
	 * @param $zone_id
	 * @param bool $blog_id
	 *
	 * @return mixed
	 */
	public function get_zone_by_id($zone_id, $blog_id = false) {
		if ( $blog_id ) $this->cf_switch_to_blog($blog_id);
		$zone = $this->cf()->get_zone_by_id($zone_id);
		if ( $blog_id ) $this->cf_restore_current_blog();
		return $zone;
	}

	/**
	 * Clear the active zone.
	 *
	 * @param bool $blog_id
	 */
	public function clear_active_zone($blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		if ( is_numeric( $blog_id ) ) {
			sb_delete_blog_option($blog_id,'cf_zone_id');
		} else {
			sb_delete_option('cf_zone_id');
		}
	}

	/**
	 * Store active zone Id.
	 *
	 * @param $zone_id
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function store_active_zone_id($zone_id, $blog_id = false) {
		if ( is_numeric($blog_id) ) {
			return sb_update_blog_option($blog_id, 'cf_zone_id', $zone_id);
		} else {
			return sb_update_option('cf_zone_id', $zone_id);
		}
	}

	/**
	 * Get active zone Id.
	 *
	 * @param bool $blog_id
	 *
	 * @return mixed|void
	 */
	public function get_active_zone_id($blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		if ( is_numeric($blog_id) ) {
			return sb_get_blog_option($blog_id, 'cf_zone_id');
		} else {
			return sb_get_option('cf_zone_id');
		}
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
	 * @param bool $blog_id
	 *
	 * @return mixed|void
	 */
	public function get_authentication_type($blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		if ( is_numeric($blog_id) ) {
			return sb_get_blog_option($blog_id, 'cf_auth_type',  $this->default_authentication_type);
		} else {
			return sb_get_option('cf_auth_type',  $this->default_authentication_type);
		}
	}

	/**
	 * Set authentication type.
	 *
	 * @param $type
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function set_authentication_type($type, $blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		if ( is_numeric($blog_id) ) {
			return sb_update_blog_option($blog_id, 'cf_auth_type', $type);
		} else {
			return sb_update_option('cf_auth_type', $type);
		}
	}

	/**
	 * Clear API credentials.
	 *
	 * @param bool $blog_id
	 */
	public function clear_credentials($blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		foreach(['cf_auth_type', 'cf_api_token', 'cf_api_key', 'cf_email'] as $key) {
			if ( is_numeric($blog_id) ) {
				sb_delete_blog_option($blog_id, $key);
			} else {
				sb_delete_option($key);
			}
		}
	}

	/**
	 * Set credentials in Cloudflare class.
	 *
	 * @param $auth_type
	 * @param $credentials
	 *
	 * @return mixed
	 */
	public function set_credentials_in_cf_class($auth_type, $credentials) {
		return $this->cf()->set_credentials($auth_type, $credentials);
	}

	/**
	 * Register credentials in class.
	 *
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function register_credentials($blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		$auth_type = $this->get_authentication_type($blog_id);
		$auth_type = $this->ensure_auth_type_integrity($auth_type);
		switch ( $auth_type ) {
			case 'api_token':
				$api_token = $this->get_credential('api_token', $blog_id);
				if ( ! empty($api_token) ) {
					$this->set_credentials_in_cf_class('api_token', compact('api_token'));
					$this->credentials_ok = true;
				}
				break;
			case 'api_key':
				$email = $this->get_credential('email', $blog_id);
				$api_key = $this->get_credential('api_key', $blog_id);
				if ( ! empty($email) && ! empty($api_key) ) {
					$this->set_credentials_in_cf_class('api_key', compact('email', 'api_key'));
					$this->credentials_ok = true;
				}
				break;
		}
		return $this->credentials_ok;
	}

	/**
	 * Register credentials in class.
	 *
	 * @param $auth_type
	 * @param $credentials
	 *
	 * @return bool
	 */
	public function register_credentials_manually($auth_type, $credentials) {
		$auth_type = $this->ensure_auth_type_integrity($auth_type);
		switch ( $auth_type ) {
			case 'api_token':
				$api_token = $credentials['api_token'];
				if ( ! empty($api_token) ) {
					$this->set_credentials_in_cf_class('api_token', compact('api_token'));
					$this->credentials_ok = true;
				}
				break;
			case 'api_key':
				$email = $credentials['email'];
				$api_key = $credentials['api_key'];
				if ( ! empty($email) && ! empty($api_key) ) {
					$this->set_credentials_in_cf_class('api_key', compact('email', 'api_key'));
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
	 * @param bool $blog_id
	 *
	 * @return bool|mixed|void
	 */
	public function get_credential($key, $blog_id = false) {
		switch ($key) {
			case 'email':
				$option_name = 'cf_email';
				break;
			case 'api_key':
				$option_name = 'cf_api_key';
				break;
			case 'api_token':
				$option_name = 'cf_api_token';
				break;
		}
		if ( isset($option_name) ) {
			return sb_smart_get_option($this->get_blog_id($blog_id), $option_name);
		}
		return false;
	}

	/**
	 * Store credential.
	 *
	 * @param $key
	 * @param $value
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	private function store_credential($key, $value, $blog_id = false) {
		switch ($key) {
			case 'email':
				$option_name = 'cf_email';
				break;
			case 'api_key':
				$option_name = 'cf_api_key';
				break;
			case 'api_token':
				$option_name = 'cf_api_token';
				break;
		}
		if ( isset($option_name) ) {
			return sb_smart_update_option($this->get_blog_id($blog_id), $option_name, $value, true);
		}
		return false;
	}

	/**
	 * Store API credentials in DB.
	 *
	 * @param $credentials
	 * @param $auth_type
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function store_credentials($auth_type, $credentials, $blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		$auth_type = $this->ensure_auth_type_integrity($auth_type);
		switch ($auth_type) {
			case 'api_key':
				if ( $this->set_authentication_type($auth_type, $blog_id) && $this->store_credential('email', $credentials['email'], $blog_id) && $this->store_credential('api_key', $credentials['api_key'], $blog_id) ) {
					$this->register_credentials($blog_id);
					return true;
				}
				break;
			case 'api_token':
				if ( $this->set_authentication_type($auth_type, $blog_id) && $this->store_credential('api_token', $credentials['api_token'], $blog_id) ) {
					$this->register_credentials($blog_id);
					return true;
				}
				break;
		}
		return false;
	}

	/**
	 * Make sure auth type is specified correctly.
	 *
	 * @param $auth_type
	 *
	 * @return bool|string
	 */
	private function ensure_auth_type_integrity($auth_type) {
		switch ( $auth_type ) {
			case 'token':
			case 'apiToken':
			case 'api_token':
				return 'api_token';
			case 'key':
			case 'apiKey':
			case 'api_key':
				return 'api_key';
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
	public function add_item_to_purge_queue($item) {
		if ( empty($item) ) return false;
		$items_to_purge_with_cron = $this->get_items_to_purge();
		$items_to_purge_with_cron[] = $item;
		$items_to_purge_with_cron = array_unique($items_to_purge_with_cron);
		return $this->set_items_to_purge($items_to_purge_with_cron);
	}

	/**
	 * Queue up a request to purge all cache.
	 */
	public function add_purge_all_to_purge_queue() {
		return $this->add_item_to_purge_queue(sb_purge_all_item_name());
	}

	/**
	 * Set the items to purge.
	 *
	 * @param array $items_to_purge
	 *
	 * @return bool
	 */
	public function set_items_to_purge( array $items_to_purge ) {
		return sb_update_option( 'cf_items_to_purge', $items_to_purge, false );
	}

	/**
	 * Count the items to purge.
	 *
	 * @return int
	 */
	public function count_items_to_purge() {
		return count($this->get_items_to_purge());
	}

	/**
	 * Check if we have items to purge.
	 *
	 * @return bool
	 */
	public function has_items_to_purge() {
		return $this->count_items_to_purge() > 0;
	}

	/**
	 * Get the items to purge.
	 *
	 * @param bool $limit
	 * @param bool $blog_id
	 *
	 * @return array|mixed|void
	 */
	public function get_items_to_purge($limit = false, $blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		if ( is_numeric($blog_id) ) {
			$items = sb_get_blog_option($blog_id, 'cf_items_to_purge');
		} else {
			$items = sb_get_option('cf_items_to_purge');
		}
		if ( ! is_array($items) ) return [];
		if ( is_numeric($limit) ) {
			$items = array_reverse(array_slice(array_reverse($items), 0, $limit));
		}
		return $items;
	}

	/**
	 * Get all URL's to purge for a post.
	 *
	 * @param $post_id
	 *
	 * @return array
	 */
	private function get_purge_urls_by_post_id( int $post_id ) {
		$purge_urls = [];

		// Front page
		if ( $front_page_id = get_option( 'page_on_front' ) ) {
			array_push( $purge_urls, get_permalink($front_page_id) );
		}

		// Posts page
		if ( $page_for_posts = get_option( 'page_for_posts' ) ) {
			array_push( $purge_urls, get_permalink($page_for_posts) );
		}

		// The post
		if ( $permalink = get_permalink( $post_id ) ) {
			array_push( $purge_urls, $permalink );
		}

		// Archive page
		if ( $archive_url = get_post_type_archive_link( get_post_type( $post_id ) ) ) {
			array_push( $purge_urls, $archive_url );
		}

		// Prevent duplicates
		$purge_urls = array_unique($purge_urls);

		return $purge_urls;
	}

	/**
	 * Purging Cloudflare on save.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return bool|void
	 */
	public function purge_post( int $post_id ) {

		// If this is just a revision, don't purge anything.
		if ( ! $post_id || wp_is_post_revision( $post_id ) ) return false;

		// If cron purge is enabled, build the list of ids to purge by cron. If not active, just purge right away.
		if ( $this->cron_purge_is_active() ) {
			return $this->add_item_to_purge_queue($post_id);
		} else if ( $urls_to_purge = $this->get_purge_urls_by_post_id($post_id) ) {
			return $this->cf()->purge_urls($urls_to_purge);
		}

		return false;
	}

	/**
	 * Purge Cloudflare by URL. Also checks for an archive to purge.
	 *
	 * @param string $url The URL to be purged.
	 *
	 * @return bool|void
	 */
	public function purge_by_url( string $url ) {
		$post_id = url_to_postid( $url );
		if ( $post_id ) {
			return $this->purge_post($post_id);
		} else {
			if ( $this->cron_purge_is_active() ) {
				return $this->add_item_to_purge_queue($url);
			} else {
				return $this->cf()->purge_urls([$url]);
			}
		}
	}

	/**
	 * Purge Cloudflare by post ID. Also checks for an archive to purge.
	 *
	 * @param int $post_id The post ID for the post to be purged.
	 *
	 * @return bool|void
	 */
	public function purge_by_post( int $post_id ) {
		return $this->purge_post($post_id);
	}

	/**
	 * Purge all.
	 *
	 * @return mixed
	 */
	public function purge_all() {
		if ( $this->cron_purge_is_active() ) {
			return $this->add_purge_all_to_purge_queue();
		} else {
			return $this->cf()->purge_all();
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
	public function cron_active_state_override() {
		if ( defined('SERVEBOLT_CF_PURGE_CRON') && is_bool(SERVEBOLT_CF_PURGE_CRON) ) {
			return SERVEBOLT_CF_PURGE_CRON;
		}
	}

	/**
	 * Check if a purge all request is queued.
	 *
	 * @return bool
	 */
	public function has_purge_all_request_in_queue() {
		$items_to_purge = $this->get_items_to_purge();
		return in_array(sb_purge_all_item_name(), $items_to_purge);
	}

	/**
	 * Check whether the Cron-based cache purger should be active.
	 *
	 * @param bool $respect_override
	 * @param bool $blog_id
	 *
	 * @return bool|mixed
	 */
	public function cron_purge_is_active($respect_override = true, $blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		$active_state_override = $this->cron_active_state_override();
		if ( $respect_override && is_bool($active_state_override) ) {
			return $active_state_override;
		}
		if ( is_numeric($blog_id) ) {
			$value = sb_get_blog_option($blog_id, $this->cf_cron_active_option_key());
		} else {
			$value = sb_get_option($this->cf_cron_active_option_key());
		}
		return sb_checkbox_true($value);
	}

	/**
	 * The option name/key we use to store the active state for the Cloudflare cache cron purge feature.
	 *
	 * @return string
	 */
	private function cf_cron_active_option_key() {
		return 'cf_cron_purge';
	}

	/**
	 * Toggle whether Cloudflare cache purge cron should be active or not.
	 *
	 * @param bool $state
	 * @param bool $blog_id
	 *
	 * @return bool|mixed
	 */
	public function cf_toggle_cron_active(bool $state, $blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		if ( is_numeric($blog_id) ) {
			return sb_update_blog_option($blog_id, $this->cf_cron_active_option_key(), $state);
		} else {
			return sb_update_option($this->cf_cron_active_option_key(), $state);
		}
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
	public function clear_items_to_purge() {
		$this->set_items_to_purge([]);
	}

}
