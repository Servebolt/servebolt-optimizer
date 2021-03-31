<?php

namespace Servebolt\Optimizer\Cli\GeneralSettings;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
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
     * Get the setting from general settings instance.
     *
     * @param string $settingKey
     * @param int|null $blogId
     * @return bool|mixed|void
     */
    public function getSetting(string $settingKey, ?int $blogId = null)
    {
        $generalSettings = GeneralSettingsAdmin::getInstance();
        $rawValue = $generalSettings->getSettingsItem($settingKey, $blogId);
        return $this->getSettingResponse($settingKey, $blogId, $rawValue);
    }

    /**
     * Set the setting from general settings instance.
     *
     * @param string $settingKey
     * @param mixed $value
     * @param int|null $blogId
     * @return bool|mixed|void
     */
    public function setSetting(string $settingKey, $value, ?int $blogId = null): bool
    {
        $generalSettings = GeneralSettingsAdmin::getInstance();
        $result = $generalSettings->setSettingsItem(
            $settingKey,
            $value,
            $blogId
        );
        return $this->setSettingResponse(
            $settingKey,
            $value,
            $blogId,
            $result
        );
    }

    /**
     * Get only the keys of the available settings.
     *
     * @return array
     */
    public function getSettingsKeys(): array
    {
        $generalSettings = GeneralSettingsAdmin::getInstance();
        return array_keys($generalSettings->getAllSettingsItems());
    }

    /**
     * Get all the settings in an array.
     *
     * @return array
     */
    public function getSettings(): array
    {
        $generalSettings = GeneralSettingsAdmin::getInstance();
        return $this->formatSettings($generalSettings->getRegisteredSettingsItems());
    }
}
