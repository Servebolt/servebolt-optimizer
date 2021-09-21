<?php

namespace Servebolt\Optimizer\Cli\CronControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\CronControl\WpCronControl;

/**
 * Class CronControl
 * @package Servebolt\Optimizer\Cli\CronControl
 */
class CronControl
{
    public function __construct()
    {
        new CronStatus;
        WP_CLI::add_command('servebolt cron fix', [$this, 'fixCronSetup']);
        WP_CLI::add_command('servebolt cron wp enable', [$this, 'enableWpCron']);
        WP_CLI::add_command('servebolt cron wp disable', [$this, 'disableWpCron']);
    }

    public function fixCronSetup()
    {
        // TODO: Disable WP Cron
        // TODO: If multisite, set up cron
        // TODO: Set up cron in UNIX crontab
    }

    /**
     * Enabled the WP Cron.
     *
     *
     * ## EXAMPLES
     *
     *     wp servebolt cron wp enable
     *
     */
    public function enableWpCron()
    {
        if (WpCronControl::wpCronIsEnabled()) {
            WP_CLI::success(__('WP Cron is already enabled.', 'servebolt-wp' ));
            return;
        }
        WpCronControl::enableWpCron();
        WP_CLI::success(__('WP Cron is enabled.', 'servebolt-wp' ));
    }

    /**
     * Disabled the WP Cron.
     *
     *
     * ## EXAMPLES
     *
     *     wp servebolt cron wp disable
     *
     */
    public function disableWpCron()
    {
        if (WpCronControl::wpCronIsDisabled()) {
            WP_CLI::success(__('WP Cron is already disabled.', 'servebolt-wp' ));
            return;
        }
        WpCronControl::disableWpCron();
        WP_CLI::success(__('WP Cron is disabled.', 'servebolt-wp' ));
    }
}
