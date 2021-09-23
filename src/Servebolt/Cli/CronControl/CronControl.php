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
        WP_CLI::add_command('servebolt cron setup', [$this, 'enableWpCron']);
        WP_CLI::add_command('servebolt cron teardown', [$this, 'disableWpCron']);
    }

    /**
     * Set up WP Cron to be executed via the UNIX cron.
     *
     * ## EXAMPLES
     *
     *     wp servebolt cron wp enable
     *
     */
    public function enableWpCron()
    {
        if (WpCronControl::isSetUp()) {
            WP_CLI::success(__('WP Cron is already set up to run from UNIX cron.', 'servebolt-wp' ));
            return;
        }
        WpCronControl::setUp();
        WP_CLI::success(__('WP Cron is now set up to run from UNIX cron.', 'servebolt-wp' ));
    }

    /**
     * Disable WP Cron to be executed via the UNIX cron (revert to native WP Cron setup).
     *
     * ## EXAMPLES
     *
     *     wp servebolt cron wp disable
     *
     */
    public function disableWpCron()
    {
        if (!WpCronControl::isSetUp()) {
            WP_CLI::success(__('WP Cron is already not set up to run from UNIX cron.', 'servebolt-wp' ));
            return;
        }
        WpCronControl::tearDown();
        WP_CLI::success(__('WP Cron is no longer set up to run from UNIX cron.', 'servebolt-wp' ));
    }
}
