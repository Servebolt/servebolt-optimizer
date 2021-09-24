<?php

namespace Servebolt\Optimizer\Admin\PerformanceOptimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CronControl\ActionSchedulerCronControl;
use Servebolt\Optimizer\CronControl\WpCronControl;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\getSiteOption;
use function Servebolt\Optimizer\Helpers\listenForCheckboxOptionUpdates;
use function Servebolt\Optimizer\Helpers\listenForCheckboxSiteOptionUpdates;
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
        $this->listenForActionSchedulerUnixCronActivation();
        $this->listenForWpUnixCronActivation();
        new SiteOptionsHandling;
    }

    /**
     * Activate/disable WP UNIX cron on option update.
     */
    private function listenForWpUnixCronActivation(): void
    {
        listenForCheckboxOptionUpdates('wp_unix_cron_active', [$this, 'handleWpUnixCronActivation']);
        listenForCheckboxSiteOptionUpdates('wp_unix_cron_active', [$this, 'handleWpUnixCronActivation']);
    }

    /**
     * Handle whenever WP UNIX cron gets enabled/disabled.
     *
     * @param $wasActive
     * @param $isActive
     * @param $optionName
     */
    public function handleWpUnixCronActivation($wasActive, $isActive, $optionName)
    {
        if ($isActive) {
            WpCronControl::setUp();
        } else {
            WpCronControl::tearDown();
        }
    }

    /**
     * Activate/disable Action Scheduler UNIX cron on option update.
     */
    private function listenForActionSchedulerUnixCronActivation(): void
    {
        listenForCheckboxOptionUpdates('action_scheduler_unix_cron_active', [$this, 'handleActionSchedulerUnixCronActivation']);
        listenForCheckboxSiteOptionUpdates('action_scheduler_unix_cron_active', [$this, 'handleActionSchedulerUnixCronActivation']);
    }

    /**
     * Handle whenever Action Scheduler UNIX cron gets enabled/disabled.
     *
     * @param $wasActive
     * @param $isActive
     * @param $optionName
     */
    public function handleActionSchedulerUnixCronActivation($wasActive, $isActive, $optionName)
    {
        if ($isActive) {
            ActionSchedulerCronControl::setUp();
        } else {
            ActionSchedulerCronControl::tearDown();
        }
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
                    $itemsWithValues[$item] = is_network_admin() ? getSiteOption($item) : getOption($item);
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
            'action_scheduler_unix_cron_active',
            'wp_unix_cron_active',
        ];
    }
}
