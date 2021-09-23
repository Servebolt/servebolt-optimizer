<?php

namespace Servebolt\Optimizer\CronControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CronControl\Commands\WpCliEventRun;
use Servebolt\Optimizer\CronControl\Commands\WpCliEventRunMultisite;

/**
 * Class CronControl
 * @package Servebolt\Optimizer\CronControl
 */
class WpUnixCronControl
{
    /**
     * Check whether the command is set up in UNIX cron.
     * @return bool
     */
    public static function isSetUp(): bool
    {
        return UnixCronModel::exists(self::getCommandClass());
    }

    /**
     * Set up WP Cron to be run from the UNIX cron.
     *
     * @return bool
     */
    public static function setUp(): bool
    {
        $commandClass = self::getCommandClass();
        if (UnixCronModel::exists($commandClass)) {
            return true;
        }
        return UnixCronModel::add($commandClass);
    }

    /**
     * Disable WP Cron to be run from the UNIX cron.
     *
     * @return bool
     */
    public static function tearDown(): bool
    {
        $commandClass = self::getCommandClass();
        if (!UnixCronModel::exists($commandClass)) {
            return true;
        }
        return UnixCronModel::delete($commandClass);
    }

    /**
     * Get the command instance based on multisite or not.
     *
     * @return object
     */
    private static function getCommandClass(): object
    {
        if (is_multisite()) {
            return new WpCliEventRunMultisite();
        } else {
            return new WpCliEventRun;
        }
    }
}
