<?php

namespace Servebolt\Optimizer\Cli\ActionScheduler;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\Compatibility\ActionScheduler\DisableActionSchedulerDefaultRunner;
use function Servebolt\Optimizer\Helpers\actionSchedulerIsActive;

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
        WP_CLI::add_command('servebolt action-scheduler status', [$this, 'status']);
        WP_CLI::add_command('servebolt action-scheduler enable', [$this, 'enable']);
        WP_CLI::add_command('servebolt action-scheduler disable', [$this, 'disable']);
    }

    private function unixCronSetUp(): bool
    {
        return false; // TODO: Add proper condition check
    }

    /**
     * Check if the Action Scheduler is installed and if it is set up to run from the UNIX cron.
     *
     * ## EXAMPLES
     *
     *     wp action-scheduler status
     */
    public function status()
    {
        if (actionSchedulerIsActive()) {
            WP_CLI::success(__('Action Scheduler seems to be installed.', 'servebol-wp'));
        } else {
            WP_CLI::warning(__('Action Scheduler is not installed.', 'servebol-wp'));
        }
        if ($this->unixCronSetUp()) {
            WP_CLI::success(__('Action Scheduler is set up to be ran from the UNIX cron.', 'servebol-wp'));
        } else {
            WP_CLI::error(__('Action Scheduler is not set up to be ran from the UNIX cron.', 'servebol-wp'), false);
            WP_CLI::line(__('Please run "wp servebolt action-scheduler enable" to fix this.', 'servebol-wp'), false);
        }
    }

    /**
     * Enable the Action Scheduler to be run from the UNIX cron.
     *
     * ## EXAMPLES
     *
     *     wp action-scheduler enable
     */
    public function enable()
    {
        if (!actionSchedulerIsActive()) {
            WP_CLI::confirm(__('Action Scheduler does not seem to be installed. Do you still want to continue?', 'servebolt-wp'));
        }
        DisableActionSchedulerDefaultRunner::toggleActive(true);
        // TODO: Enabled Action Scheduler to be ran from UNIX cron
        WP_CLI::success(__('The Action Scheduler is now set up to run from the UNIX cron.', 'servebolt-wp'));
    }

    /**
     * Disable the Action Scheduler to be run from the UNIX cron.
     *
     * ## EXAMPLES
     *
     *     wp action-scheduler disable
     */
    public function disable()
    {
        DisableActionSchedulerDefaultRunner::toggleActive(false);
        // TODO: Disable Action Scheduler to be ran from UNIX cron
        WP_CLI::success(__('The Action Scheduler is no longer set up to run from the UNIX cron.', 'servebolt-wp'));
    }
}
