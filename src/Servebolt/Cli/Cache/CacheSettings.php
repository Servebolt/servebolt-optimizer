<?php

namespace Servebolt\Optimizer\Cli\Cache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Cli\CliKeyValueStorage\CliKeyValueStorage;

/**
 * Class CacheSettings
 * @package Servebolt\Optimizer\Cli\Cache
 */
class CacheSettings extends CliKeyValueStorage
{
    /**
     * @var string The CLI namespace used when interacting with the key-value-storage class.
     */
    protected $namespace = 'cache settings';

    /**
     * @var array Settings items.
     */
    protected $settingsItems = [

        // Accelerated Domains
        'acd_switch' => 'boolean',
        'acd_minify_switch' => 'boolean',

        // HTML / cache
        'cache_404_switch' => 'boolean',
        'fast_404_switch' => 'boolean',
        'fpc_switch' => 'boolean',
        'fpc_settings' => [
            'type' => 'multi',
            'validation' => true,
        ],

        // Cache purge
        'cache_purge_switch' => 'boolean',
        'cache_purge_auto' => 'boolean',
        'cache_purge_driver' => [
            'type' => 'radio',
            'values' => [
                'cloudflare',
                'acd',
            ]
        ],
        'cf_zone_id' => 'string',
        'cf_auth_type' => [
            'type' => 'radio',
            'values' => [
                'api_token',
                'api_key',
            ]
        ],
        'cf_email' => 'string',
        'cf_api_key' => 'string',
        'cf_api_token' => 'string',
        'queue_based_cache_purge' => 'boolean',
    ];

    /**
     * CacheSettings constructor.
     */
    public function __construct()
    {
        new CacheSettingsConstraints;
        parent::__construct();
    }

    /**
     * Display all available settings.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Display the setting for all sites.
     *
     * [--format=<format>]
     * : Return format.
     * ---
     * default: text
     * options:
     *   - text
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     # List all settings
     *     wp servebolt cache settings list
     *
     *     # List all settings for all sites in multisite
     *     wp servebolt cache settings list --all
     *
     *     # List all settings in JSON-format
     *     wp servebolt cache settings list --format=json
     *
     */
    public function list($args, $assocArgs)
    {
        parent::list($args, $assocArgs);
    }

    /**
     * Get the value of a setting.
     *
     * ## OPTIONS
     *
     * <setting>
     * : The name of the setting to get.
     *
     * [--all]
     * : Set the setting for all sites.
     *
     * [--format=<format>]
     * : Return format.
     * ---
     * default: text
     * options:
     *   - text
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     # Get settings value
     *     wp servebolt cache settings get use-native-js-fallback
     *
     *     # Get settings value for all sites in multisite
     *     wp servebolt cache settings get use-native-js-fallback --all
     *
     *     # Get settings value in JSON-format
     *     wp servebolt cache settings get use-native-js-fallback --format=json
     *
     */
    public function get($args, $assocArgs)
    {
        parent::get($args, $assocArgs);
    }

    /**
     * Set the value of a setting.
     *
     * ## OPTIONS
     *
     * <setting>
     * : The name of the setting to set.
     *
     * <value>
     * : The value of the setting.
     *
     * [--all]
     * : Display the setting for all sites.
     *
     * [--format=<format>]
     * : Return format.
     * ---
     * default: text
     * options:
     *   - text
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     # Set setting
     *     wp servebolt cache settings set use-native-js-fallback true
     *
     *     # Set setting on all sites in multisite
     *     wp servebolt cache settings set use-native-js-fallback true --all
     *
     *     # Set setting and return result in JSON-format
     *     wp servebolt cache settings set use-native-js-fallback true --format=json
     *
     */
    public function set($args, $assocArgs)
    {
        parent::set($args, $assocArgs);
    }

    /**
     * Clear the value of a setting.
     *
     * ## OPTIONS
     *
     * <setting>
     * : The name of the setting to set.
     *
     * [--all]
     * : Display the setting for all sites.
     *
     * [--format=<format>]
     * : Return format.
     * ---
     * default: text
     * options:
     *   - text
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     # Clear setting
     *     wp servebolt cache settings clear use-native-js-fallback
     *
     *     # Clear setting for all sites in multisite
     *     wp servebolt cache settings clear use-native-js-fallback --all
     *
     *     # Clear setting and return result in JSON-format
     *     wp servebolt cache settings clear use-native-js-fallback --format=json
     *
     */
    public function clear($args, $assocArgs)
    {
        parent::clear($args, $assocArgs);
    }
}
