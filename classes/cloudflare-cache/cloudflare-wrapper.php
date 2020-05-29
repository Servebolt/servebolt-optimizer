<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Require the Servebolt Cloudflare SDK
require_once __DIR__ . '/sb-cloudflare-sdk.class.php';

/**
 * Class Cloudflare
 * @package Servebolt
 *
 * The class communicates with the Cloudflare API using the WP HTTP API-functions.
 */
class Cloudflare extends SB_CF_SDK {

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
			self::$instance = new Cloudflare();
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
	public function verify_token(bool $token = false) {
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
		try {
			$request = $this->request('zones/' . $zone_id . '/purge_cache', 'DELETE', [
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
	 * @return bool
	 */
	public function purge_all() {
		$zone_id = $this->get_zone_id();
		if ( ! $zone_id || empty($zone_id) ) return false;
		try {
			$request = $this->request('zones/' . $zone_id . '/purge_cache', 'DELETE', [
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
		if ( $credentials && array_key_exists($key, $credentials ) ) {
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
