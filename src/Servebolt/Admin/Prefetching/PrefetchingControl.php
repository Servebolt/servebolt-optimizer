<?php

namespace Servebolt\Optimizer\Admin\Prefetching;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\AcceleratedDomains\Prefetching\ManifestDataModel;
use Servebolt\Optimizer\AcceleratedDomains\Prefetching\ManifestFilesModel;
use Servebolt\Optimizer\AcceleratedDomains\Prefetching\ManifestFileWriter;
use Servebolt\Optimizer\AcceleratedDomains\Prefetching\WpPrefetching;
use Servebolt\Optimizer\Admin\Prefetching\Ajax\PrefetchingFileGeneration;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\getVersionForStaticAsset;
use function Servebolt\Optimizer\Helpers\isScreen;
use function Servebolt\Optimizer\Helpers\listenForCheckboxOptionChange;
use function Servebolt\Optimizer\Helpers\listenForOptionChange;
use function Servebolt\Optimizer\Helpers\overrideMenuTitle;
use function Servebolt\Optimizer\Helpers\overrideParentMenuPage;
use function Servebolt\Optimizer\Helpers\view;

/**
 * Class PrefetchingControl
 * @package Servebolt\Optimizer\Admin\PrefetchingControl
 */
class PrefetchingControl
{
    use Singleton;

    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * PrefetchingControl constructor.
     */
    public function __construct()
    {
        $this->initSettingsActions();
        $this->initSettings();
        $this->initAssets();
        $this->initAjax();
        $this->rewriteHighlightedMenuItem();
    }

    /**
     * Flag "Performance Optimizer"-page as active when on Prefetching-page.
     */
    private function rewriteHighlightedMenuItem(): void
    {
        overrideParentMenuPage('servebolt-prefetching', 'servebolt-acd');
        overrideMenuTitle('admin_page_servebolt-prefetching', __('Prefetching', 'servebolt-wp'));
    }

    private function initAjax(): void
    {
        new PrefetchingFileGeneration;
    }

    private function initAssets(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    /**
     * Render the options page.
     */
    public function render(): void
    {
        $settings = $this->getSettingsItemsWithValues();
        $defaultMaxNumberOfLines = WpPrefetching::$defaultMaxNumberOfLines;
        $prefetchData = ManifestDataModel::get();
        $prefetchFiles = ManifestFilesModel::get();
        view('accelerated-domains.prefetching.prefetching', compact('settings', 'defaultMaxNumberOfLines', 'prefetchData', 'prefetchFiles'));
    }

    private function initSettings(): void
    {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Add listeners for when options are changing.
     */
    private function initSettingsActions(): void
    {
        listenForCheckboxOptionChange('prefetch_switch', function ($wasActive, $isActive, $optionName) {
            if ($isActive) {
                $this->refreshManifestFiles();
            } else {
                ManifestFileWriter::clear(null, true);
                ManifestFilesModel::clear();
            }
        });
        listenForOptionChange('prefetch_max_number_of_lines', function ($newValue, $oldValue, $optionName) {
            $this->refreshManifestFiles();
        });

        listenForCheckboxOptionChange('prefetch_full_url_switch', function ($wasActive, $isActive, $optionName) {
            $this->refreshManifestFiles();
        });

        listenForCheckboxOptionChange([
            'prefetch_file_style_switch',
            'prefetch_file_script_switch',
            'prefetch_file_menu_switch',
        ], function ($wasActive, $isActive, $optionName) {
            if ($isActive) {
                $this->refreshManifestFiles();
            } else {
                $this->removeManifestFile($optionName); // Remove manifest file on the fly
            }
        });
    }

    /**
     * Refresh manifest files, but only do it once during the execution (at the end).
     */
    private function refreshManifestFiles(): void
    {
        $callback = 'Servebolt\\Optimizer\\AcceleratedDomains\\Prefetching\\WpPrefetching::recordPrefetchItems';
        if (!has_action('shutdown', $callback)) {
            WpPrefetching::rescheduleManifestDataGeneration(); // We've changed settings, let's regenerate the data
            add_action('shutdown', $callback);
        }
    }

    /**
     * Remove manifest file after we've disabled it in the options.
     *
     * @param string $optionName
     */
    private function removeManifestFile(string $optionName): void
    {
        if (preg_match('/^prefetch_file_(.+)_switch$/', $optionName, $matches)) {
            ManifestFileWriter::clear($matches[1]);
            ManifestFileWriter::removeFromWrittenFiles($matches[1]);
        }
    }

    public function enqueueScripts(): void
    {
        // TODO: Fix multisite setup menu for prefetching
        // TODO: Fix script for prefetching in multisite
        if (!isScreen('admin_page_servebolt-prefetching')) {
            return;
        }
        wp_enqueue_script(
            'servebolt-optimizer-prefetching',
            SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/js/prefetching.js',
            [],
            getVersionForStaticAsset(SERVEBOLT_PLUGIN_DIR_PATH . 'assets/dist/js/prefetching.js'),
            true
        );
    }

    /**
     * Get all plugin settings in array.
     *
     * @return array
     */
    public function getSettingsItemsWithValues(): array
    {
        $items = $this->getSettingsItems();
        $itemsWithValues = [];
        foreach ($items as $item) {
            switch ($item) {
                default:
                    $itemsWithValues[$item] = getOption($item);
                    break;
            }
        }
        return $itemsWithValues;
    }

    public function registerSettings(): void
    {
        foreach ($this->getSettingsItems() as $key) {
            register_setting('sb-prefetch-feature-options-page', getOptionName($key));
        }
    }

    /**
     * Settings items for the prefetch feature.
     *
     * @return array
     */
    private function getSettingsItems(): array
    {
        return [
            'prefetch_switch',
            'prefetch_file_style_switch',
            'prefetch_file_script_switch',
            'prefetch_file_menu_switch',
            'prefetch_full_url_switch',
            'prefetch_max_number_of_lines',
        ];
    }
}
