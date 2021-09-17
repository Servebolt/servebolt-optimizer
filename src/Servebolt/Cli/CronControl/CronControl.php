<?php

namespace Servebolt\Optimizer\Cli\CronControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\CronControl\CronControl as CronControlNotCli;

/**
 * Class CronControl
 * @package Servebolt\Optimizer\Cli\CronControl
 */
class CronControl
{
    public function __construct()
    {
        WP_CLI::add_command('servebolt cron wp enable', [$this, 'enableWpCron']);
        WP_CLI::add_command('servebolt cron wp disable', [$this, 'disableWpCron']);
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
        CronControlNotCli::enableWpCron();
        WP_CLI::line(__('WP Cron is enabled.', 'servebolt-wp' ));
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
        CronControlNotCli::disableWpCron();
        WP_CLI::line(__('WP Cron is disabled.', 'servebolt-wp' ));
    }
}
