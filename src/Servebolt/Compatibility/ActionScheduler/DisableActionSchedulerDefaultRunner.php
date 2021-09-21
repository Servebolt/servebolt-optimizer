<?php

namespace Servebolt\Optimizer\Compatibility\ActionScheduler;

use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\smartGetOption;
use function Servebolt\Optimizer\Helpers\smartUpdateOption;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

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
     * @param int|null $blogId
     * @return bool
     */
    public static function isActive(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'action_scheduler_disable'));
    }

    /**
     * @param bool $state
     * @param int|null $blogId
     */
    public static function toggleActive(bool $state, ?int $blogId = null): void
    {
        smartUpdateOption($blogId, 'action_scheduler_disable', $state);
    }
}
