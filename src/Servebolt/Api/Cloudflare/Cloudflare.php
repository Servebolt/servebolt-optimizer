<?php

namespace Servebolt\Optimizer\Api\Cloudflare;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Multiton;
use Servebolt\Optimizer\Traits\ClientMethodProxy;
use Servebolt\Optimizer\Sdk\Cloudflare\Cloudflare as CloudflareSdk;

/**
 * Class Cloudflare
 *
 * This class initializes the Cloudflare SDK based on the given blog Id.
 *
 * @package Servebolt\Optimizer\Api\Cloudflare
 */
class Cloudflare
{
    use Multiton, ClientMethodProxy;

    /**
     * @var CloudflareSdk Sdk instance.
     */
    private $client;

    /**
     * Cloudflare constructor.
     * @param null|int $blogId
     */
    private function __construct($blogId = null)
    {
        $authType = $this->getAuthType($blogId);
        $this->client = new CloudflareSdk([
            'authType' => $authType,
            'credentials' => $this->getCredentialsForAuthType($authType, $blogId),
            'zoneId' => $this->getZoneId($blogId),
        ]);
    }

    /**
     * The Cloudflare API permissions required for this plugin.
     *
     * @param bool $humanReadable
     *
     * @return array|string
     */
    public function apiPermissionsNeeded($humanReadable = true)
    {
        $permissions = ['Zone.Zone', 'Zone.Cache Purge'];
        if ( $humanReadable ) {
            return sb_natural_language_join($permissions);
        }
        return $permissions;
    }

    /**
     * Get active zone Id.
     *
     * @param null|int $blogId
     *
     * @return mixed|void
     */
    private function getZoneId($blogId = null)
    {
        if ( is_numeric($blogId) ) {
            return sb_get_blog_option($blogId, 'cf_zone_id');
        } else {
            return sb_get_option('cf_zone_id');
        }
    }

    /**
     * Get credential from DB.
     *
     * @param $key
     * @param null|int $blogId
     *
     * @return bool|mixed|void
     */
    private function getCredential($key, $blogId = null)
    {
        switch ($key) {
            case 'email':
                $optionName = 'cf_email';
                break;
            case 'api_key':
                $optionName = 'cf_api_key';
                break;
            case 'api_token':
                $optionName = 'cf_api_token';
                break;
        }
        if ( isset($optionName) ) {
            return sb_smart_get_option($blogId, $optionName);
        }
        return false;
    }

    /**
     * Get the Cloudflare API credentials based on the authentication type.
     *
     * @param $authType
     * @param null|int $blogId
     * @return array|null
     */
    private function getCredentialsForAuthType($authType, $blogId = null): ?array
    {
        switch ( $authType ) {
            case 'api_token':
                $apiToken = $this->getCredential('api_token', $blogId);

                if ( ! empty($apiToken) ) {
                    return compact('apiToken');
                }
                break;
            case 'api_key':
                $email = $this->getCredential('email', $blogId);
                $apiKey = $this->getCredential('api_key', $blogId);
                if ( ! empty($email) && ! empty($apiKey) ) {
                    return compact('email', 'apiKey');
                }
                break;
        }
        return null;
    }

    /**
     * The default auth type if none is selected.
     *
     * @return string
     */
    private function getDefaultAuthType(): string
    {
        return 'api_token';
    }

    /**
     * Get authentication type for the Cloudflare API.
     *
     * @param null|int $blogId
     *
     * @return string|void
     */
    private function getAuthType($blogId = null)
    {
        if ( is_numeric($blogId) ) {
            return $this->ensureAuthTypeIntegrity(
                sb_get_blog_option($blogId, 'cf_auth_type',  $this->getDefaultAuthType())
            );
        } else {
            return $this->ensureAuthTypeIntegrity(
                sb_get_option('cf_auth_type',  $this->getDefaultAuthType())
            );
        }
    }

    /**
     * Make sure auth type is specified correctly.
     *
     * @param $authType
     *
     * @return bool|string
     */
    private function ensureAuthTypeIntegrity($authType) {
        switch ($authType) {
            case 'token':
            case 'apiToken':
            case 'api_token':
                return 'api_token';
            case 'key':
            case 'apiKey':
            case 'api_key':
                return 'api_key';
        }
        return $this->getDefaultAuthType();
    }
}
