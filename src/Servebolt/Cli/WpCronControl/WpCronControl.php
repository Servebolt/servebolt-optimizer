<?php

namespace Servebolt\Optimizer\Cli\WpCronControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\CronControl\WpCronControl as WpCronControlNotCli;

/**
 * Class WpCronControl
 * @package Servebolt\Optimizer\Cli\WpCronControl
 */
class WpCronControl
{
    /**
     * WpCronControl constructor.
     */
    public function __construct()
    {
        WP_CLI::add_command('servebolt cron status', [$this, 'status']);
        WP_CLI::add_command('servebolt cron enable', [$this, 'enable']);
        WP_CLI::add_command('servebolt cron disable', [$this, 'disable']);
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
        if (WpCronControlNotCli::isSetUp()) {
            WP_CLI::success(__('WP Cron setup ok!', 'servebolt-wp'));
        } else {
            WP_CLI::error(__('WP Cron is not set up correctly.', 'servebolt-wp'), false);
            WP_CLI::line(__('Please run "wp servebolt cron setup" to fix this.', 'servebolt-wp'), false);
        }
    }

    /**
     * Set up WP Cron to be executed via the UNIX cron.
     *
     * ## EXAMPLES
     *
     *     wp servebolt cron wp enable
     *
     */
    public function enable()
    {
        $isSetUp = WpCronControlNotCli::isSetUp();
        WpCronControlNotCli::setUp();
        if ($isSetUp) {
            WP_CLI::success(__('WP Cron is already set up to run from UNIX cron.', 'servebolt-wp' ));
        } else {
            WP_CLI::success(__('WP Cron is now set up to run from UNIX cron.', 'servebolt-wp' ));
        }
    }

    /**
     * Disable WP Cron to be executed via the UNIX cron (revert to native WP Cron setup).
     *
     * ## EXAMPLES
     *
     *     wp servebolt cron wp disable
     *
     */
    public function disable()
    {
        $isSetUp = WpCronControlNotCli::isSetUp();
        WpCronControlNotCli::tearDown();
        if (!$isSetUp) {
            WP_CLI::success(__('WP Cron is already not set up to run from UNIX cron.', 'servebolt-wp' ));
        } else {
            WP_CLI::success(__('WP Cron is no longer set up to run from UNIX cron.', 'servebolt-wp' ));
        }
    }
}
