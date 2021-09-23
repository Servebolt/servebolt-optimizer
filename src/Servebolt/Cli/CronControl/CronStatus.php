<?php

namespace Servebolt\Optimizer\Cli\CronControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\CronControl\WpCronDisabler;
use Servebolt\Optimizer\CronControl\WpUnixCronControl;

/**
 * Class CronStatus
 * @package Servebolt\Optimizer\Cli\CronControl
 */
class CronStatus
{
    /**
     * CronStatus Constructor.
     */
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
        $allGood = true;
        if (WpCronDisabler::wpCronIsDisabled()) {
            WP_CLI::success(__('WP Cron is disabled (in wp-config.php).', 'servebolt-wp'));
        } else {
            $allGood = false;
            WP_CLI::error(__('WP Cron is not disabled (in wp-config.php).', 'servebolt-wp'), false);
        }
        if (WpUnixCronControl::isSetUp()) {
            WP_CLI::success(__('Unix cron setup ok!', 'servebolt-wp'));
        } else {
            $allGood = false;
            WP_CLI::error(__('Unix cron is not set up correctly.', 'servebolt-wp'), false);
        }
        if (!$allGood) {
            WP_CLI::line(__('Please run "wp servebolt cron setup" to fix this.', 'servebolt-wp'), false);
        }
    }
}
