<?php

namespace Servebolt\Optimizer\Cli\GeneralSettings;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\Cli\CliHelpers;
use Servebolt\Optimizer\Admin\GeneralSettings\GeneralSettings as GeneralSettingsAdmin;
use function Servebolt\Optimizer\Helpers\displayValue;

/**
 * Class GeneralSettings
 * @package Servebolt\Optimizer\Cli\GeneralSettings
 */
class GeneralSettings
{

    /**
     * GeneralSettings constructor.
     */
    public function __construct()
    {
        WP_CLI::add_command('servebolt general-settings list', [$this, 'list']);
        WP_CLI::add_command('servebolt general-settings get', [$this, 'get']);
        WP_CLI::add_command('servebolt general-settings set', [$this, 'set']);
    }

    /**
     * Display all available general settings.
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
    public function list($args, $assoc_args)
    {
        $columns = [
            'Name',
            'Type',
            'Value',
        ];
        if (CliHelpers::affectAllSites($assoc_args)) {
            iterateSites(function ($site) use ($columns) {
                $items = array_map(function($item) use ($columns, $site) {
                    $settingsKey = $this->resolveSettingsKey($item['name']);
                    $resolveSettingsValues = $this->getSetting($settingsKey, $site->blog_id);
                    $item['value'] = $resolveSettingsValues[$settingsKey];
                    return array_combine($columns, $item);
                }, $this->getSettings());
                WP_CLI::line(sprintf('%s setting(s) for site "%s":', count($items), get_site_url($site->blog_id)));
                WP_CLI\Utils\format_items( 'table', $items, $columns);
                WP_CLI::line('');
            });
        } else {
            $items = array_map(function($item) use ($columns) {
                $settingsKey = $this->resolveSettingsKey($item['name']);
                $resolveSettingsValues = $this->getSetting($settingsKey);
                $item['value'] = $resolveSettingsValues[$settingsKey];
                return array_combine($columns, $item);
            }, $this->getSettings());
            WP_CLI::line(sprintf('%s setting(s):', count($items)));
            WP_CLI\Utils\format_items( 'table', $items, $columns);
            WP_CLI::line('');
        }
        WP_CLI::line(__('Use "wp servebolt general-settings get [name]" and "wp servebolt general-settings set [name]" to get/set value of a settings.', 'servebolt-wp'));
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
    public function get($args, $assoc_args)
    {
        list($setting) = $args;
        $settingsKey = $this->resolveSettingsKey($setting);
        if (!$settingsKey) {
            $this->unresolvedSetting($setting);
            return;
        }
        if (CliHelpers::affectAllSites($assoc_args)) {
            $sitesSetting = [];
            iterateSites(function ($site) use ($settingsKey, &$sitesSetting) {
                $sitesSetting[] = $this->getSetting($settingsKey, $site->blog_id);
            });
        } else {
            $sitesSetting[] = $this->getSetting($settingsKey);
        }
        WP_CLI\Utils\format_items('table', $sitesSetting, array_keys(current($sitesSetting)));
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
    public function set($args, $assoc_args)
    {
        list($setting, $value) = $args;
        $settingsKey = $this->resolveSettingsKey($setting);
        if (!$settingsKey) {
            $this->unresolvedSetting($setting);
            return;
        }
        if (CliHelpers::affectAllSites($assoc_args)) {
            iterateSites(function ($site) use ($settingsKey, $value) {
                $this->setSetting($settingsKey, $value, $site->blog_id);
            });
        } else {
            $this->setSetting($settingsKey, $value);
        }
    }

    /**
     * Get only the keys of the available settings.
     *
     * @return array
     */
    protected function getSettingsKeys()
    {
        $generalSettings = GeneralSettingsAdmin::getInstance();
        return array_keys($generalSettings->getAllSettingsItems());
    }

    /**
     * Get the setting from general settings instance.
     *
     * @param $settingKey
     * @param bool $blogId
     * @return bool|mixed|void
     */
    protected function getSetting($settingKey, $blogId = false) {

        $generalSettings = GeneralSettingsAdmin::getInstance();
        $rawValue = $generalSettings->getSettingsItem($settingKey, $blogId);
        $value = displayValue($rawValue, true);
        $array = [];
        if ($blogId) {
            $array['URL'] = get_site_url($blogId);
        }
        $array[$settingKey] = $value;
        return $array;
    }

    /**
     * Set the setting from general settings instance.
     *
     * @param $settingKey
     * @param $value
     * @param bool $blogId
     * @return bool|mixed|void
     */
    protected function setSetting($settingKey, $value, $blogId = false)
    {
        $generalSettings = GeneralSettingsAdmin::getInstance();
        $result = $generalSettings->setSettingsItem($settingKey, $value, $blogId);
        if (!$result) {
            if ($blogId) {
                WP_CLI::error(sprintf(__('Could not set setting "%s" to value "%s" on site %s', 'servebolt-wp'), $settingKey, $value, get_site_url($blogId)), false);
            } else {
                WP_CLI::error(sprintf(__('Could not set setting "%s" to value "%s"', 'servebolt-wp'), $settingKey, $value), false);
            }
            return false;
        }
        if ($blogId) {
            WP_CLI::success(sprintf(__('Setting "%s" set to value "%s" on site %s', 'servebolt-wp'), $settingKey, $value, get_site_url($blogId)));
        } else {
            WP_CLI::success(sprintf(__('Setting "%s" set to value "%s"', 'servebolt-wp'), $settingKey, $value));
        }
        return true;
    }

    /**
     * Get all the settings in a conformative array.
     *
     * @return array
     */
    protected function getSettings()
    {
        $generalSettings = GeneralSettingsAdmin::getInstance();
        $types = $generalSettings->getRegisteredSettingsItems();
        $formattedItems = [];
        foreach ($types as $name => $type) {
            $formattedItems[] = [
                'name' => str_replace('_', '-', $name),
                'type' => $type,
            ];
        }
        return $formattedItems;
    }

    /**
     * Resolve the settings key for the specified setting.
     *
     * @param $setting
     * @return bool|string|string[]
     */
    private function resolveSettingsKey($setting)
    {
        $setting = str_replace('-', '_', $setting);
        if (in_array($setting, $this->getSettingsKeys())) {
            return $setting;
        }
        return false;
    }

    /**
     * Display error about a setting that is not defined.
     *
     * @param $setting
     */
    private function unresolvedSetting($setting): void
    {
        WP_CLI::error(sprintf(__('Setting "%s" not found. Please run "wp servebolt general-settings list" to see available settings.', 'servebolt-wp'), $setting));
    }
}
