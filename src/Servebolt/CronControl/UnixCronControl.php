<?php

namespace Servebolt\Optimizer\CronControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class UnixCronControl
 * @package Servebolt\Optimizer\CronControl
 */
abstract class UnixCronControl
{
    /**
     * Check whether the command is set up in UNIX cron.
     *
     * @return bool
     */
    public static function isSetUp(): bool
    {
        return UnixCronModel::exists(static::getCurrentCommandClass());
    }

    /**
     * Set up the command to be run from the UNIX cron.
     *
     * @return bool
     */
    public static function setUp(): bool
    {
        $otherCommandClass = static::getOtherCommandClass();
        if (UnixCronModel::exists($otherCommandClass)) {
            UnixCronModel::delete($otherCommandClass);
        }
        $commandClass = static::getCurrentCommandClass();
        if (UnixCronModel::exists($commandClass)) {
            return true;
        }
        return UnixCronModel::add($commandClass);
    }

    /**
     * Disable the command to be run from the UNIX cron.
     *
     * @param bool $bothMultiAndSingleSite
     */
    public static function tearDown(bool $bothMultiAndSingleSite = true): void
    {
        if ($bothMultiAndSingleSite) {
            $commandClasses = static::getCommandClasses();
        } else {
            $commandClasses = [static::getCurrentCommandClass()];
        }
        foreach($commandClasses as $commandClass) {
            if (!UnixCronModel::exists($commandClass)) {
                continue;
            }
            UnixCronModel::delete($commandClass);
        }
    }
}
