<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include 'cloudflare-error.php';

use Cloudflare\API\Auth\APIToken;
use Cloudflare\API\Auth\APIKey;
use Cloudflare\API\Adapter\Guzzle;
use Cloudflare\API\Endpoints\Zones;

/**
 * Class Cloudflare
 *
 * This class facilitates the use of the Cloudflare PHP SDK.
 *
 * @package Servebolt
 */
class Cloudflare {

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
	private $auth_type = null;

	/**
	 * Cloudflare Zone Id.
	 *
	 * @var null
	 */
	private $zone_id = null;

	/**
	 * Instantiate class.
	 *
	 * @param bool $credentials
	 *
	 * @return Cloudflare|null
	 */
	public static function get_instance($credentials = false) {
		if ( self::$instance == null ) {
			self::$instance = new Cloudflare($credentials);
		}
		return self::$instance;
	}

	/**
	 * Cloudflare constructor.
	 *
	 * @param $credentials
	 */
	private function __construct($credentials = false) {
		if ( $credentials ) $this->set_credentials($credentials);
	}

	/**
	 * Purge one or more URL's in a zone.
	 **
	 * @param array $urls
	 *
	 * @return bool|void
	 */
	public function purge_urls( array $urls ) {
		$zone_instance = $this->get_zones_instance();
		if ( ! $zone_instance ) return false;
		try {
			return $zone_instance->cachePurge( $this->get_zone_id(), $urls );
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
		$zone_instance = $this->get_zones_instance();
		if ( ! $zone_instance ) return false;
		try {
			return $zone_instance->cachePurgeEverything($this->get_zone_id());
		} catch (Exception $e) {
			return sb_cf_error($e);
		}
	}

	/**
	 * Set credentials. Can handle both API key-setup and API token-setup.
	 *
	 * @param $auth_type
	 * @param $credentials
	 */
	public function set_credentials($auth_type, $credentials) {
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
	 * @param bool $key
	 *
	 * @return array|bool|mixed
	 */
	public function get_credential($key) {
		$credentials = $this->get_credentials();
		if ( array_key_exists($key, $credentials ) ) {
			return $credentials[$key];
		}
		return false;
	}

	/**
	 * Get Cloudflare authentication instance.
	 *
	 * @return bool|\Cloudflare\API\Auth\APIKey
	 */
	private function get_key_instance() {
		switch ( $this->auth_type ) {
			case 'apiToken':
				return new APIToken($this->get_credential('apiToken'));
				break;
			case 'apiKey':
				return new APIKey($this->get_credential('email'), $this->get_credential('apiKey'));
				break;
		}
		return false;
	}

	/**
	 * Get adapter instance.
	 *
	 * @param bool $key
	 *
	 * @return \Cloudflare\API\Adapter\Guzzle
	 */
	private function get_adapter_instance($key = false) {
		if ( ! $key ) $key = $this->get_key_instance();
		if ( ! $key ) return false;
		return new Guzzle( $key );
	}

	/**
	 * Get Zones instance.
	 *
	 * @return bool|Zones
	 */
	private function get_zones_instance() {
		$adapter = $this->get_adapter_instance();
		if ( ! $adapter ) return false;
		return new Zones( $adapter );
	}

	/**
	 * List zones.
	 *
	 * @return bool|stdClass
	 */
	public function list_zones() {
		$zone_instance = $this->get_zones_instance();
		if ( ! $zone_instance ) return false;
		try {
			$zones = $zone_instance->listZones();
			if ( ! $zones ) return false;
			return (array) $zones->result;
		} catch (Exception $e) {
			return sb_cf_error($e);
		}
	}

	/**
	 * Check if zone exists.
	 *
	 * @param $zone_id
	 *
	 * @return bool
	 */
	private function zone_exists($zone_id) {
		return $this->get_zone_by_key($zone_id, 'id') !== false;
	}

	/**
	 * Get zone by Id.
	 *
	 * @param $zone_id
	 *
	 * @return bool|object
	 */
	public function get_zone_by_id($zone_id) {
		$zone_instance = $this->get_zones_instance();
		if ( ! $zone_instance ) return false;
		try {
			$zone = $zone_instance->getZoneById($zone_id);
			if ( ! $zone ) return false;
			return (object) $zone->result;
		} catch (Exception $e) {
			return sb_cf_error($e);
		}
	}

	/**
	 * Get zone from Cloudflare by given key.
	 *
	 * @param $zone_name
	 * @param string $key
	 *
	 * @return bool
	 */
	public function get_zone_by_key($zone_name, $key = 'name') {
		foreach ( $this->list_zones() as $zone ) {
			if ( isset($zone->{ $key }) && $zone->{ $key } === $zone_name ) {
				return $zone;
			}
		}
		return true;
	}

	/**
	 * Set zone Id.
	 *
	 * @param $zone_id
	 * @param bool $do_zone_check
	 *
	 * @return bool
	 */
	public function set_zone_id($zone_id, $do_zone_check = true) {
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


