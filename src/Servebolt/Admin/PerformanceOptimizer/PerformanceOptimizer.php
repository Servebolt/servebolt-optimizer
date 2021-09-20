<?php

namespace Servebolt\Optimizer\Admin\PerformanceOptimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\DatabaseOptimizer\DatabaseChecks;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\wpCronDisabled;
use function Servebolt\Optimizer\Helpers\view;

/**
 * Class PerformanceOptimizer
 *
 * This class display the optimization options and handles execution of optimizations.
 */
class PerformanceOptimizer
{
    use Singleton;

    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * PerformanceOptimizer constructor.
     */
    private function __construct()
    {
        DatabaseOptimizations::init();
        PerformanceOptimizerAdvanced::init();
        MenuOptimizerControl::init();
    }

    /**
     * Display performance checks view.
     */
    public function render()
    {
        $checksInstance = DatabaseChecks::getInstance();
        view('performance-optimizer.performance-optimizer', [
            'wpCronDisabled' => wpCronDisabled(),
        ]);
    }
}
