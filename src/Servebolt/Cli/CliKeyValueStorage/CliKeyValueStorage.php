<?php

namespace Servebolt\Optimizer\Cli\CliKeyValueStorage;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\Utils\KeyValueStorage\KeyValueStorage;
use Servebolt\Optimizer\Cli\CliHelpers;
use function Servebolt\Optimizer\Helpers\naturalLanguageJoin;
use function WP_CLI\Utils\format_items as WP_CLI_FormatItems;
use function Servebolt\Optimizer\Helpers\iterateSites;

/**
 * Class CliKeyValueStorage
 * @package Servebolt\Optimizer\Cli
 */
abstract class CliKeyValueStorage
{

    /**
     * @var string String used for CLI namespace when registering CLI commands.
     */
    protected $namespace;

    /**
     * @var array The settings items.
     */
    protected $settingsItems = [];

    /**
     * @var object|KeyValueStorage
     */
    private $storage;

    /**
     * CliKeyValueStorage constructor.
     */
    public function __construct()
    {
        if (method_exists($this, 'getSettingsItems')) {
            $this->settingsItems = $this->getSettingsItems();
        }
        $this->storage = KeyValueStorage::init($this->settingsItems);
        $this->registerBaseCommands();
    }

    /**
     * Register key value commands.
     */
    protected function registerBaseCommands(): void
    {
        if ($this->namespace) {
            $commandBase = 'servebolt ' . $this->namespace;
            WP_CLI::add_command($commandBase . ' list', [$this, 'list']);
            WP_CLI::add_command($commandBase . ' get', [$this, 'get']);
            WP_CLI::add_command($commandBase . ' set', [$this, 'set']);
            WP_CLI::add_command($commandBase . ' clear', [$this, 'clear']);
        }
    }

