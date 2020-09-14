<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class SB_General_Settings
 *
 * This class displays the general settings control GUI.
 */
class SB_General_Settings {

    /**
     * @var null Singleton instance.
     */
    private static $instance = null;

    /**
     * Singleton instantiation.
     *
     * @return SB_General_Settings|null
     */
    public static function get_instance() {
        if ( self::$instance == null ) {
            self::$instance = new SB_General_Settings;
        }
        return self::$instance;
    }

    /**
     * Nginx_FPC_Controls constructor.
     */
    private function __construct() {
        $this->init_settings();
    }

    /**
     * Initialize settings.
     */
    private function init_settings() {
        add_action( 'admin_init', [$this, 'register_settings'] );
    }

    /**
     * Register custom option.
     */
    public function register_settings() {
        foreach($this->registered_settings_items() as $key => $type) {
            register_setting('sb-general-settings-options-page', sb_get_option_name($key));
        }
    }

    /**
     * Settings items for general settings.
     *
     * @return array
     */
    public function registered_settings_items() {
        return [
            'use_native_js_fallback' => 'boolean',
        ];
    }

    /**
     * Check whether this setting is overridden using a constant.
     *
     * @param $name
     * @param string $prefix
     * @return bool
     */
    public function setting_is_overridden($name, $prefix = 'SERVEBOLT') {
        $name = $this->get_override_constant_name($name, $prefix);
        return defined($name);
    }

    /**
     * Get the value of the override constant.
     *
     * @param $name
     * @param string $prefix
     * @return mixed
     */
    public function get_override_constant_value($name, $prefix = 'SERVEBOLT') {
        $name = $this->get_override_constant_name($name, $prefix);
        return constant($name);
    }

    /**
     * Get the name of the setting override constant.
     *
     * @param $name
     * @param string $prefix
     * @return string
     */
    private function get_override_constant_name($name, $prefix = 'SERVEBOLT') {
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
        if ( in_array($name, array_keys($this->registered_settings_items())) ) {
            $settings_items = $this->get_all_settings_items();
            return $settings_items[$name];
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
    public function get_all_settings_items($blog_id = false) {
        $settings_items = $this->registered_settings_items();
        $values = [];
        foreach ( $settings_items as $item => $type ) {
            switch ($item) {
                default:
                    $values[$item] = $this->get_settings_item($item, $blog_id, $type);
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
    public function get_settings_item($item, $blog_id = false, $type = false, $respect_override = true) {
        if ( $respect_override && $this->setting_is_overridden($item) ) {
            $value = $this->get_override_constant_value($item);
        } else {
            if ( is_numeric($blog_id) ) {
                $value = sb_get_blog_option($blog_id, $item);
            } else {
                $value = sb_get_option($item);
            }
        }
        if ( ! $type ) {
            $type = $this->resolve_settings_item_type($item);
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
    public function set_settings_item($item, $value, $blog_id = false, $type = false) {
        if ( ! $type ) {
            $type = $this->resolve_settings_item_type($item);
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
            return sb_update_blog_option($blog_id, $item, $value, true);
        } else {
            return sb_update_option($item, $value, true);
        }
    }

    /**
     * Resolve the type of the settings item.
     *
     * @param $item
     * @return bool|mixed|string
     */
    private function resolve_settings_item_type($item) {
        $items = $this->registered_settings_items();
        if ( array_key_exists($item, $items) ) {
            return $items[$item];
        }
        return false;
    }

    /**
     * Display view.
     */
    public function view() {
        sb_view('admin/views/general-settings', [
            'sb_admin_url' => sb_get_admin_url(),
        ]);
    }

}
SB_General_Settings::get_instance();
