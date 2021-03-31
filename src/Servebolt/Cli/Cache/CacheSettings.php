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
        'acd_switch' => [
            'type' => 'boolean',
        ],
        'cache_purge_switch' => 'boolean',
        'cache_purge_auto' => [
            'type' => 'boolean',
        ],
        'cache_purge_driver' => [
            'type' => 'radio',
            'values' => [
                'cloudflare',
                'acd',
            ]
        ],
    ];

    /**
     * GeneralSettings constructor.
     */
    public function __construct()
    {
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
     * ## EXAMPLES
     *
     *     wp servebolt cache settings list --all
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
     * ## EXAMPLES
     *
     *     wp servebolt cache settings get use-native-js-fallback
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
     * ## EXAMPLES
     *
     *     wp servebolt cache settings set use-native-js-fallback true
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
     * ## EXAMPLES
     *
     *     wp servebolt cache settings clear use-native-js-fallback
     *
     */
    public function clear($args, $assocArgs)
    {
        parent::clear($args, $assocArgs);
    }
}
