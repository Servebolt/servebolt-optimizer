<?php

namespace Servebolt\Optimizer\Cli\Cache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Cli\CliHelpers;
use Servebolt\Optimizer\Sdk\Cloudflare\Cloudflare as CloudflareSdk;
use WP_CLI;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\iterateSites;
use function Servebolt\Optimizer\Helpers\smartUpdateOption;

/**
 * Class CachePurgeSetup
 * @package Servebolt\Optimizer\Cli\Cache
 */
class CfSetup
{

    /**
     * Allowed authentication types for the Cloudflare API.
     *
     * @var array
     */
    private static $allowedAuthTypes = [
        'token' => 'API token',
        'key' => 'API keys',
    ];

    /**
     * CachePurgeSetup constructor.
     */
    public function __construct()
    {
        WP_CLI::add_command('servebolt cf setup', [$this, 'setup']);
    }

    /**
     * Setup procedure for Cloudflare-based cache purge feature.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Setup on all sites in multisite.
     *
     * [--auth-type[=<auth-type>]]
     * : The way we want to authenticate with the Cloudflare API. Required parameter.
     * ---
     * default: token
     * options:
     *   - token
     *   - key
     * ---
     *
     * [--api-token=<api-token>]
     * : Cloudflare API token. Required when auth type is set to "token".
     *
     * [--email=<email>]
     * : Cloudflare e-mail. Required when auth type is set to "key".
     *
     * [--api-key=<api-key>]
     * : Cloudflare API key. Required when auth type is set to "key".
     *
     * [--zone-id=<zone-id>]
     * : Cloudflare Zone. Required parameter.
     *
     * [--disable-validation]
     * : Whether to validate the input data or not.
     *
     * ## EXAMPLES
     *
     *     # Set up feature using API key authentication
     *     wp servebolt cf setup --auth-type=key --email="your@email.com" --api-key="your-api-token" --zone-id="your-zone-id"
     *
     *     # Set up feature using API token authentication, applying configuration to all sites in a multisite
     *     wp servebolt cf setup --auth-type=token --api-token="your-api-token" --zone-id="your-zone-id" --all
     *
     *
     */
    public function setup($args, $assocArgs): void
    {
        $affectAllSites    = CliHelpers::affectAllSites($assocArgs);
        $authType          = arrayGet('auth-type', $assocArgs);
        $apiToken          = arrayGet('api-token', $assocArgs);
        $email             = arrayGet('email', $assocArgs);
        $apiKey            = arrayGet('api-key', $assocArgs);
        $zoneId            = arrayGet('zone-id', $assocArgs);
        $disableValidation = array_key_exists( 'disable-validation', $assocArgs );

        $params = compact('affectAllSites', 'authType', 'apiToken', 'email', 'apiKey', 'zoneId', 'disableValidation');

        self::cfCachePurgeSetup($params);
    }

    /**
     * Non-interactive setup for Cloudflare.
     *
     * @param $params
     */
    private static function cfCachePurgeSetup($params)
    {
        // Validate data
        $validation = self::validateSetupParams($params);
        if ( $validation !== true ) {
            WP_CLI::error_multi_line($validation);
            return;
        }

        if ( $params['affectAllSites'] ) {
            $result = [];
            iterateSites(function($site) use (&$result, $params) {
                $result[$site->blog_id] = self::storeCfConfiguration($params, $site->blog_id);
            });
            $hasFailed = in_array(false, $result, true);
            $allFailed = !in_array(true, $result, true);
            if ($hasFailed) {
                if ($allFailed) {
                    WP_CLI::error(__('Could not set config on any sites.', 'servebolt-wp'));
                } else {
                    $table = [];
                    foreach($result as $key => $value) {
                        $table[] = [
                            __('Blod ID', 'servebolt-wp') => $key,
                            __('Configuration', 'servebolt-wp') => $value ? __('Success', 'servebolt-wp') : __('Failed', 'servebolt-wp'),
                        ];
                    }
                    WP_CLI::warning(__('Action complete, but we failed to apply config to some sites:', 'servebolt-wp'));
                    WP_CLI\Utils\format_items( 'table', $table, array_keys(current($table)));
                }
            } else {
                WP_CLI::success(__('Configuration on all sites!', 'servebolt-wp'));
            }
        } else {
            if (self::storeCfConfiguration($params)) {
                WP_CLI::success(__('Cloudflare configuration stored successfully.', 'servebolt-wp'));
            } else {
                WP_CLI::error(__('Could not store Cloudflare configuration.', 'servebolt-wp'));
            }
        }
    }

