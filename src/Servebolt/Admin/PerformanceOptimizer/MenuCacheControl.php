<?php

namespace Servebolt\Optimizer\Admin\PerformanceOptimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\getVersionForStaticAsset;
use function Servebolt\Optimizer\Helpers\isScreen;
use function Servebolt\Optimizer\Helpers\overrideMenuTitle;
use function Servebolt\Optimizer\Helpers\overrideParentMenuPage;
use function Servebolt\Optimizer\Helpers\view;

/**
 * Class MenuCacheControl
 * @package Servebolt\Optimizer\Admin\PerformanceOptimizer
 */
class MenuCacheControl
{
    use Singleton;

    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * MenuCacheControl constructor.
     */
    public function __construct()
    {
        $this->initSettings();
        $this->initAssets();
        $this->rewriteHighlightedMenuItem();
    }

    /**
     * Flag "Performance optimizer"-page as active when on Menu cache-page.
     */
    private function rewriteHighlightedMenuItem(): void
    {
        overrideParentMenuPage('servebolt-menu-cache', 'servebolt-performance-optimizer');
        overrideMenuTitle('admin_page_servebolt-menu-cache', __('Menu Cache', 'servebolt-wp'));
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
        view('performance-optimizer.menu-cache.menu-cache', compact('settings'));
    }

    private function initSettings(): void
    {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function enqueueScripts(): void
    {
        if (!isScreen('admin_page_servebolt-menu-cache')) {
            return;
        }
        wp_enqueue_script(
            'servebolt-optimizer-menu-cache',
            SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/js/menu-cache.js',
            [],
            getVersionForStaticAsset(SERVEBOLT_PLUGIN_DIR_PATH . 'assets/dist/js/menu-cache.js'),
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
            register_setting('sb-menu-cache-feature-options-page', getOptionName($key));
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
            'menu_cache_switch',
            'menu_cache_only_authenticated_switch',
        ];
    }
}
