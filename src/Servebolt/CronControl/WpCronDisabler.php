<?php

namespace Servebolt\Optimizer\CronControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Throwable;
use Servebolt\Optimizer\Utils\WPConfigTransformer;
use function Servebolt\Optimizer\Helpers\getWpConfigPath;

/**
 * Class WpCronDisabler
 * @package Servebolt\Optimizer\CronControl
 */
class WpCronDisabler
{

    /**
     * @var string The constant used to control the native WP Cron behaviour.
     */
    private static $constantName = 'DISABLE_WP_CRON';

    /**
     * Enable WP Cron.
     */
    public static function enableWpCron(): void
    {
        self::toggleWpCron(true);
    }

    /**
     * Disable WP Cron.
     */
    public static function disableWpCron(): void
    {
        self::toggleWpCron(false);
    }

    /**
     * Check whether WP Cron is enabled.
     *
     * @return bool
     */
    public static function wpCronIsEnabled(): bool
    {
        return !self::wpCronIsDisabled();
    }

    /**
     * Check whether WP Cron is disabled.
     *
     * @return bool
     */
    public static function wpCronIsDisabled(): bool
    {
        try {
            $ct = self::getWPConfigTransformer();
            if ($ct && $ct->exists('constant', self::$constantName)) {
                $value = $ct->get_value('constant', self::$constantName);
                if ($value !== 'false' && $value !== '0') {
                    return true;
                }
            }
        } catch (Throwable $e) {}
        return false; // Default value
    }

    /**
     * Get instance of "WPConfigTransformer".
     *
     * @return object|null
     * @throws \Exception
     */
    private static function getWPConfigTransformer(): ?object
    {
        $configFilePath = getWpConfigPath();
        if (!$configFilePath) {
            return null;
        }
        return new WPConfigTransformer($configFilePath);
    }

    /**
     * Toggle Wp Cron on/off.
     *
     * @param bool $wpCronEnabled Whether the WP Cron should be disabled or not.
     * @return void
     */
    private static function toggleWpCron(bool $wpCronEnabled): void
    {
        try {
            $ct = self::getWPConfigTransformer();
            if (!$ct) {
                return;
            }
            $options = ['raw' => true];
            $configString = $wpCronEnabled ? 'false' : 'true';
            if ($ct->exists('constant', self::$constantName)) {
                $ct->update('constant', self::$constantName, $configString, $options);
            } else {
                $ct->add('constant', self::$constantName, $configString, $options);
            }
        } catch (Throwable $e) {}
    }
}
