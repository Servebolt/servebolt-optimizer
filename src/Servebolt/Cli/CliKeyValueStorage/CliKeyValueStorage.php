<?php

namespace Servebolt\Optimizer\Cli\CliKeyValueStorage;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\KeyValueStorage\KeyValueStorage;
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
        $this->storage = KeyValueStorage::init($this->settingsItems);
        $this->registerBaseCommands();
    }

    /**
     * Register key value commands.
     */
    protected function registerBaseCommands(): void
    {
        if ($this->namespace) {
            WP_CLI::add_command('servebolt ' . $this->namespace . ' list', [$this, 'list']);
            WP_CLI::add_command('servebolt ' . $this->namespace . ' get', [$this, 'get']);
            WP_CLI::add_command('servebolt ' . $this->namespace . ' set', [$this, 'set']);
            WP_CLI::add_command('servebolt ' . $this->namespace . ' clear', [$this, 'clear']);
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
     * ## EXAMPLES
     *
     *     wp servebolt [settings-namespace] list --all
     *
     */
    public function list($args, $assocArgs)
    {
        if (CliHelpers::affectAllSites($assocArgs)) {
            iterateSites(function ($site) {
                $this->printSettingsList($site->blog_id, function($items) use ($site) {
                    return sprintf('%s setting(s) for site "%s":', count($items), get_site_url($site->blog_id));
                });
            });
        } else {
            $this->printSettingsList(null, function($items) {
                return sprintf('%s setting(s):', count($items));
            });
        }
        WP_CLI::line(__('Available commands:', 'servebolt-wp'));
        WP_CLI::line(__('wp servebolt ' . $this->namespace . ' get [name]', 'servebolt-wp'));
        WP_CLI::line(__('wp servebolt ' . $this->namespace . ' set [name] [value]', 'servebolt-wp'));
        WP_CLI::line(__('wp servebolt ' . $this->namespace . ' clear [name]', 'servebolt-wp'));
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
     *     wp servebolt [namespace] get use-native-js-fallback
     *
     */
    public function get($args, $assocArgs)
    {
        list($settingsKey) = $args;
        if (!$this->storage->settingExists($settingsKey)) {
            $this->unresolvedSetting($settingsKey);
            return;
        }
        if (CliHelpers::affectAllSites($assocArgs)) {
            $settings = [];
            iterateSites(function ($site) use ($settingsKey, &$settings) {
                $settings[] = [
                    'Blog' => get_site_url($site->blog_id),
                    'Value' => $this->storage->getHumanReadableValue($settingsKey, $site->blog_id, null, true),
                ];
            });
            WP_CLI_FormatItems('table', $settings, array_keys(current($settings)));
        } else {
            WP_CLI::line(sprintf(__('Value set to "%s".', 'servebolt-wp'), $this->storage->getHumanReadableValue($settingsKey)));
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
     * ## EXAMPLES
     *
     *     wp servebolt [namespace] set use-native-js-fallback true
     *
     */
    public function set($args, $assocArgs)
    {
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
     * ## EXAMPLES
     *
     *     wp servebolt [namespace] clear use-native-js-fallback
     *
     */
    public function clear($args, $assocArgs)
    {
        list($settingsKey) = $args;
        if (!$this->storage->settingExists($settingsKey)) {
            $this->unresolvedSetting($settingsKey);
            return;
        }
        if (CliHelpers::affectAllSites($assocArgs)) {
            iterateSites(function ($site) use ($settingsKey) {
                $this->storage->clearValue($settingsKey, $site->blog_id);
                $this->clearSettingResponse();
            });
        } else {
            $this->storage->clearValue($settingsKey);
        }
    }

    /**
     * @param string $settingKey
     * @param mixed $value
     * @param int|null $blogId
     * @param bool $result
     * @return bool
     */
    protected function clearSettingResponse(string $settingKey, $value, ?int $blogId = null, bool $result): bool
    {
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
     * @param null|int $blogId
     * @param callable|null $closure
     */
    private function printSettingsList(?ont $blogId = null, $closure = null): void
    {
        $items = $this->storage->getSettings($blogId, true, true);
        $columns = array_keys(current($items));
        if (is_callable($closure)) {
            WP_CLI::line($closure($items));
        }
        WP_CLI_FormatItems('table', $items, $columns);
        WP_CLI::line('');
    }

    /**
     * Display error about a setting that is not defined.
     *
     * @param $setting
     */
    protected function unresolvedSetting($setting): void
    {
        WP_CLI::error(sprintf(__('Setting "%s" not found. Please run "wp servebolt ' . $this->namespace . ' list" to see available settings.', 'servebolt-wp'), $setting));
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
                    WP_CLI::error(sprintf(__('Values need to be either %s', 'servebolt-wp'), naturalLanguageJoin($valueConstraints, 'or')), false);
                } else {
                    WP_CLI::error(sprintf(__('Values need to be "%s"', 'servebolt-wp'), current($valueConstraints)), false);
                }
            }
        }
    }

    /*
    protected function formatSettings(array $types): array
    {
        $formattedItems = [];
        foreach ($types as $name => $type) {
            $formattedItems[] = [
                'name' => str_replace('_', '-', $name),
                'type' => $type,
            ];
        }
        return $formattedItems;
    }
    */
}
