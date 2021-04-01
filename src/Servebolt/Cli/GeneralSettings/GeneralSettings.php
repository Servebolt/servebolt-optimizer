<?php

namespace Servebolt\Optimizer\Cli\GeneralSettings;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Cli\CliKeyValueStorage\CliKeyValueStorage;
use Servebolt\Optimizer\Admin\GeneralSettings\GeneralSettings as GeneralSettingsAdmin;

/**
 * Class GeneralSettings
 * @package Servebolt\Optimizer\Cli\GeneralSettings
 */
class GeneralSettings extends CliKeyValueStorage
{

    /**
     * @var string The CLI namespace used when interacting with the key-value-storage class.
     */
    protected $namespace = 'general-settings';

    /**
     * @return array
     */
    protected function getSettingsItems(): array
    {
        return GeneralSettingsAdmin::$settingsItems;
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
     *     wp servebolt general-settings list --all
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
     *     wp servebolt general-settings get use-native-js-fallback
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
     *     wp servebolt general-settings set use-native-js-fallback true
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
     *     wp servebolt general-settings clear use-native-js-fallback
     *
     */
    public function clear($args, $assocArgs)
    {
        parent::clear($args, $assocArgs);
    }
}
