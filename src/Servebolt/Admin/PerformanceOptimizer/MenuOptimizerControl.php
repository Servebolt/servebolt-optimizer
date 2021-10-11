<?php

namespace Servebolt\Optimizer\Admin\PerformanceOptimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Admin\PerformanceOptimizer\Ajax\MenuOptimizerActions;
use Servebolt\Optimizer\MenuOptimizer\MenuOptimizer;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\getVersionForStaticAsset;
use function Servebolt\Optimizer\Helpers\isScreen;
use function Servebolt\Optimizer\Helpers\listenForCheckboxOptionChange;
use function Servebolt\Optimizer\Helpers\overrideMenuTitle;
use function Servebolt\Optimizer\Helpers\overrideParentMenuPage;
use function Servebolt\Optimizer\Helpers\view;

/**
 * Class MenuOptimizerControl
 * @package Servebolt\Optimizer\Admin\PerformanceOptimizer
 */
class MenuOptimizerControl
{
    use Singleton;

    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * MenuOptimizerControl constructor.
     */
    public function __construct()
    {
        $this->initAjax();
        $this->initAssets();
        $this->initSettings();
        $this->initSettingsActions();
        $this->rewriteHighlightedMenuItem();
    }

    /**
     * Add listeners for Menu Optimizer active state change.
     */
    private function initSettingsActions(): void
    {
        listenForCheckboxOptionChange('menu_cache_switch', function($wasActive, $isActive, $optionName) {
            MenuOptimizer::purgeCache();
        });
    }

    /**
     * Init assets.
     */
    private function initAssets(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    /**
     * Plugin scripts.
     */
    public function enqueueScripts(): void
    {
        if (!isScreen('admin_page_servebolt-menu-optimizer')) {
            return;
        }
        wp_enqueue_script('servebolt-optimizer-menu-optimizer-scripts', SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/js/menu-optimizer.js', ['servebolt-optimizer-scripts'], getVersionForStaticAsset(SERVEBOLT_PLUGIN_DIR_PATH . 'assets/dist/js/menu-optimizer.js'), true);
    }

    /**
     * Add AJAX handling.
     */
    private function initAjax(): void
    {
        new MenuOptimizerActions;
    }

    /**
     * Flag "Performance optimizer"-page as active when on "Menu Optimizer"-page.
     */
    private function rewriteHighlightedMenuItem(): void
    {
        overrideParentMenuPage('servebolt-menu-optimizer', 'servebolt-performance-optimizer');
        overrideMenuTitle('admin_page_servebolt-menu-optimizer', __('Menu Optimizer', 'servebolt-wp'));
    }

    /**
     * Render the options page.
     */
    public function render(): void
    {
        $settings = $this->getSettingsItemsWithValues();
        view('performance-optimizer.menu-optimizer.menu-optimizer', compact('settings'));
    }

    private function initSettings(): void
    {
        add_action('admin_init', [$this, 'registerSettings']);
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
            register_setting('sb-menu-optimizer-feature-options-page', getOptionName($key));
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
            'menu_cache_disabled_for_authenticated_switch',
            'menu_cache_auto_cache_purge_on_menu_update',
            'menu_cache_auto_cache_purge_on_front_page_settings_update',
            'menu_cache_run_timing',
            'menu_cache_simple_menu_signature',
        ];
    }
}
