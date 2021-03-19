<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Servebolt\Optimizer\Admin\GeneralSettings\GeneralSettings;

/**
 * Class Servebolt_CLI_General_Settings_Extra
 */
class Servebolt_CLI_General_Settings_Extra extends Servebolt_CLI_Extras {

    /**
     * Get only the keys of the available settings.
     *
     * @return array
     */
    protected function get_settings_keys()
    {
        $generalSettings = GeneralSettings::getInstance();
        return array_keys($generalSettings->getAllSettingsItems());
    }

    /**
     * Get the setting from general settings instance.
     *
     * @param $setting_key
     * @param bool $blog_id
     * @return bool|mixed|void
     */
    protected function get_setting($setting_key, $blog_id = false) {

        $generalSettings = GeneralSettings::getInstance();
        $raw_value = $generalSettings->getSettingsItem($setting_key, $blog_id);
        $value = sb_display_value($raw_value, true);
        $array = [];
        if ( $blog_id ) {
            $array['URL'] = get_site_url($blog_id);
        }
        $array[$setting_key] = $value;
        return $array;
    }

    /**
     * Set the setting from general settings instance.
     *
     * @param $setting_key
     * @param $value
     * @param bool $blog_id
     * @return bool|mixed|void
     */
    protected function set_setting($setting_key, $value, $blog_id = false)
    {
        $generalSettings = GeneralSettings::getInstance();
        $result = $generalSettings->setSettingsItem($setting_key, $value, $blog_id);
        if ( ! $result ) {
            if ( $blog_id ) {
                WP_CLI::error(sprintf(__('Could not set setting "%s" to value "%s" on site %s'), $setting_key, $value, get_site_url($blog_id)), false);
            } else {
                WP_CLI::error(sprintf(__('Could not set setting "%s" to value "%s"'), $setting_key, $value), false);
            }
            return false;
        }
        if ( $blog_id ) {
            WP_CLI::success(sprintf(__('Setting "%s" set to value "%s" on site %s'), $setting_key, $value, get_site_url($blog_id)));
        } else {
            WP_CLI::success(sprintf(__('Setting "%s" set to value "%s"'), $setting_key, $value));
        }
        return true;
    }

    /**
     * Get all the settings in a conformative array.
     *
     * @return array
     */
    protected function get_settings() {
        $generalSettings = GeneralSettings::getInstance();
        $types = $generalSettings->getRegisteredSettingsItems();
        $formatted_items = [];
        foreach ( $types as $name => $type ) {
            $formatted_items[] = [
                'name'  => str_replace('_', '-', $name),
                'type'  => $type,
            ];
        }
        return $formatted_items;
    }

    /**
     * Resolve the settings key for the specified setting.
     *
     * @param $setting
     * @return bool|string|string[]
     */
    protected function resolve_settings_key($setting) {
        $setting = str_replace('-', '_', $setting);
        if ( in_array($setting, $this->get_settings_keys()) ) {
            return $setting;
        }
        return false;
    }

    /**
     * Display error about a setting that is not defined.
     *
     * @param $setting
     */
    protected function unresolved_setting($setting) {
        WP_CLI::error(sprintf(__('Setting "%s" not found. Please run "wp servebolt general-settings list" to see available settings.'), $setting));
    }

}
