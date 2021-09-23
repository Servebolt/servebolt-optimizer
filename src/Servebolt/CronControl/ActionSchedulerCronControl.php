<?php

namespace Servebolt\Optimizer\CronControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Compatibility\ActionScheduler\DisableActionSchedulerDefaultRunner;
use Servebolt\Optimizer\CronControl\Scripts\ActionSchedulerMultisiteScript\ActionSchedulerMultisiteScript;

/**
 * Class ActionSchedulerCronControl
 * @package Servebolt\Optimizer\CronControl
 */
class ActionSchedulerCronControl
{
    /**
     * Check if Action Scheduler is set up to run from UNIX cron.
     *
     * @return bool
     */
    public static function isSetUp(): bool
    {
        if (DisableActionSchedulerDefaultRunner::isActive()) {
            return false;
        }
        if (is_multisite() && !(new ActionSchedulerMultisiteScript)->isInstalled()) {
            return false;
        }
        if (!ActionSchedulerUnixCronControl::isSetUp()) {
            return false;
        }
        return true;
    }

    /**
     * Disable WP Cron to run natively, and run WP Cron from UNIX cron.
     */
    public static function setUp()
    {
        DisableActionSchedulerDefaultRunner::toggleActive(true);
        if (is_multisite()) {
            (new ActionSchedulerMultisiteScript)->install();
        }
        ActionSchedulerUnixCronControl::setUp();
    }

    /**
     * Enable WP Cron to run natively, and do not run WP Cron from UNIX cron.
     */
    public static function tearDown()
    {
        DisableActionSchedulerDefaultRunner::toggleActive(false);
        if (is_multisite()) {
            (new ActionSchedulerMultisiteScript)->uninstall();
        }
        ActionSchedulerUnixCronControl::tearDown();
    }
}
