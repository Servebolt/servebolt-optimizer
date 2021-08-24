<?php

namespace Servebolt\Optimizer\Admin\PerformanceOptimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\DatabaseOptimizer\DatabaseChecks;
use Servebolt\Optimizer\Admin\PerformanceOptimizer\Ajax\OptimizeActions;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\getVersionForStaticAsset;
use function Servebolt\Optimizer\Helpers\isScreen;
use function Servebolt\Optimizer\Helpers\overrideMenuTitle;
use function Servebolt\Optimizer\Helpers\overrideParentMenuPage;
use function Servebolt\Optimizer\Helpers\view;

/**
 * Class PerformanceOptimizerAdvanced
 *
 * This class display the optimization options and handles execution of optimizations.
 */
class PerformanceOptimizerAdvanced
{
    use Singleton;

    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * PerformanceOptimizerAdvanced constructor.
     */
    private function __construct()
    {
        $this->initSettings();
        $this->rewriteHighlightedMenuItem();
    }

    private function initSettings(): void
    {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Flag "Performance Optimizer"-page as active when on advanced page.
     */
    private function rewriteHighlightedMenuItem(): void
    {
        overrideParentMenuPage('servebolt-performance-optimizer-advanced', 'servebolt-performance-optimizer');
        overrideMenuTitle('admin_page_servebolt-performance-optimizer-advanced', __('Advanced', 'servebolt-wp'));
    }

    /**
     * Display performance checks view.
     */
    public function render()
    {
        $settings = $this->getSettingsItemsWithValues();
        view('performance-optimizer.advanced.advanced', compact('settings'));
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
            register_setting('sb-performance-optimizer-advanced-options-page', getOptionName($key));
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
            'custom_text_domain_loader_switch',
        ];
    }
}
