<?php

namespace Servebolt\Optimizer\Admin\PerformanceChecks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Servebolt\Optimizer\Admin\PerformanceChecks\Ajax\OptimizeActions;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\view;

/**
 * Class Servebolt_Performance_Checks
 *
 * This class display the optimization options and handles execution of optimizations.
 */
class PerformanceChecks
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
        $this->initAjax();
        $this->initAssets();
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
        wp_enqueue_script( 'servebolt-optimizer-performance-checks-scripts', SERVEBOLT_PATH_URL . 'assets/dist/js/performance-checks.js', ['servebolt-optimizer-scripts'], filemtime(SERVEBOLT_PATH . 'assets/dist/js/performance-checks.js'), true );
    }

    /**
     * Add AJAX handling.
     */
    private function initAjax() {
        new OptimizeActions;
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
        $checksInstance = sb_checks();
        $tablesToIndex = $checksInstance->tablesToHaveIndexed();
        view('performance-checks.performance-checks', [
            'indexFixAvailable' => $this->tablesNeedIndex($tablesToIndex),
            'tables'            => $tablesToIndex,
            'myisamTables'      => $checksInstance->getMyisamTables(),
            'wpCronDisabled'    => $checksInstance->wpCronDisabled(),
        ]);
    }
}
