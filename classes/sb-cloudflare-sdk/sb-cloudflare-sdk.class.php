<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Require the request-method class which our SDK class is dependent on
require_once __DIR__ . '/sb-cloudflare-sdk-request-methods.class.php';

/**
 * Class SB_CF_SDK
 * @package Servebolt
 *
 * This class lets us communicate with the Cloudflare API using native the native WP HTTP API.
 * Earlier we used Guzzle for this, but it turns out it created conflicts with a lot of plugins due to lack of namespace separation between Guzzle-versions.
 */
class SB_CF_SDK extends SB_CF_SDK_Request_Methods {

	/**
	 * Singleton instance.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Cloudflare API credentials.
	 *
	 * @var array
	 */
	private $credentials = null;

	/**
	 * Cloudflare API authentication type.
	 *
	 * @var null
	 */
	protected $auth_type = null;

	/**
	 * Cloudflare Zone Id.
	 *
	 * @var null
	 */
	private $zone_id = null;

	/**
	 * Instantiate class.
	 *
	 * @return Cloudflare|null
	 */
	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new SB_CF_SDK();
		}
		return self::$instance;
	}

	/**
	 * Get user details of authenticated user.
	 *
	 * @return mixed
	 */
	public function get_user_details() {
		try {
			$request = $this->request('user');
			return $request['json']->result;
		} catch (Exception $e) {
			return sb_cf_error($e);
		}
	}

	/**
	 * Get user ID of authenticated user.
	 *
	 * @return mixed
	 */
	public function get_user_id() {
		$user = $this->get_user_details();
		return isset($user->id) ? $user->id : false;
	}

	/**
	 * Verify API token.
	 *
	 * @param bool $token
	 *
	 * @return bool
	 */
	public function verify_token($token = false) {
		if ( ! $token ) {
			$token = $this->get_credential('api_token');
		}
		try {
			$request = $this->request('user/tokens/verify', 'GET', [], [
				'Authorization' => 'Bearer ' . $token,
			]);
			return $request['http_code'] === 200;
		} catch (Exception $e) {
			return sb_cf_error($e);
		}
	}

	/**
	 * Verify that we can fetch the user.
	 *
	 * @return bool
	 */
	public function verify_user(): bool {
		return is_string($this->get_user_id());
	}

	/**
	 * Purge one or more URL's in a zone.
	 **
	 * @param array $urls
	 *
	 * @return bool|void
	 */
	public function purge_urls(array $urls) {

		$zone_id = $this->get_zone_id();
		if ( ! $zone_id || empty($zone_id) ) return false;

        // Check whether we should purge all
        $should_purge_all = in_array('all', $urls);

		// Maybe alter URL's before sending to CF?
		$urls = apply_filters('sb_optimizer_urls_to_be_purged', $urls);

		// Only keep the URL's in the cache purge queue array
		$urls = array_filter( $urls, function($url) {
			return $url !== 'all';
		} );

		// Purge all, return error if we cannot execute
		if ( $should_purge_all ) {
			$purge_all_request = $this->purge_all($zone_id);
			if ( $purge_all_request !== true ) {
				return $purge_all_request;
			}
		}

		try {
			$request = $this->request('zones/' . $zone_id . '/purge_cache', 'POST', [
				'files' => $urls,
			]);
			if ( isset($request['json']->result->id) ) {
				return true;
			}
			return false;
		} catch (Exception $e) {
			return sb_cf_error($e);
		}
	}

	/**
	 * Purge all URL's in a zone.
	 *
	 * @param bool $zone_id
	 *
	 * @return bool
	 */
	public function purge_all($zone_id = false) {
		if ( ! $zone_id ) {
			$zone_id = $this->get_zone_id();
		}
		if ( ! $zone_id || empty($zone_id) ) return false;
		try {
			$request = $this->request('zones/' . $zone_id . '/purge_cache', 'POST', [
				'purge_everything' => true,
			]);
			if ( isset($request['json']->result->id) ) {
				return true;
			}
			return false;
		} catch (Exception $e) {
			return sb_cf_error($e);
		}
	}

	/**
	 * Set credentials. Can handle both API key-setup and API token-setup.
	 *
	 * @param string $auth_type
	 * @param array $credentials
	 */
	public function set_credentials(string $auth_type, array $credentials) {
		$this->auth_type = $auth_type;
		$this->credentials = $credentials;
	}

	/**
	 * Get credentials.
	 *
	 * @return array
	 */
	private function get_credentials() {
		return $this->credentials;
	}

	/**
	 * Get specific credential from credentials.
	 *
	 * @param string $key
	 *
	 * @return array|bool|mixed
	 */
	public function get_credential(string $key) {
		$credentials = $this->get_credentials();
		if ( $credentials && array_key_exists($key, $credentials) ) {
			return $credentials[$key];
		}
		return false;
	}

	/**
	 * List zones.
	 *
	 * @return bool|stdClass
	 */
	public function list_zones() {

		// Function arguments
		/*
		string $name = '',
		string $status = '',
		int $page = 1,
		int $perPage = 20,
		string $order = '',
		string $direction = '',
		string $match = 'all'

		$query = [
            'page' => $page,
            'per_page' => $perPage,
            'match' => $match
        ];

        if (!empty($name)) {
            $query['name'] = $name;
        }

        if (!empty($status)) {
            $query['status'] = $status;
        }

        if (!empty($order)) {
            $query['order'] = $order;
        }

        if (!empty($direction)) {
            $query['direction'] = $direction;
        }
		*/

		$query = [
			'page'     => 1,
			'per_page' => 20,
			'match'    => 'all'
		];

		try {
			$request = $this->request('zones', 'GET', $query);
			return $request['json']->result;
		} catch (Exception $e) {
			return sb_cf_error($e);
		}
	}

	/**
	 * Check if zone exists.
	 *
	 * @param string $zone_id
	 *
	 * @return bool
	 */
	private function zone_exists(string $zone_id): bool {
		return $this->get_zone_by_key($zone_id, 'id') !== false;
	}

	/**
	 * Get zone by Id.
	 *
	 * @param string $zone_id
	 *
	 * @return bool|object
	 */
	public function get_zone_by_id(string $zone_id) {
		try {
			$request = $this->request('zones/' . $zone_id);
			return $request['json']->result;
		} catch (Exception $e) {
			return sb_cf_error($e);
		}
	}

	/**
	 * Get zone from Cloudflare by given key.
	 *
	 * @param string $zone_name
	 * @param string $key
	 *
	 * @return bool
	 */
	public function get_zone_by_key(string $zone_name, string $key = 'name') {
		foreach ( $this->list_zones() as $zone ) {
			if ( isset($zone->{ $key }) && $zone->{ $key } === $zone_name ) {
				return $zone;
			}
		}
		return true;
	}

	/**
	 * Set zone Id (to be used when purging cache).
	 *
	 * @param string $zone_id
	 * @param bool $do_zone_check
	 *
	 * @return bool
	 */
	public function set_zone_id(string $zone_id, bool $do_zone_check = true): bool {
		if ( ! $zone_id || ( $do_zone_check && ! $this->zone_exists($zone_id) ) ) return false;
		$this->zone_id = $zone_id;
		return true;
	}

	/**
	 * Get zone Id.
	 *
	 * @return null
	 */
	private function get_zone_id() {
		return $this->zone_id;
	}

}
