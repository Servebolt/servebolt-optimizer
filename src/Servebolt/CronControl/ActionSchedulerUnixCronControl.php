<?php

namespace Servebolt\Optimizer\CronControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CronControl\Commands\ActionSchedulerRun;
use Servebolt\Optimizer\CronControl\Commands\ActionSchedulerRunMultisite;

/**
 * Class ActionSchedulerUnixCronControl
 * @package Servebolt\Optimizer\CronControl
 */
class ActionSchedulerUnixCronControl extends UnixCronControl
{
    /**
     * Get the all command instances for Action Scheduler cron.
     *
     * @return array
     */
    protected static function getCommandClasses(): array
    {
        return [
            new ActionSchedulerRunMultisite,
            new ActionSchedulerRun
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
            return new ActionSchedulerRunMultisite;
        } else {
            return new ActionSchedulerRun;
        }
    }
}
