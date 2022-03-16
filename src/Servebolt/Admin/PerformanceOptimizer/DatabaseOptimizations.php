<?php

namespace Servebolt\Optimizer\Admin\PerformanceOptimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\DatabaseOptimizer\DatabaseChecks;
use Servebolt\Optimizer\Admin\PerformanceOptimizer\Ajax\DatabaseOptimizationActions;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\getVersionForStaticAsset;
use function Servebolt\Optimizer\Helpers\isScreen;
use function Servebolt\Optimizer\Helpers\overrideMenuTitle;
use function Servebolt\Optimizer\Helpers\overrideParentMenuPage;
use function Servebolt\Optimizer\Helpers\view;

/**
 * Class DatabaseOptimizations
 *
 * This class display the optimization options and handles execution of optimizations.
 */
class DatabaseOptimizations
{
    use Singleton;

    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * DatabaseOptimizations constructor.
     */
    private function __construct()
    {
        $this->initAjax();
        $this->initAssets();
        $this->rewriteHighlightedMenuItem();
    }

    /**
     * Flag "Performance Optimizer"-page as active when on advanced page.
     */
    private function rewriteHighlightedMenuItem(): void
    {
        overrideParentMenuPage('servebolt-performance-optimizer-database', 'servebolt-performance-optimizer');
        overrideMenuTitle('admin_page_servebolt-performance-optimizer-database', __('Database', 'servebolt-wp'));
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
        if (!isScreen('page_servebolt-performance-optimizer-database')) {
            return;
        }
        wp_enqueue_script('servebolt-optimizer-performance-optimizer-scripts', SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/js/performance-optimizer.js', ['servebolt-optimizer-scripts'], getVersionForStaticAsset(SERVEBOLT_PLUGIN_DIR_PATH . 'assets/dist/js/performance-optimizer.js'), true);
    }

    /**
     * Add AJAX handling.
     */
    private function initAjax(): void
    {
        new DatabaseOptimizationActions;
    }

    /**
     * Check if any of the tables in the array needs indexing.
     *
     * @param $tables
     *
     * @return bool
     */
    private function tablesNeedIndex($tables): bool
    {
        foreach ($tables as $table) {
            if (!$table['has_index']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Display performance checks view.
     */
    public function render()
    {
        $checksInstance = DatabaseChecks::getInstance();
        $tablesToIndex = $checksInstance->tablesToHaveIndexed();
        view('performance-optimizer.database-optimizations.database-optimizations', [
            'indexFixAvailable' => $this->tablesNeedIndex($tablesToIndex),
            'tables'            => $tablesToIndex,
            'myisamTables'      => $checksInstance->getMyisamTables(),
        ]);
    }
}
