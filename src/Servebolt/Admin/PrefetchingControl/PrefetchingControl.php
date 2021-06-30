<?php

namespace Servebolt\Optimizer\Admin\PrefetchingControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\AcceleratedDomains\ImageResize\ImageSizeIndexModel;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\getVersionForStaticAsset;
use function Servebolt\Optimizer\Helpers\isScreen;
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
        $this->initSettings();
        $this->initAssets();
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
        view('prefetching.prefetching', compact('settings'));
    }

    private function initSettings(): void
    {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function enqueueScripts(): void
    {
        if (!isScreen('servebolt_page_servebolt-prefetching')) {
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
            'prefetch_max_number_of_lines',
        ];
    }
}
