<?php

namespace Servebolt\Optimizer\Sdk\Cloudflare;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Sdk\Cloudflare\ApiMethods\CachePurge as CachePurgeMethods;
use Servebolt\Optimizer\Sdk\Cloudflare\ApiMethods\Zone as ZoneMethods;
use Servebolt\Optimizer\Sdk\Cloudflare\ApiMethods\User as UserMethods;

/**
 * Class Cloudflare
 *
 * Quick note: Why we don't use the PHP SDK from Cloudflare you wonder? This was due to lack of version separation in Guzzle's namespacing, and this did not work well in the WordPress ecosystem of various plugins.
 *
 * @package Servebolt\Optimizer\Sdk\Cloudflare
 */
class Cloudflare extends HttpClient
{

    use CachePurgeMethods, ZoneMethods, UserMethods;

    /**
     * Cloudflare API credentials.
     *
     * @var array
     */
    private $credentials = [];

    /**
     * Cloudflare API authentication type.
     *
     * @var null
     */
    protected $authType = null;

    /**
     * Cloudflare Zone Id.
     *
     * @var null
     */
    private $zoneId = null;

    /**
     * Cloudflare API URL.
     *
     * @var string
     */
    protected $baseUri = 'https://api.cloudflare.com/client/v4/';

    /**
     * Cloudflare constructor.
     * @param null $args
     */
    public function __construct($args = null)
    {
        if ($this->credentialsOk($args)) {
            $this->setCredentials($args['authType'], $args['credentials']);
            if ($this->zoneOk($args)) {
                $this->setZoneId($args['zoneId'], false);
            }
        }
    }

    /**
     * Check that we got zone Id.
     *
     * @param $args
     * @return bool
     */
    private function zoneOk($args): bool
    {
        return is_array($args)
            && array_key_exists('zoneId', $args);
    }

    /**
     * Check that we got sufficient arguments to initialize the SDK.
     *
     * @param $args
     * @return bool
     */
    private function credentialsOk($args): bool
    {
        return is_array($args)
            && array_key_exists('authType', $args)
            && array_key_exists('credentials', $args)
            && is_array($args['credentials']);
    }

    /**
     * Set credentials. Can handle both API key-setup and API token-setup.
     *
     * @param string $authType
     * @param array $credentials
     */
    public function setCredentials(string $authType, array $credentials)
    {
        $this->authType = $authType;
        $this->credentials = $credentials;
    }

    /**
     * Get credentials.
     *
     * @return array
     */
    private function getCredentials() : array
    {
        return $this->credentials;
    }

    /**
     * Get specific credential from credentials.
     *
     * @param string $key
     *
     * @return array|bool|mixed
     */
    public function getCredential(string $key)
    {
        $credentials = $this->getCredentials();
        if ($credentials && array_key_exists($key, $credentials)) {
            return $credentials[$key];
        }
        return false;
    }

    /**
     * Get zone Id.
     *
     * @return null
     */
    protected function getZoneId()
    {
        return $this->zoneId;
    }

    /**
     * Set zone Id (to be used when purging cache).
     *
     * @param string $zoneId
     * @param bool $doZoneCheck
     *
     * @return bool
     */
    public function setZoneId(string $zoneId, bool $doZoneCheck = true) : bool
    {
        if (!$zoneId || ($doZoneCheck && !$this->zoneExists($zoneId))) {
            return false;
        }
        $this->zoneId = $zoneId;
        return true;
    }
}
