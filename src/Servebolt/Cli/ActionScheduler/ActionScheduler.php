<?php

namespace Servebolt\Optimizer\Cli\ActionScheduler;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\CronControl\ActionSchedulerCronControl;
use function Servebolt\Optimizer\Helpers\actionSchedulerIsActive;
use function Servebolt\Optimizer\Helpers\envFileReadFailureCliHandling;

/**
 * Class ActionScheduler
 * @package Servebolt\Optimizer\ActionScheduler
 */
class ActionScheduler
{
    /**
     * ActionScheduler constructor.
     */
    public function __construct()
    {
        WP_CLI::add_command('servebolt action-scheduler cron status', [$this, 'status']);
        WP_CLI::add_command('servebolt action-scheduler cron enable', [$this, 'enable']);
        WP_CLI::add_command('servebolt action-scheduler cron disable', [$this, 'disable']);
    }

    /**
     * Check if the Action Scheduler is installed and if it is set up to run from the UNIX cron.
     *
     * ## EXAMPLES
     *
     *     wp action-scheduler cron status
     */
    public function status()
    {
        envFileReadFailureCliHandling();
        if (actionSchedulerIsActive()) {
            WP_CLI::success(__('Action Scheduler seems to be installed.', 'servebolt-wp'));
        } else {
            WP_CLI::warning(__('Action Scheduler is not installed.', 'servebolt-wp'));
        }
        if (ActionSchedulerCronControl::isSetUp()) {
            WP_CLI::success(__('Action Scheduler is set up to be ran from the UNIX cron.', 'servebolt-wp'));
        } else {
            WP_CLI::error(__('Action Scheduler is not set up to be ran from the UNIX cron.', 'servebolt-wp'), false);
            WP_CLI::line(__('Please run "wp servebolt action-scheduler enable" to fix this.', 'servebolt-wp'), false);
        }
    }

    /**
     * Enable the Action Scheduler to be run from the UNIX cron.
     *
     * ## EXAMPLES
     *
     *     wp action-scheduler cron enable
     */
    public function enable()
    {
        envFileReadFailureCliHandling();
        if (!actionSchedulerIsActive()) {
            WP_CLI::confirm(__('Action Scheduler does not seem to be installed. Do you still want to continue?', 'servebolt-wp'));
        }
        $wasSatUp = ActionSchedulerCronControl::isSetUp();
        ActionSchedulerCronControl::setUp();
        if ($wasSatUp) {
            WP_CLI::success(__('The Action Scheduler is already set up to run from the UNIX cron.', 'servebolt-wp'));
            return;
        }
        WP_CLI::success(__('The Action Scheduler is now set up to run from the UNIX cron.', 'servebolt-wp'));
    }

    /**
     * Disable the Action Scheduler from being run from the UNIX cron.
     *
     * ## EXAMPLES
     *
     *     wp action-scheduler cron disable
     */
    public function disable()
    {
        envFileReadFailureCliHandling();
        if (!actionSchedulerIsActive()) {
            WP_CLI::confirm(__('Action Scheduler does not seem to be installed. Do you still want to continue?', 'servebolt-wp'));
        }
        $wasSatUp = ActionSchedulerCronControl::isSetUp();
        ActionSchedulerCronControl::tearDown();
        if (!$wasSatUp) {
            WP_CLI::success(__('The Action Scheduler is already not set up to run from the UNIX cron.', 'servebolt-wp'));
            return;
        }
        WP_CLI::success(__('The Action Scheduler is no longer set up to run from the UNIX cron.', 'servebolt-wp'));
    }
}
