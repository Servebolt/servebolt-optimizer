<?php

namespace Servebolt\Optimizer\MenuOptimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\writeLog;

/**
 * Class TimingCheck
 * @package Servebolt\Optimizer\MenuOptimizer
 */
class TimingCheck
{

    /**
     * @var int Property used to store elapsed time during measurement.
     */
    private $timing = 0;

    /**
     * TimingCheck constructor.
     */
    public function __construct()
    {
        add_filter('pre_wp_nav_menu', [$this, 'startTiming'], (PHP_INT_MAX - 2), 2);
        add_filter('pre_wp_nav_menu', [$this, 'cacheHit'], PHP_INT_MAX, 2);
        add_filter('wp_nav_menu', [$this, 'cacheMiss'], 11, 2);
    }

    /**
     * Add the logging timer.
     *
     * @param string|null $output Nav menu output to short-circuit with. Default null.
     * @param stdClass    $args   An object containing wp_nav_menu() arguments.
     * @return string|null Output passthrough (default null).
     */
    public function startTiming($output, $args)
    {
        $this->timing = microtime(true);
        return $output;
    }

    /**
     * Log the menu generation time if we have a cache hit.
     *
     * @param string|null $output Nav menu output to short-circuit with. Default null.
     * @param stdClass    $args   An object containing wp_nav_menu() arguments.
     * @return string|null Output passthrough (default null).
     */
    public function cacheHit($output, $args)
    {
        if (!is_null($output)) {
            $this->updateElapsedTime();
            $this->output(sprintf('Cache hit: Menu output was fetched in %d ms', $this->currentElapsedTime()));
        }
        return $output;
    }

    /**
     * Log the menu generation time.
     *
     * @param string   $navMenu The HTML content for the navigation menu.
     * @param stdClass $args     An object containing wp_nav_menu() arguments.
     */
    public function cacheMiss($navMenu, $args)
    {
        $this->updateElapsedTime();
        $this->output(sprintf('Cache miss: Menu output was generated in %d ms', $this->currentElapsedTime()));
        return $navMenu;
    }

    /**
     * Set the current elapsed time.
     */
    private function updateElapsedTime()
    {
        $this->timing = microtime(true) - $this->timing;
    }

    /**
     * Get the current elapsed time.
     *
     * @return float
     */
    private function currentElapsedTime(): float
    {
        return round($this->timing * 1000);
    }

    /**
     * Print timing output.
     *
     * @param $string
     */
    private function output($string): void
    {
        if (apply_filters('sb_optimizer_menu_optimizer_timing_output_to_screen', false)) {
            echo $string;
        } else {
            writeLog($string);
        }
    }
}
