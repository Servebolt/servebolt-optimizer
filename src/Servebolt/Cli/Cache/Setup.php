<?php

namespace Servebolt\Optimizer\Cli\Cache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\Cli\CliHelpers;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\hostIsServebolt;
use function Servebolt\Optimizer\Helpers\iterateSites;

/**
 * Class Cache
 * @package Servebolt\Optimizer\Cli\Cache
 */
class Setup
{

    public static function cachePurgeSetup($args, $assocArgs, $interactive = true)
    {
        $affectAllSites    = CliHelpers::affectAllSites($assocArgs);
        $driver            = arrayGet('driver', $assocArgs);
        $autoPurgeContent  = arrayGet('auto-purge-content', $assocArgs);
        $authType          = arrayGet('auth-type', $assocArgs);
        $apiToken          = arrayGet('api-token', $assocArgs);
        $email             = arrayGet('email', $assocArgs);
        $apiKey            = arrayGet('api-key', $assocArgs);
        $zone              = arrayGet('zone-id', $assocArgs);
        $disableValidation = array_key_exists( 'disable-validation', $assocArgs );

        $individualZones = arrayGet('individual-zones', $assocArgs);
        $individualZones = array_key_exists( 'individual-zones', $assocArgs ) ? ( empty($individualZones) ? true : filter_var($individualZones, FILTER_VALIDATE_BOOLEAN) ) : null;

        $params = compact('interactive', 'affectAllSites', 'driver', 'autoPurgeContent', 'authType', 'apiToken', 'email', 'apiKey', 'zone', 'disableValidation', 'individualZones');

        if ($interactive) {
            self::cachePurgeSetupInteractive($params);
        } else {
            self::cachePurgeSetupNonInteractive($params);
        }
    }

