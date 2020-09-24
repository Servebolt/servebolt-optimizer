<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once __DIR__ . '/general-settings-extra.class.php';

/**
 * Class Servebolt_CLI_Cloudflare_Image_Resize
 */
class Servebolt_CLI_General_Settings extends Servebolt_CLI_General_Settings_Extra {


    /**
     * Display all available general settings.
     *
     * ## OPTIONS
     *
     * ## EXAMPLES
     *
     *     wp servebolt general-settings list
     *
     */
    public function command_general_settings_list($args, $assoc_args) {
        $columns = [ 'Name', 'Type' ];
        $items = array_map(function($item) use ($columns) {
            return array_combine($columns, $item);
        }, $this->get_settings());
        WP_CLI::line(sprintf('%s setting(s):', count($items)));
        WP_CLI\Utils\format_items( 'table', $items, $columns);
        WP_CLI::line('');
        WP_CLI::line(sb__('Use "wp servebolt general-settings get [name]" and "wp servebolt general-settings set [name]" to get/set value of a settings.'));
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
    public function command_general_settings_get($args, $assoc_args) {
        list($setting) = $args;
        $settings_key = $this->resolve_settings_key($setting);
        if ( ! $settings_key ) {
            $this->unresolved_setting($setting);
            return;
        }
        if ( $this->affect_all_sites($assoc_args) ) {
            $sites_setting = [];
            sb_iterate_sites(function ($site) use ($settings_key, &$sites_setting) {
                $sites_setting[] = $this->get_setting($settings_key, $site->blog_id);
            });
        } else {
            $sites_setting[] = $this->get_setting($settings_key);
        }
        WP_CLI\Utils\format_items( 'table', $sites_setting, array_keys(current($sites_setting)));
    }

    /**
     * Get the value of a setting.
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
    public function command_general_settings_set($args, $assoc_args) {
        list($setting, $value) = $args;
        $settings_key = $this->resolve_settings_key($setting);
        if ( ! $settings_key ) {
            $this->unresolved_setting($setting);
            return;
        }
        if ( $this->affect_all_sites($assoc_args) ) {
            sb_iterate_sites(function ($site) use ($settings_key, $value) {
                $this->set_setting($settings_key, $value, $site->blog_id);
            });
        } else {
            $this->set_setting($settings_key, $value);
        }
    }

}
