<?php

namespace Servebolt\Optimizer\CronControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CronControl\Scripts\WpCronMultisiteScript\WpCronMultisiteScript;

/**
 * Class WpCronControl
 * @package Servebolt\Optimizer\CronControl
 */
class WpCronControl
{
    /**
     * Check if WP Cron is default in WordPress and set up to run from UNIX cron.
     *
     * @return bool
     */
    public static function isSetUp(): bool
    {
        if (WpCronDisabler::wpCronIsEnabled()) {
            return false;
        }
        if (!self::cronIsSetUp()) {
            return false;
        }
        return true;
    }

    /**
     * Check if WP Cron is set up to run from UNIX cron.
     *
     * @return bool
     */
    public static function cronIsSetUp(): bool
    {
        if (is_multisite() && !(new WpCronMultisiteScript)->isInstalled()) {
            return false;
        }
        if (!WpUnixCronControl::isSetUp()) {
            return false;
        }
        return true;
    }

    /**
     * Disable WP Cron to run natively, and run WP Cron from UNIX cron.
     */
    public static function setUp()
    {
        WpCronDisabler::disableWpCron();
        if (is_multisite()) {
            (new WpCronMultisiteScript)->install();
        } else {
            (new WpCronMultisiteScript)->uninstall();
        }
        WpUnixCronControl::setUp();
    }

    /**
     * Enable WP Cron to run natively, and do not run WP Cron from UNIX cron.
     */
    public static function tearDown()
    {
        WpCronDisabler::enableWpCron();
        (new WpCronMultisiteScript)->uninstall();
        WpUnixCronControl::tearDown();
    }
}
