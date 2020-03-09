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
	private $authType = null;

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
	 **
	 * @param array $urls
	 *
	 * @return bool|void
	 */
	public function purgeUrls( array $urls ) {
		$zoneInstance = $this->getZonesInstance();
		if ( ! $zoneInstance ) return false;
		try {
			return $zoneInstance->cachePurge( $this->getZoneId(), $urls );
		} catch (Exception $e) {
			return cf_error($e);
		}
	}

	/**
	 * Purge all URL's in a zone.
	 *
	 * @return bool
	 */
	public function purgeAll() {
		$zoneInstance = $this->getZonesInstance();
		if ( ! $zoneInstance ) return false;
		try {
			return $zoneInstance->cachePurgeEverything($this->getZoneId());
		} catch (Exception $e) {
			return cf_error($e);
		}
	}

	/**
	 * Set credentials. Can handle both API key-setup and API token-setup.
	 *
	 * @param $authType
	 * @param $credentials
	 */
	public function setCredentials($authType, $credentials) {
		$this->authType = $authType;
		$this->credentials = $credentials;
	}

	/**
	 * Get credentials.
	 *
	 * @return array
	 */
	private function getCredentials()
	{
		return $this->credentials;
	}

	/**
	 * Get specific credential from credentials.
	 *
	 * @param bool $key
	 *
	 * @return array|bool|mixed
	 */
	public function getCredential($key)
	{
		$credentials = $this->getCredentials();
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
	private function getKeyInstance() {
		switch ( $this->authType ) {
			case 'apiToken':
				return new APIToken($this->getCredential('apiToken'));
				break;
			case 'apiKey':
				return new APIKey($this->getCredential('email'), $this->getCredential('apiKey'));
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
	 * List zones.
	 *
	 * @return bool|stdClass
	 */
	public function listZones() {
		$zoneInstance = $this->getZonesInstance();
		if ( ! $zoneInstance ) return false;
		try {
			$zones = $zoneInstance->listZones();
			if ( ! $zones ) return false;
			return (array) $zones->result;
		} catch (Exception $e) {
			return cf_error($e);
		}
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
	 * Get zone by Id.
	 *
	 * @param $zoneId
	 *
	 * @return bool|object
	 */
	public function getZoneById($zoneId) {
		$zoneInstance = $this->getZonesInstance();
		if ( ! $zoneInstance ) return false;
		try {
			$zone = $zoneInstance->getZoneById($zoneId);
			if ( ! $zone ) return false;
			return (object) $zone->result;
		} catch (Exception $e) {
			return cf_error($e);
		}
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
	 * @param bool $doZoneCheck
	 *
	 * @return bool
	 */
	public function setZoneId($zoneId, $doZoneCheck = true)
	{
		if ( ! $zoneId || ( $doZoneCheck && ! $this->zoneExists($zoneId) ) ) return false;
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

}


