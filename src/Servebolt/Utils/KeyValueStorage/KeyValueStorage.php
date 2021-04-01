<?php

namespace Servebolt\Optimizer\Utils\KeyValueStorage;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\displayValue;
use function Servebolt\Optimizer\Helpers\formatArrayToCsv;
use function Servebolt\Optimizer\Helpers\smartDeleteOption;
use function Servebolt\Optimizer\Helpers\smartGetOption;
use function Servebolt\Optimizer\Helpers\smartUpdateOption;

/**
 * Class KeyValueStorage
 * @package Servebolt\Optimizer\Utils\KeyValueStorage
 */
class KeyValueStorage
{

    /**
     * @var array Settings items.
     */
    private $settingsItems = [];

    /**
     * @param array $settingsItems
     * @return KeyValueStorage
     */
    public static function init(array $settingsItems): object
    {
        return new self($settingsItems);
    }

    /**
     * KeyValueStorage constructor.
     * @param array $settingsItems
     */
    public function __construct(array $settingsItems)
    {
        $this->registerSettings($settingsItems);
    }

    /**
     * Get settings items.
     *
     * @return array
     */
    public function getSettingsItems(): array
    {
        return $this->settingsItems;
    }

    /**
     * Check whether a setting exists.
     *
     * @param $settingsKey
     * @return bool
     */
    public function settingExists($settingsKey): bool
    {
        $settingsKey = $this->ensureCorrectItemName($settingsKey);
        return in_array($settingsKey, $this->getSettingsItemKeys());
    }

    /**
     * Register settings.
     *
     * @param array $settingsItems
     */
    private function registerSettings(array $settingsItems): void
    {
        $this->settingsItems = $settingsItems;
    }

    /**
     * Get all settings with values.
     *
     * @param int|null $blogId
     * @param bool $extendedInformation
     * @param bool $humanReadable
     * @return array
     */
    public function getSettings(?int $blogId = null, bool $extendedInformation = false, bool $humanReadable = false): array
    {
        $settings = [];
        foreach ($this->getSettingsItems() as $settingItem => $properties) {

            $type = $this->resolveSettingsItemType($settingItem);
            $typeString = $type;
            $value = $this->getValue($settingItem, $blogId);

            if ($humanReadable) {
                switch ($type) {
                    case 'radio':
                        $properties = $this->resolveSettingsItemProperties($settingItem) ?: [];
                        $values = arrayGet('values', $properties, false);
                        if ($values) {
                            $typeString .= sprintf(' (%s)', implode(', ', $values));
                        }
                        break;
                    case 'multi':
                        $value = formatArrayToCsv(array_keys($value));
                        break;
                }
            }

            if ($humanReadable) {
                $value = displayValue($value);
                $settingItem = str_replace('_', '-', $settingItem);
            }

            if ($extendedInformation) {
                $settings[] = [
                    'name' => $settingItem,
                    'type' => $typeString,
                    'value' => $value,
                ];
            } else {
                $settings[$settingItem] = $value;
            }
        }
        if ($humanReadable) {
            $settings = $this->convertKeysToHumanReadable($settings);
        }
        return $settings;
    }

    /**
     * Convert the first letter of the keys to uppercase.
     *
     * @param array $items
     * @return array
     */
    public function convertKeysToHumanReadable(array $items): array
    {
        return array_map(function($item) {
            $array = [];
            foreach ($item as $key => $value) {
                $key = ucfirst($key);
                $key = str_replace('_', ' ', $key);
                $array[$key] = $value;
            }
            return $array;
        }, $items);
    }

    /**
     * Get the keys of all registered settings.
     *
     * @return array
     */
    public function getSettingsItemKeys(): array
    {
        return array_keys($this->settingsItems);
    }

    /**
     * Resolve setting name from item name.
     *
     * @param string $itemName
     * @return string|null
     */
    public function resolveSettingsName(string $itemName): ?string
    {
        $itemName = $this->ensureCorrectItemName($itemName);
        if (in_array($itemName, $this->getSettingsItemKeys())) {
            return $itemName;
        }
        return null;
    }

    /**
     * Make sure options name has underscores instead of hyphens.
     *
     * @param $itemName
     * @return string
     */
    private function ensureCorrectItemName($itemName): string
    {
        return str_replace('-', '_', $itemName);
    }

    /**
     * Resolve setting name from item name.
     *
     * @param string $itemName
     * @return string|null|array
     */
    public function resolveSettingsItemProperties(string $itemName)
    {
        $itemName = $this->ensureCorrectItemName($itemName);
        $items = $this->getSettingsItems();
        if (array_key_exists($itemName, $items)) {
            return $items[$itemName];
        }
        return null;
    }

    /**
     * Resolve settings type from item name.
     *
     * @param string $itemName
     * @return array|null
     */
    public function resolveSettingsItemType(string $itemName): ?string
    {
        if ($properties = $this->resolveSettingsItemProperties($itemName)) {
            if (is_string($properties)) {
                return $properties; // Type
            }
            return arrayGet('type', $properties, 'string');
        }
        return null;
    }

