<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Cloudflare\API\Auth\APIToken;
use Cloudflare\API\Auth\APIKey;
use Cloudflare\API\Adapter\Guzzle;
use Cloudflare\API\Endpoints\Zones;
use Cloudflare\API\Endpoints\User;

/**
 * Class Cloudflare
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
	 * Cloudflare Zone Id.
	 *
	 * @var null
	 */
	private $zoneId = null;

	/**
	 * Instantiate class.
	 *
	 * @param bool $credentials
	 *
	 * @return Servebolt_CF|null
	 */
	public static function getInstance($credentials = false) {
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
		if ( $credentials ) $this->setCredentials($credentials);
	}

	/**
	 * Purge one or more URL's in a zone.
	 *
	 * @param array $urls
	 *
	 * @return bool
	 * @throws \Cloudflare\API\Endpoints\EndpointException
	 */
	public function purgeUrls( array $urls ) {
		$zoneInstance = $this->getZonesInstance();
		if ( ! $zoneInstance ) return false;
		return $zoneInstance->cachePurge( $this->getZoneId(), $urls );
	}

	/**
	 * Purge all URL's in a zone.
	 *
	 * @return bool
	 */
	public function purgeAll() {
		$zoneInstance = $this->getZonesInstance();
		if ( ! $zoneInstance ) return false;
		return $zoneInstance->cachePurgeEverything($this->getZoneId());
	}

	/**
	 * Set credentials. Can handle both API key-setup and API token-setup.
	 *
	 * @param $credentials
	 *
	 * @return bool
	 */
	public function setCredentials($credentials) {
		switch ( $this->getCredentialType($credentials) ) {
			case 'apiToken':
			case 'apiKey':
				$this->credentials = $credentials;
				return true;
		}
		return false;
	}

	/**
	 * Check what kind of credentials was passed.
	 *
	 * @param bool $credentials
	 *
	 * @return bool|string
	 */
	private function getCredentialType($credentials = false) {
		if ( $credentials === false ) $credentials = $this->credentials;
		if ( ! $credentials ) return false;
		if ( is_string($credentials) && ! empty($credentials) ) {
			return 'apiToken';
		} else if ( is_array($credentials) ) {
			$email = array_key_exists('email', $credentials) ? $credentials['email'] : false;
			$apiKey = array_key_exists('apiKey', $credentials) ? $credentials['apiKey'] : false;
			if ( $email && $apiKey ) {
				return 'apiKey';
			}
		}
		return false;
	}

	/**
	 * Get Cloudflare authentication instance.
	 *
	 * @return bool|\Cloudflare\API\Auth\APIKey
	 */
	private function getKeyInstance() {
		switch ( $this->getCredentialType() ) {
			case 'apiToken':
				return new APIToken($this->getCredential());
				break;
			case 'apiKey':
				return new APIKey( $this->getCredential('email'), $this->getCredential('apiKey') );
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
	private function getAdapterInstance($key = false) {
		if ( ! $key ) $key = $this->getKeyInstance();
		if ( ! $key ) return false;
		return new Guzzle( $key );
	}

	/**
	 * Get User instance.
	 *
	 * @return bool|User
	 */
	public function getUserInstance() {
		$adapter = $this->getAdapterInstance();
		if ( ! $adapter ) return false;
		return new User($adapter);
	}

	/**
	 * Get Zones instance.
	 *
	 * @return bool|Zones
	 */
	private function getZonesInstance() {
		$adapter = $this->getAdapterInstance();
		if ( ! $adapter ) return false;
		return new Zones( $adapter );
	}

	/**
	 * Get zone by Id.
	 *
	 * @param $zoneId
	 *
	 * @return bool|object
	 */
	public function getZoneById($zoneId) {
		$zoneInstance = $this->getZonesInstance();
		if ( ! $zoneInstance ) return false;
		$zone = $zoneInstance->getZoneById($zoneId);
		if ( ! $zone ) return false;
		return (object) $zone->result;
	}

	/**
	 * List zones.
	 *
	 * @return bool|stdClass
	 */
	public function listZones() {
		$zoneInstance = $this->getZonesInstance();
		if ( ! $zoneInstance ) return false;
		$zones = $zoneInstance->listZones();
		if ( ! $zones ) return false;
		return (array) $zones->result;
	}

	/**
	 * Check if zone exists.
	 *
	 * @param $zoneId
	 *
	 * @return bool
	 */
	private function zoneExists($zoneId)
	{
		return $this->getZoneByKey($zoneId, 'id') !== false;
	}

	/**
	 * Get zone from Cloudflare by given key.
	 *
	 * @param $zoneName
	 * @param string $key
	 *
	 * @return bool
	 */
	public function getZoneByKey($zoneName, $key = 'name')
	{
		foreach ( $this->listZones() as $zone ) {
			if ( isset($zone->{ $key }) && $zone->{ $key } === $zoneName ) {
				return $zone;
			}
		}
		return true;
	}

	/**
	 * Set zone Id.
	 *
	 * @param $zoneId
	 *
	 * @return bool
	 */
	public function setZoneId($zoneId)
	{
		if ( ! $zoneId || ! $this->zoneExists($zoneId) ) return false;
		$this->zoneId = $zoneId;
		return true;
	}

	/**
	 * Get zone Id.
	 *
	 * @return null
	 */
	private function getZoneId()
	{
		return $this->zoneId;
	}

	/**
	 * Get specific credential from credentials.
	 *
	 * @param bool $key
	 *
	 * @return array|bool|mixed
	 */
	private function getCredential($key = false) {

		if ( $key ) {
			if ( array_key_exists($key, $this->credentials ) ) {
				return $this->credentials[$key];
			}
		} else {
			return $this->credentials;
		}
		return false;
	}

}


