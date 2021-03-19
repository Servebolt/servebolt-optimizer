<?php

namespace Servebolt\Optimizer\Admin\GeneralSettings;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\view;
use function Servebolt\Optimizer\Helpers\camelCaseToSnakeCase;
use function Servebolt\Optimizer\Helpers\getServeboltAdminUrl;
use function Servebolt\Optimizer\Helpers\arrayGet;

/**
 * Class SB_General_Settings
 *
 * This class displays the general settings control GUI.
 */
class GeneralSettings
{
    use Singleton;

    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * Nginx_FPC_Controls constructor.
     */
    private function __construct()
    {
        $this->initSettings();
    }

    /**
     * Initialize settings.
     */
    private function initSettings()
    {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Register custom option.
     */
    public function registerSettings(): void
    {
        foreach ($this->getRegisteredSettingsItems() as $key => $type) {
            register_setting('sb-general-settings-options-page', sb_get_option_name($key));
        }
    }

    /**
     * Settings items for general settings.
     *
     * @return array
     */
    public function getRegisteredSettingsItems(): array
    {
        return [
            'use_native_js_fallback' => 'boolean',
            'asset_auto_version'     => 'boolean',
            'use_cloudflare_apo'     => 'boolean',
        ];
    }

    /**
     * Check whether this setting is overridden using a constant.
     *
     * @param $name
     * @param string $prefix
     * @return bool
     */
    public function settingIsOverridden($name, $prefix = 'SERVEBOLT')
    {
        $name = $this->getOverrideConstantName($name, $prefix);
        return defined($name);
    }

    /**
     * Get the value of the override constant.
     *
     * @param $name
     * @param string $prefix
     * @return mixed
     */
    public function getOverrideConstantValue($name, $prefix = 'SERVEBOLT') {
        $name = $this->getOverrideConstantName($name, $prefix);
        return constant($name);
    }

    /**
     * Get the name of the setting override constant.
     *
     * @param $name
     * @param string $prefix
     * @return string
     */
    private function getOverrideConstantName($name, $prefix = 'SERVEBOLT') {
        return rtrim($prefix, '_') . '_' . mb_strtoupper($name);
    }

    /**
     * Magic method to proxy settings with ease.
     *
     * @param $name
     * @param $arguments
     * @return mixed|string|null
     */
    public function __call($name, $arguments)
    {
        $name = camelCaseToSnakeCase($name);
        if ( in_array($name, array_keys($this->getRegisteredSettingsItems())) ) {
            $blog_id = arrayGet(0, $arguments, false);
            return $this->getSettingsItem($name, $blog_id);
        }

        // Trigger error for call to undefined method
        $class = get_class($this);
        $trace = debug_backtrace();
        $file = $trace[0]['file'];
        $line = $trace[0]['line'];
        trigger_error("Call to undefined method $class::$name() in $file on line $line", E_USER_ERROR);
    }

    /**
     * Get all general settings in array.
     *
     * @param bool $blog_id
     * @return array
     */
    public function getAllSettingsItems($blog_id = false) {
        $settings_items = $this->getRegisteredSettingsItems();
        $values = [];
        foreach ( $settings_items as $item => $type ) {
            switch ($item) {
                default:
                    $values[$item] = $this->getSettingsItem($item, $blog_id, $type);
                    break;
            }
        }
        return $values;
    }

    /**
     * Get value of specific general setting, with override taken into consideration.
     *
     * @param $item
     * @param bool $blog_id
     * @param bool $type
     * @param bool $respect_override
     * @return bool|mixed|void
     */
    public function getSettingsItem($item, $blog_id = false, $type = false, $respect_override = true) {
        if ( $respect_override && $this->settingIsOverridden($item) ) {
            $value = $this->getOverrideConstantValue($item);
        } else {
            if ( is_numeric($blog_id) ) {
                $value = sb_get_blog_option($blog_id, $item);
            } else {
                $value = sb_get_option($item);
            }
        }
        if ( ! $type ) {
            $type = $this->resolveSettingsItemType($item);
        }
        switch ($type) {
            case 'boolean':
            case 'bool':
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
        }
        return $value;
    }

    /**
     * Set value of specific general setting.
     *
     * @param $item
     * @param $value
     * @param bool $blog_id
     * @param bool $type
     * @return bool|mixed|void
     */
    public function setSettingsItem($item, $value, $blog_id = false, $type = false)
    {
        if ( ! $type ) {
            $type = $this->resolveSettingsItemType($item);
        }
        $value = trim($value);
        switch ($type) {
            case 'boolean':
            case 'bool':
                if ( ! in_array($value, ['true', 'false', '0', '1']) ) {
                    return false; // Invalid value
                }
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
        }
        if ( is_numeric($blog_id) ) {
            sb_update_blog_option($blog_id, $item, $value);
        } else {
            sb_update_option($item, $value);
        }
        return true;
    }

    /**
     * Resolve the type of the settings item.
     *
     * @param $item
     * @return bool|mixed|string
     */
    private function resolveSettingsItemType($item)
    {
        $items = $this->getRegisteredSettingsItems();
        if ( array_key_exists($item, $items) ) {
            return $items[$item];
        }
        return false;
    }

    /**
     * Display view.
     */
    public function render(): void
    {
        view('general-settings.general-settings', [
            'sbAdminUrl' => getServeboltAdminUrl(),
            'generalSettings' => $this,
        ]);
    }

}