    /**
     * Check if item is specific type.
     *
     * @param string $settingsKey
     * @param $typesToMatch
     * @return bool
     */
    private function itemIsType(string $settingsKey, $typesToMatch): bool
    {
        if (is_string($typesToMatch)) {
            $typesToMatch = [$typesToMatch];
        }
        $itemType = $this->resolveSettingsItemType($settingsKey);
        return in_array($itemType, $typesToMatch);
    }

    /**
     * Check if item has value constraints.
     *
     * @param string $settingsKey
     * @return bool
     */
    public function hasValueConstraints(string $settingsKey): bool
    {
        if ($this->hasValueConstraintsViaFilter($settingsKey)) {
            return true;
        }
        return $this->itemIsType($settingsKey, 'radio');
    }

    private function hasValueConstraintsViaFilter(string $settingsKey): bool
    {
        if (has_filter('servebolt_optimizer_key_value_storage_constraints_for_' . $settingsKey)) {
            return true;
        }
        return false;
    }

    /**
     * Get any values constraints.
     *
     * @param string $settingsKey
     * @return array|null
     */
    public function getValueConstraints(string $settingsKey): ?array
    {
        $itemType = $this->resolveSettingsItemType($settingsKey);
        if ($this->hasValueConstraintsViaFilter($settingsKey)) {
            return apply_filters('servebolt_optimizer_key_value_storage_constraints_for_' . $settingsKey, $itemType);
        }
        switch ($itemType) {
            case 'radio':
                $properties = $this->resolveSettingsItemProperties($settingsKey);
                return arrayGet('values', $properties, null);
        }
        return null;
    }

    /**
     * @param mixed $value
     * @param string $itemType
     * @param null|array $properties
     * @return false|mixed
     */
    public static function formatValueBasedOnType($value, string $itemType, ?array $properties)
    {
        switch ($itemType) {
            case 'radio':
                $allowedValues = arrayGet('values', $properties, []);
                if (!in_array($value, $allowedValues)) {
                    return false;
                }
                break;
            case 'string':
                if (is_null($value)) {
                    $value = ''; // Return empty string on null
                }
                break;
            case 'multi':
                break;
            case 'boolean':
            case 'bool':
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
        }
        return $value;
    }

    /**
     * Get value.
     *
     * @param string $itemName
     * @param int|null $blogId
     * @param null $defaultValue
     * @return mixed|void
     */
    public function getValue(string $itemName, ?int $blogId = null, $defaultValue = null)
    {
        if ($itemName = $this->resolveSettingsName($itemName)) {
            $value = smartGetOption($blogId, $itemName);
            $value = apply_filters('servebolt_optimizer_key_value_storage_get_format_' . $itemName, $value, $itemName, $blogId, $defaultValue);
            $properties = $this->resolveSettingsItemProperties($itemName);
            $itemType = $this->resolveSettingsItemType($itemName);
            $hasValue = !is_null($value);
            if (!$hasValue) {
                return self::formatValueBasedOnType($defaultValue, $itemType, $properties); // Return default value with validation
            }
            return self::formatValueBasedOnType($value, $itemType, $properties);
        }
        return null;
    }

    /**
     * Convert value to human readable value.
     *
     * @param string $itemName
     * @param int|null $blogId
     * @param null $defaultValue
     * @return bool|string|null
     */
    public function getHumanReadableValue(string $itemName, ?int $blogId = null, $defaultValue = null)
    {
        return displayValue($this->getValue($itemName, $blogId, $defaultValue));
    }

    /**
     * Clear value for options.
     *
     * @param string $itemName
     * @param int|null $blogId
     * @return bool
     */
    public function clearValue(string $itemName, ?int $blogId = null): bool
    {
        if ($settingsName = $this->resolveSettingsName($itemName)) {
            smartDeleteOption($blogId, $settingsName);
            return true;
        }
        return false;
    }

    /**
     * Set value.
     *
     * @param string $itemName
     * @param $value
     * @param int|null $blogId
     * @return false
     */
    public function setValue(string $itemName, $value, ?int $blogId = null)
    {
        if ($itemName = $this->resolveSettingsName($itemName)) {
            $properties = $this->resolveSettingsItemProperties($itemName);
            $itemType = $this->resolveSettingsItemType($itemName);

            if (!apply_filters('servebolt_optimizer_key_value_storage_set_validate_' . $itemName, true, $value, $itemName, $blogId, $itemType)) {
                return false;
            }

            switch ($itemType) {
                case 'string':
                    if (!is_string($value)) {
                        return false;
                    }
                    break;
                case 'multi':
                    $value = array_filter(array_map('trim', explode(',')));
                    break;
                case 'radio':
                    $allowedValues = arrayGet('values', $properties, []);
                    if (!in_array($value, $allowedValues)) {
                        return false; // Invalid
                    }
                    break;
                case 'boolean':
                case 'bool':
                    if (in_array($value, [true, 'true', '1'], true)) {
                        $value = 1;
                    } elseif (in_array($value, [false, 'false', '0'], true)) {
                        $value = 0;
                    } else {
                        return false;
                    }
                    break;
            }
            $value = apply_filters('servebolt_optimizer_key_value_storage_set_format_' . $itemName, $value, $itemName, $blogId, $itemType);
            return smartUpdateOption($blogId, $itemName, $value);
        }
        return false;
    }
}
