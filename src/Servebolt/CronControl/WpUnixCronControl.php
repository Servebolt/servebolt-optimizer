<?php

namespace Servebolt\Optimizer\CronControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CronControl\Commands\WpCliEventRun;
use Servebolt\Optimizer\CronControl\Commands\WpCliEventRunMultisite;

/**
 * Class WpUnixCronControl
 * @package Servebolt\Optimizer\CronControl
 */
class WpUnixCronControl extends UnixCronControl
{
    /**
     * Get the all command instances for WP Cron.
     *
     * @return array
     */
    protected static function getCommandClasses(): array
    {
        return [
            new WpCliEventRunMultisite,
            new WpCliEventRun
        ];
    }

    /**
     * Get the command instance based on single or multisite.
     *
     * @return object
     */
    protected static function getCurrentCommandClass(): object
    {
        if (is_multisite()) {
            return new WpCliEventRunMultisite();
        } else {
            return new WpCliEventRun;
        }
    }
}