    /**
     * Interactive setup for cache purging.
     *
     * @param $params
     */
    private static function cachePurgeSetupInteractive($params)
    {

        $api_connection_available = false;

        WP_CLI::line(__('Welcome!', 'servebolt-wp'));
        WP_CLI::line(__('This guide will set up the cache purge feature on your site.', 'servebolt-wp'));
        WP_CLI::line(__('This allows for automatic cache purge when the contents of your site changes.', 'servebolt-wp'));
        WP_CLI::line(__('Note that this will potentially overwrite any already existing configuration.', 'servebolt-wp'));
        if ($params['affectAllSites']) {
            WP_CLI::warning(__('This will affect all your site in the multisite-network', 'servebolt-wp'));
        }
        WP_CLI::line();
        WP_CLI::confirm(__('Do you want to continue?', 'servebolt-wp'));

        if (is_multisite() && !$params['affectAllSites']) {
            WP_CLI::line(__('It looks like this is a multisite.', 'servebolt-wp'));
            $params['affectAllSites'] = (boolean) CliHelpers::confirm(__('Do you want to setup Cloudflare on all sites in multisite-network?', 'servebolt-wp'));
        }

        CliHelpers::separator();

        $defaultDriver = 'cloudflare';
        if ($params['driver'] && !hostIsServebolt() && $params['driver'] != $defaultDriver) {
            $params['driver'] = $defaultDriver;
        }

        if ($params['driver']) {
            if (!self::driverValid($params['driver'])) {
                WP_CLI::error(sprintf(__('Invalid driver specified: "%s"', 'servebolt-wp'), $params['driver']));
            }
            WP_CLI::success(sprintf(__('Driver is already set to "%s"', 'servebolt-wp'), $params['driver']));
        } else {
            if (hostIsServebolt()) {
                WP_CLI::line(__('Okay, first we need to determine which driver to use.', 'servebolt-wp'));
                $params['driver'] = CliHelpers::collectParameter(__('Select one of the options: ', 'servebolt-wp'), __('Invalid selection, please try again.', 'servebolt-wp'), function ($input) {
                    if (empty($input)) {
                        return false;
                    }
                    return self::driverValid($input, false, true);
                }, function () {
                    WP_CLI::line(__('Which driver should be used when purging cache?', 'servebolt-wp'));
                    foreach (self::$allowedDrivers as $key => $value) {
                        WP_CLI::line(sprintf('[%s] %s', $key, $value));
                    }
                });
            } else {
                $params['driver'] = $defaultDriver;
            }
        }

        switch ($params['driver']) {
            case 'cloudflare':
                WP_CLI::line(__('Okay, first we need to set up the API connection to Cloudflare.', 'servebolt-wp'));
                break;
            case 'acd':

                break;
        }

        if ($params['driver'] === 'cloudflare') {

        }

        // Determine authentication type
        if ($params['authType']) {
            if (!self::authTypeValid($params['authType'])) {
                WP_CLI::error(sprintf(__('Invalid authentication type specified: "%s"', 'servebolt-wp'), $params['auth_type']));
            }
            WP_CLI::success(sprintf(__('Cloudflare API authentication type is already set to "%s"', 'servebolt-wp'), $params['auth_type']));
        } else {
            $params['authType'] = CliHelpers::collectParameter(__('Select one of the options: ', 'servebolt-wp'), __('Invalid selection, please try again.', 'servebolt-wp'), function ($input) {
                if (empty($input)) return false;
                return self::authTypeValid($input, false, true);
            }, function () {
                WP_CLI::line(__('How will you authenticate with the Cloudflare API?', 'servebolt-wp'));
                foreach (self::$allowedAuthTypes as $key => $value) {
                    WP_CLI::line(sprintf('[%s] %s', $key, $value));
                }
            });
        }

        // Collect credentials based on authentication type
        switch ($params['auth_type']) {
            case 'token':
            case 'API Token':
                if ( $params['api_token'] ) {
                    WP_CLI::success(sprintf(__('API token is already set to "%s"', 'servebolt-wp'), $params['api_token']));
                } else {
                    $params['api_token'] = CliHelpers::collectParameter(__('Specify Cloudflare API Token: ', 'servebolt-wp'), __('API cannot be empty.', 'servebolt-wp'));
                }

                $cf = $this->cf_create_cloudflare_cache_instance($params);
                if ( $this->test_api($cf, $params) ) {
                    $api_connection_available = true;
                }

                break;
            case 'key':
            case 'API Keys':
                if ( $params['email'] ) {
                    WP_CLI::success(sprintf(__('E-mail is already set to "%s"', 'servebolt-wp'), $params['email']));
                } else {
                    $params['email'] = $this->collect_parameter(__('Specify Cloudflare username (e-mail): '), __('E-mail cannot be empty.', 'servebolt-wp'));
                }

                if ( $params['api_key'] ) {
                    WP_CLI::success(sprintf(__('API key is already set to "%s"', 'servebolt-wp'), $params['api_key']));
                } else {
                    $params['api_key'] = $this->collect_parameter(__('Specify Cloudflare API key: ', 'servebolt-wp'), __('API key cannot be empty.', 'servebolt-wp'));
                }

                $cf = $this->cf_create_cloudflare_cache_instance($params);
                if ( $this->test_api($cf, $params) ) {
                    $api_connection_available = true;
                }

                break;
            default:
                WP_CLI::error(__('Invalid authentication type, please try again.', 'servebolt-wp'));
                break;
        }

        if ( $params['affect_all_sites'] ) {
            CliHelpers::separator();
        }

        $individual_zone_setup = $params['individual_zones'];
        if ( $params['affect_all_sites'] && $this->multisite_has_multiple_domains() && is_null($individual_zone_setup) ) {
            if ( $individual_zone_setup !== true ) {
                WP_CLI::line(__('Seems like your multisite has multiple domains.', 'servebolt-wp'));
                $individual_zone_setup = $this->confirm(__('Would you like to set an individual Zone ID for each site?', 'servebolt-wp'));
            } else {
                WP_CLI::line(__('Seems like your multisite has multiple domains. Please set an individual Zone ID for each site.', 'servebolt-wp'));
            }
        }

        if ( $params['affect_all_sites'] && $individual_zone_setup ) {

            if ( $params['zone'] ) {
                WP_CLI::warning(sprintf(__('Zone ID is already specified as "%s" via the arguments, but since Zone ID is individual for each site then this value will be ignored.', 'servebolt-wp'), $params['zone']));
            }

            WP_CLI::line('Please follow the guide below to set Zone ID for each site:');
            $params['zone'] = [];

            $first = true;
            iterateSites(function ($site) use ($api_connection_available, &$params, &$first) {
                $params['zone'][$site->blog_id] = $this->select_zone($api_connection_available, $params, $site->blog_id, ! $first);
                $first = false;
            });

        } else {
            $params['zone'] = $this->select_zone($api_connection_available, $params, false, null);
        }

        $this->separator();

        if ( $params['affect_all_sites'] ) {
            $result = [];
            iterateSites(function($site) use (&$result, $params, $individual_zone_setup) {
                if ( $individual_zone_setup ) {
                    $zone = $params['zone'][$site->blog_id]['id'];
                } else {
                    $zone = $params['zone']['id'];
                }
                $result[$site->blog_id] = $this->store_cf_configuration($params['auth_type'], $params, $zone, $site->blog_id) && sb_cf_cache()->cf_toggle_active(true, $site->blog_id);
            });
            $has_failed = in_array(false, $result, true);
            $all_failed = ! in_array(true, $result, true);
            if ( $has_failed ) {
                if ( $all_failed ) {
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
            if ( $this->store_cf_configuration($params['auth_type'], $params, $params['zone']['id']) && sb_cf_cache()->cf_toggle_active(true) ) {
                WP_CLI::success(__('Configuration stored!', 'servebolt-wp'));
            } else {
                WP_CLI::error(__('Hmm, could not store configuration. Please try again and/or contact support.', 'servebolt-wp'));
            }
        }

        WP_CLI::success(__('Cloudflare feature successfully set up!', 'servebolt-wp'));
    }

    /**
     * Allowed authentication types for the Cloudflare API.
     *
     * @var array
     */
    private static $allowedAuthTypes = ['token' => 'API token', 'key' => 'API keys'];

    /**
     * Allowed drivers.
     *
     * @var array
     */
    private static $allowedDrivers = ['cloudflare' => 'Cloudflare', 'acd' => 'Accelerated domains'];

    /**
     * Check if authentication type for the Cloudflare API is valid or not.
     *
     * @param string $driver
     * @param bool $strict
     * @param bool $returnValue
     *
     * @return bool|string
     */
    private static function driverValid(string $driver, bool $strict = true, bool $returnValue = false)
    {

        if ($strict) {
            if (in_array($driver, array_keys(self::$alowedDrivers), true)) {
                return ( $returnValue ? $driver : true );
            }
            return false;
        }

        $driver = mb_strtolower($driver);
        $values = array_map(function ($item) {
            return mb_strtolower($item);
        }, self::$allowedDrivers);
        $keys = array_map(function ($item) {
            return mb_strtolower($item);
        }, array_keys(self::$allowedDrivers));

        if (in_array($driver, $values, true)) {
            $value = array_flip($values)[$driver];
            return ($returnValue ? $value : true);
        }
        if (in_array($driver, $keys, true)) {
            return ($returnValue ? $driver : true);
        }
        return false;
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
                return ( $returnValue ? $authType : true );
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