    /**
     * @param $params
     * @return array|null
     */
    private static function createSdkInitiationArray($params): ?array
    {
        switch($params['authType']) {
            case 'token':
                return [
                    'authType' => 'api_token',
                    'credentials' => [
                        'apiToken' => $params['apiToken'],
                    ],
                ];
            case 'key':
                return [
                    'authType' => 'api_key',
                    'credentials' => [
                        'email' => $params['email'],
                        'apiKey' => $params['apiKey'],
                    ],
                ];
        }
        return null;
    }

    /**
     * Validate input data.
     *
     * @param $params
     * @return array|bool
     */
    private static function validateSetupParams($params)
    {
        $messages = [];
        $apiConnectionAvailable = false;

        if (!self::authTypeValid($params['authType'], true, false)) {
            $messages[] = __('Authentication type invalid. Must be either "token" or "key".', 'servebolt-wp');
        }

        switch ($params['authType']) {
            case 'key':
                if (empty($params['email'])) {
                    $messages[] = __('E-mail must be specified.', 'servebolt-wp');
                }
                if (empty($params['apiKey'])) {
                    $messages[] = __('API key must be specified.', 'servebolt-wp');
                }
                $cfSdk = new CloudflareSdk(self::createSdkInitiationArray($params));
                if ($cfSdk->verifyUser()) {
                    $apiConnectionAvailable = true;
                } else {
                    $messages[] = __('API connection unsuccessful using API key authentication.', 'servebolt-wp');
                }
                break;

            case 'token':
                if (empty($params['apiToken'])) {
                    $messages[] = __('API token must be specified.', 'servebolt-wp');
                }
                $cfSdk = new CloudflareSdk(self::createSdkInitiationArray($params));
                if ($cfSdk->verifyApiToken()) {
                    $apiConnectionAvailable = true;
                } else {
                    $messages[] = __('API connection unsuccessful using API token authentication.', 'servebolt-wp');
                }
                break;
        }

        if (empty($params['zoneId'])) {
            $messages[] = __('Zone Id must be specified.', 'servebolt-wp');
        } elseif (isset($cfSdk) && $apiConnectionAvailable && ! $params['disableValidation']) {
            $zone = $cfSdk->getZoneById($params['zoneId']);
            if (!$zone) {
                $messages[] = __('Zone is invalid. Make sure that it exists and that we have access to it.', 'servebolt-wp');
            }
        }

        return empty($messages) ? true : $messages;
    }

    /**
     * Store all Cloudflare configuration.
     *
     * @param array $params
     * @param null|int $blogId
     * @return bool
     */
    private static function storeCfConfiguration(array $params, ?int $blogId = null): bool
    {
        smartUpdateOption($blogId, 'cache_purge_switch', true);
        smartUpdateOption($blogId, 'cache_purge_auto', true);
        smartUpdateOption($blogId, 'cache_purge_driver', 'cloudflare');

        smartUpdateOption($blogId, 'cf_zone_id', $params['zoneId']);

        switch ($params['authType']) {
            case 'key':
                smartUpdateOption($blogId, 'cf_auth_type', 'api_key');
                smartUpdateOption($blogId, 'cf_email', $params['email']);
                smartUpdateOption($blogId, 'cf_api_key', $params['apiKey']);
                break;
            case 'token':
                smartUpdateOption($blogId, 'cf_auth_type', 'api_token');
                smartUpdateOption($blogId, 'cf_api_token', $params['apiToken']);
                break;
        }
        return true;
    }

    /**
     * Check if authentication type for the Cloudflare API is valid or not.
     *
     * @param string $authType
     * @param bool $strict
     * @param bool $returnValue
     *
     * @return bool|string
     */
    private static function authTypeValid(string $authType, bool $strict = true, bool $returnValue = false)
    {
        if ($strict) {
            if (in_array($authType, array_keys(self::$allowedAuthTypes), true)) {
                return ($returnValue ? $authType : true);
            }
            return false;
        }

        $authType = mb_strtolower($authType);
        $values = array_map(function ($item) {
            return mb_strtolower($item);
        }, self::$allowedAuthTypes);
        $keys = array_map(function ($item) {
            return mb_strtolower($item);
        }, array_keys(self::$allowedAuthTypes));

        if (in_array($authType, $values, true)) {
            $value = array_flip($values)[$authType];
            return ($returnValue ? $value : true);
        }
        if (in_array($authType, $keys, true)) {
            return ($returnValue ? $authType : true);
        }
        return false;
    }
}
