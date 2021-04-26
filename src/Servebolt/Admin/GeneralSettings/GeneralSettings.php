<?php

namespace Servebolt\Optimizer\Admin\GeneralSettings;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Admin\GeneralSettings\Ajax\GeneralSettingsActions;
use Servebolt\Optimizer\AcceleratedDomains\AcceleratedDomains;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\Utils\KeyValueStorage\KeyValueStorage;
use function Servebolt\Optimizer\Helpers\smartGetOption;
use function Servebolt\Optimizer\Helpers\view;
use function Servebolt\Optimizer\Helpers\camelCaseToSnakeCase;
use function Servebolt\Optimizer\Helpers\getServeboltAdminUrl;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\getOptionName;

/**
 * Class GeneralSettings
 *
 * This class displays the general settings control GUI.
 */
class GeneralSettings
{
    use Singleton;

    /**
     * @var string[]
     */
    public static $settingsItems = [
        'use_native_js_fallback' => 'boolean',
        'asset_auto_version' => 'boolean',
        'use_cloudflare_apo' => 'boolean',
    ];

    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * GeneralSettings constructor.
     */
    private function __construct()
    {
        $this->initAjax();
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
     * Add AJAX handling.
     */
    private function initAjax(): void
    {
        new GeneralSettingsActions;
    }

    /**
     * Register custom option.
     */
    public function registerSettings(): void
    {
        foreach (self::getRegisteredSettingsItemKeys() as $itemName) {
            register_setting('sb-general-settings-options-page', getOptionName($itemName));
        }
    }

    /**
     * Get settings items for general settings, keys only.
     *
     * @return array
     */
    public static function getRegisteredSettingsItemKeys(): array
    {
        return array_keys(self::$settingsItems);
    }

    /**
     * Check whether this setting is overridden using a constant.
     *
     * @param $name
     * @param string $prefix
     * @return bool
     */
    public function settingIsOverridden($name, $prefix = 'SERVEBOLT'): bool
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
    public function getOverrideConstantValue($name, $prefix = 'SERVEBOLT')
    {
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
    private function getOverrideConstantName($name, $prefix = 'SERVEBOLT'): string
    {
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
        if (in_array($name, self::getRegisteredSettingsItemKeys())) {
            $blogId = arrayGet(0, $arguments, null);
            return $this->getSettingsItem($name, $blogId);
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
     * @param null|int $blogId
     * @return array
     */
    public function getAllSettingsItems(?int $blogId = null): array
    {
        $settingsItems = self::$settingsItems;
        $values = [];
        foreach ($settingsItems as $item => $type) {
            switch ($item) {
                default:
                    $values[$item] = $this->getSettingsItem($item, $blogId, $type);
                    break;
            }
        }
        return $values;
    }

    /**
     * Get value of specific general setting, with override taken into consideration.
     *
     * @param $item
     * @param null|int $blogId
     * @param bool $type
     * @param bool $respectOverride
     * @return bool|mixed|void
     */
    public function getSettingsItem($item, ?int $blogId = null, $type = false, $respectOverride = true)
    {
        if ($respectOverride && $this->settingIsOverridden($item)) {
            $value = $this->getOverrideConstantValue($item);
        } else {
            $value = smartGetOption($blogId, $item);
        }
        if (!$type) {
            $type = $this->resolveSettingsItemType($item);
        }
        return KeyValueStorage::formatValueBasedOnType($value, $type, null);
    }

    /**
     * Resolve the type of the settings item.
     *
     * @param $item
     * @return bool|mixed|string
     */
    private function resolveSettingsItemType($item)
    {
        $items = self::$settingsItems;
        if (array_key_exists($item, $items)) {
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
            'acdActive' => AcceleratedDomains::isActive(),
        ]);
    }
}
