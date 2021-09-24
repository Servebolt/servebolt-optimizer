<?php

namespace Servebolt\Optimizer\Compatibility\ActionScheduler;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getSiteOption;
use function Servebolt\Optimizer\Helpers\updateOption;
use function Servebolt\Optimizer\Helpers\updateSiteOption;

/**
 * Class DisableActionSchedulerDefaultRunner
 * @package Servebolt\Optimizer\Compatibility\WooCommerce
 */
class DisableActionSchedulerDefaultRunner
{
    /**
     * DisableActionSchedulerDefaultRunner constructor.
     */
    public function __construct()
    {
        if (self::isActive()) {
            add_action('init', [$this, 'disableDefaultRunner']);
        }
    }

    /**
     * Disable Action Scheduler default runner.
     */
    public function disableDefaultRunner(): void
    {
        remove_action('action_scheduler_run_queue', [\ActionScheduler::runner(), 'run']);
    }

    /**
     * Check whether we should disable the default runner for the Action Scheduler.
     *
     * @return bool
     */
    public static function isActive(): bool
    {
        if (is_multisite()) {
            return checkboxIsChecked(getSiteOption('action_scheduler_disable'));
        } else {
            return checkboxIsChecked(getOption('action_scheduler_disable'));
        }
    }

    /**
     * @param bool $state
     */
    public static function toggleActive(bool $state): void
    {
        if (is_multisite()) {
            updateSiteOption('action_scheduler_disable', $state);
        } else {
            updateOption('action_scheduler_disable', $state);
        }
    }
}