    /**
     * Display all available settings.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Display the setting for all sites.
     *
     * [--format=<format>]
     * : Return format.
     * ---
     * default: text
     * options:
     *   - text
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     # List all settings
     *     wp servebolt [namespace] list
     *
     *     # List all settings for all sites in multisite
     *     wp servebolt [namespace] list --all
     *
     *     # List all settings in JSON-format
     *     wp servebolt [namespace] list --format=json
     *
     */
    public function list($args, $assocArgs)
    {
        CliHelpers::setReturnJson($assocArgs);
        if (CliHelpers::affectAllSites($assocArgs)) {
            iterateSites(function ($site) {
                $this->printSettingsList($site->blog_id, function($items) use ($site) {
                    if (!CliHelpers::returnJson()) {
                        return sprintf('%s setting(s) for site "%s":', count($items), get_site_url($site->blog_id));
                    }
                });
            });
        } else {
            $this->printSettingsList(null, function($items) {
                if (!CliHelpers::returnJson()) {
                    return sprintf('%s setting(s):', count($items));
                }
            });
        }
        if (!CliHelpers::returnJson()) {
            WP_CLI::line(__('Available commands:', 'servebolt-wp'));
            WP_CLI::line(__('wp servebolt ' . $this->namespace . ' get [name]', 'servebolt-wp'));
            WP_CLI::line(__('wp servebolt ' . $this->namespace . ' set [name] [value]', 'servebolt-wp'));
            WP_CLI::line(__('wp servebolt ' . $this->namespace . ' clear [name]', 'servebolt-wp'));
        }
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
     * [--format=<format>]
     * : Return format.
     * ---
     * default: text
     * options:
     *   - text
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     # Get settings value
     *     wp servebolt [namespace] get use-native-js-fallback
     *
     *     # Get settings value for all sites in multisite
     *     wp servebolt [namespace] get use-native-js-fallback --all
     *
     *     # Get settings value in JSON-format
     *     wp servebolt [namespace] get use-native-js-fallback --format=json
     *
     */
    public function get($args, $assocArgs)
    {
        CliHelpers::setReturnJson($assocArgs);
        list($settingsKey) = $args;
        if (!$this->storage->settingExists($settingsKey)) {
            $this->unresolvedSetting($settingsKey);
            return;
        }
        if (CliHelpers::affectAllSites($assocArgs)) {
            $settings = [];
            iterateSites(function ($site) use ($settingsKey, &$settings) {
                if (CliHelpers::returnJson()) {
                    $settings[] = [
                        'blog_id' => $site->blog_id,
                        'value' => $this->storage->getValue($settingsKey, $site->blog_id),
                    ];
                } else {
                    $settings[] = [
                        'Blog' => get_site_url($site->blog_id),
                        'Value' => $this->storage->getHumanReadableValue($settingsKey, $site->blog_id),
                    ];
                }

            });
            if (CliHelpers::returnJson()) {
                $this->printJson($settings);
            } else {
                WP_CLI_FormatItems('table', $settings, array_keys(current($settings)));
            }
        } else {
            if (CliHelpers::returnJson()) {
                $this->printJson([
                    'value' => $this->storage->getValue($settingsKey)
                ]);
            } else {
                WP_CLI::line(sprintf(__('Value set to "%s".', 'servebolt-wp'), $this->storage->getHumanReadableValue($settingsKey)));
            }
        }
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
     * [--format=<format>]
     * : Return format.
     * ---
     * default: text
     * options:
     *   - text
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     # Set setting
     *     wp servebolt [namespace] set use-native-js-fallback true
     *
     *     # Set setting on all sites in multisite
     *     wp servebolt [namespace] set use-native-js-fallback true --all
     *
     *     # Set setting and return result in JSON-format
     *     wp servebolt [namespace] set use-native-js-fallback true --format=json
     *
     */
    public function set($args, $assocArgs)
    {
        CliHelpers::setReturnJson($assocArgs);
        list($settingsKey, $value) = $args;
        if (!$this->storage->settingExists($settingsKey)) {
            $this->unresolvedSetting($settingsKey);
            return;
        }
        $hasFailed = false;
        if (CliHelpers::affectAllSites($assocArgs)) {
            iterateSites(function ($site) use ($settingsKey, $value, &$hasFailed) {
                $result = $this->storage->setValue($settingsKey, $value, $site->blog_id);
                if (!$result) {
                    $hasFailed = true;
                }
                $this->setSettingResponse(
                    $settingsKey,
                    $value,
                    $site->blog_id,
                    $result
                );
            });
        } else {
            $result = $this->storage->setValue($settingsKey, $value);
            if (!$result) {
                $hasFailed = true;
            }
            $this->setSettingResponse(
                $settingsKey,
                $value,
                null,
                $result
            );
        }

        $this->maybePrintConstraintMessage($hasFailed, $settingsKey);
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
     * [--format=<format>]
     * : Return format.
     * ---
     * default: text
     * options:
     *   - text
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     # Clear setting
     *     wp servebolt [namespace] clear use-native-js-fallback
     *
     *     # Clear setting for all sites in multisite
     *     wp servebolt [namespace] clear use-native-js-fallback --all
     *
     *     # Clear setting and return result in JSON-format
     *     wp servebolt [namespace] clear use-native-js-fallback --format=json
     *
     */
    public function clear($args, $assocArgs)
    {
        CliHelpers::setReturnJson($assocArgs);
        list($settingsKey) = $args;
        if (!$this->storage->settingExists($settingsKey)) {
            $this->unresolvedSetting($settingsKey);
            return;
        }
        if (CliHelpers::affectAllSites($assocArgs)) {
            iterateSites(function ($site) use ($settingsKey) {
                $this->storage->clearValue($settingsKey, $site->blog_id);
                $this->clearSettingResponse($settingsKey, $site->blog_id);
            });
        } else {
            $this->storage->clearValue($settingsKey);
            $this->clearSettingResponse($settingsKey);
        }
    }

    /**
     * Print message after clearing setting.
     *
     * @param string $settingKey
     * @param int|null $blogId
     * @return bool
     */
    protected function clearSettingResponse(string $settingKey, ?int $blogId = null): bool
    {
        if ($blogId) {
            $message = sprintf(__('Setting "%s" was cleared on site %s.', 'servebolt-wp'), $settingKey, get_site_url($blogId));
        } else {
            $message = sprintf(__('Setting "%s" was cleared.', 'servebolt-wp'), $settingKey);
        }
        if (CliHelpers::returnJson()) {
            $this->printJson(compact('message'));
        } else {
            WP_CLI::success($message);
        }
        return true;
    }

    /**
     * Print message after setting a setting.
     *
     * @param string $settingKey
     * @param mixed $value
     * @param int|null $blogId
     * @param bool $result
     * @return bool
     */
    protected function setSettingResponse(string $settingKey, $value, ?int $blogId = null, bool $result): bool
    {
        if (!$result) {
            if ($blogId) {
                $errorMessage = sprintf(__('Could not set setting "%s" to value "%s" on site %s.', 'servebolt-wp'), $settingKey, $value, get_site_url($blogId));
            } else {
                $errorMessage = sprintf(__('Could not set setting "%s" to value "%s".', 'servebolt-wp'), $settingKey, $value);
            }
            if (CliHelpers::returnJson()) {
                $this->printJson(compact('errorMessage'));
            } else {
                WP_CLI::error($errorMessage, false);
            }
            return false;
        }
        if ($blogId) {
            $message = sprintf(__('Setting "%s" set to value "%s" on site %s.', 'servebolt-wp'), $settingKey, $value, get_site_url($blogId));
        } else {
            $message = sprintf(__('Setting "%s" set to value "%s".', 'servebolt-wp'), $settingKey, $value);
        }
        if (CliHelpers::returnJson()) {
            $this->printJson(compact('message'));
        } else {
            WP_CLI::success($message);
        }
        return true;
    }

    /**
     * Print settings list.
     *
     * @param null|int $blogId
     * @param callable|null $closure
     */
    private function printSettingsList(?int $blogId = null, $closure = null): void
    {
        $items = $this->storage->getSettings($blogId, true, !CliHelpers::returnJson());
        if (is_callable($closure)) {
            WP_CLI::line($closure($items));
        }
        if (CliHelpers::returnJson()) {
            $this->printJson($items);
        } else {
            $columns = array_keys(current($items));
            WP_CLI_FormatItems('table', $items, $columns);
        }
        if (!CliHelpers::returnJson()) {
            WP_CLI::line('');
        }
    }

    /**
     * Display error about a setting that is not defined.
     *
     * @param $setting
     */
    protected function unresolvedSetting($setting): void
    {
        $errorMessage = sprintf(__('Setting "%s" not found. Please run "wp servebolt ' . $this->namespace . ' list" to see available settings.', 'servebolt-wp'), $setting);
        if (CliHelpers::returnJson()) {
            $this->printJson([
                'error' => $errorMessage
            ]);
        } else {
            WP_CLI::error($errorMessage, false);
        }
    }

    /**
     * Display message about value constraints.
     *
     * @param bool $hasFailed
     * @param string $settingsKey
     */
    private function maybePrintConstraintMessage(bool $hasFailed, string $settingsKey): void
    {
        if ($hasFailed && $this->storage->hasValueConstraints($settingsKey)) {
            $valueConstraints = $this->storage->getValueConstraints($settingsKey);
            if (!empty($valueConstraints)) {
                if (count($valueConstraints) > 1) {
                    $errorMessage = sprintf(__('Values need to be either %s.', 'servebolt-wp'), naturalLanguageJoin($valueConstraints, 'or'));
                } else {
                    $errorMessage = sprintf(__('Values need to be "%s".', 'servebolt-wp'), current($valueConstraints));
                }
                if (CliHelpers::returnJson()) {
                    $this->printJson([
                        'error' => $errorMessage
                    ]);
                } else {
                    WP_CLI::error($errorMessage, false);
                }
            }
        }
    }

    /**
     * Print pretty JSON.
     *
     * @param $array
     * @param string $method
     */
    private function printJson($array, $method = 'line'): void
    {
        if (!method_exists('WP_CLI', $method)) {
            $method = 'line';
        }
        $jsonString = json_encode($array, JSON_PRETTY_PRINT);
        if ($method == 'error') {
            WP_CLI::error($jsonString, false);
        } else {
            WP_CLI::$method($jsonString);
        }
    }
}
