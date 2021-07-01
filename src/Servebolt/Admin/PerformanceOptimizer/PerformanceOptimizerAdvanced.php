<?php

namespace Servebolt\Optimizer\Admin\PerformanceOptimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\DatabaseOptimizer\DatabaseChecks;
use Servebolt\Optimizer\Admin\PerformanceOptimizer\Ajax\OptimizeActions;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\getVersionForStaticAsset;
use function Servebolt\Optimizer\Helpers\isScreen;
use function Servebolt\Optimizer\Helpers\overrideMenuTitle;
use function Servebolt\Optimizer\Helpers\overrideParentMenuPage;
use function Servebolt\Optimizer\Helpers\view;

/**
 * Class Servebolt_Performance_Checks
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
     * Servebolt_Performance_Checks constructor.
     */
    private function __construct()
    {
        //$this->initAjax();
        //$this->initAssets();
        $this->rewriteHighlightedMenuItem();
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
        if (!isScreen('servebolt_page_servebolt-performance-optimizer')) {
            return;
        }
        wp_enqueue_script( 'servebolt-optimizer-performance-optimizer-scripts', SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/js/performance-optimizer.js', ['servebolt-optimizer-scripts'], getVersionForStaticAsset(SERVEBOLT_PLUGIN_DIR_PATH . 'assets/dist/js/performance-optimizer.js'), true );
    }

    /**
     * Add AJAX handling.
     */
    private function initAjax(): void
    {
        //new OptimizeActions;
    }

    /**
     * Display performance checks view.
     */
    public function render()
    {
        view('performance-optimizer.performance-optimizer-advanced');
    }
}
