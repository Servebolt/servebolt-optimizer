<?php

namespace Servebolt\Optimizer\Api\Cloudflare;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Multiton;
use Servebolt\Optimizer\Traits\ClientMethodProxy;
use Servebolt\Optimizer\Sdk\Cloudflare\Cloudflare as CloudflareSdk;
use function Servebolt\Optimizer\Helpers\naturalLanguageJoin;
use function Servebolt\Optimizer\Helpers\getBlogOption;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\smartGetOption;

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
     * Credential type - "api_key" or "api_token".
     *
     * @var string|void
     */
    private $authType;

    /**
     * Array with credentials.
     *
     * @var array|null
     */
    private $credentials;

    /**
     * Zone Id.
     *
     * @var string|void
     */
    private $zoneId;

    /**
     * Cloudflare constructor.
     * @param null|int $blogId
     */
    public function __construct(?int $blogId = null)
    {
        $this->authType = $this->getAuthType($blogId);
        $this->credentials = $this->getCredentialsForAuthType($this->authType, $blogId);
        $this->zoneId = $this->getZoneId($blogId);
        if ($this->isConfigured()) {
            $this->client = new CloudflareSdk([
                'authType' => $this->authType,
                'credentials' => $this->credentials,
                'zoneId' => $this->zoneId,
            ]);
        }
    }

    /**
     * Check whether we have correct configuration.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->authTypeIsSet()
            && $this->credentialsAreSet()
            && $this->zoneIsSet();
    }

    /**
     * Check that auth type is set.
     *
     * @return bool
     */
    private function authTypeIsSet(): bool
    {
        return !is_null($this->authType);
    }

    /**
     * Check that credentials are valid.
     *
     * @return bool
     */
    private function credentialsAreSet(): bool
    {
        return !is_null($this->credentials); // Credentials will be null if not valid
    }

    /**
     * Check that zone is val
     * @return bool
     */
    private function zoneIsSet(): bool
    {
        return is_string($this->zoneId);
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
            return naturalLanguageJoin($permissions);
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
        if (is_numeric($blogId)) {
            return getBlogOption($blogId, 'cf_zone_id');
        } else {
            return getOption('cf_zone_id');
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
            return smartGetOption($blogId, $optionName);
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
     * @return string|null
     */
    private function getAuthType($blogId = null): ?string
    {
        if ( is_numeric($blogId) ) {
            return $this->ensureAuthTypeIntegrity(
                getBlogOption($blogId, 'cf_auth_type', $this->getDefaultAuthType())
            );
        } else {
            return $this->ensureAuthTypeIntegrity(
                getOption('cf_auth_type', $this->getDefaultAuthType())
            );
        }
        return null;
    }

    /**
     * Make sure auth type is specified correctly.
     *
     * @param $authType
     *
     * @return string
     */
    private function ensureAuthTypeIntegrity($authType): string
    {
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
