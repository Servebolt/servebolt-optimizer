<?php

namespace Servebolt\Optimizer\Cli\CronControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\CronControl\WpCronControl;
use Servebolt\Optimizer\CronControl\CronControl;

/**
 * Class CronStatus
 * @package Servebolt\Optimizer\Cli\CronControl
 */
class CronStatus
{
    public function __construct()
    {
        WP_CLI::add_command('servebolt cron status', [$this, 'status']);
    }

    /**
     * Display the status of the cron setup - whether WP Cron is disabled and whether the UNIX cron is set up to run WP Cron.
     *
     *
     * ## EXAMPLES
     *
     *     wp servebolt cron status
     *
     */
    public function status()
    {
        if (WpCronControl::wpCronIsDisabled()) {
            WP_CLI::success(__('WP Cron is disabled.', 'servebolt-wp'));
        } else {
            WP_CLI::error(__('WP Cron is not disabled.', 'servebolt-wp'), false);
        }
        if (CronControl::unixCronIsSetup()) {
            WP_CLI::success(__('Unix cron setup ok!', 'servebolt-wp'));
        } else {
            WP_CLI::error(__('Unix cron is not set up correctly.', 'servebolt-wp'), false);
        }
    }
}
