<?php

namespace Servebolt\Optimizer\CronControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Exception;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\Utils\WPConfigTransformer;
use function Servebolt\Optimizer\Helpers\getWpConfigPath;

class WpCronControl
{
    use Singleton;

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
            if ($ct && $ct->exists('constant', 'DISABLE_WP_CRON')) {
                $value = $ct->get_value('constant', 'DISABLE_WP_CRON');
                if ($value !== 'false' && $value !== '0') {
                    return true;
                }
            }
        } catch (Exception $e) {}
        return false; // Default value
    }

    /**
     * Get instance of "WPConfigTransformer".
     *
     * @return object|null
     * @throws Exception
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
     * @param bool $cronEnabled
     */
    private static function toggleWpCron($cronEnabled)
    {
        try {
            $ct = self::getWPConfigTransformer();
            if (!$ct) {
                return;
            }
            if ($cronEnabled) {
                if ($ct->exists('constant', 'DISABLE_WP_CRON')) {
                    $ct->remove('constant', 'DISABLE_WP_CRON');
                }
            } else {
                if ($ct->exists('constant', 'DISABLE_WP_CRON')) {
                    $ct->update('constant', 'DISABLE_WP_CRON', 'true');
                } else {
                    $ct->add('constant', 'DISABLE_WP_CRON', 'true');
                }
            }
        } catch (Exception $e) {}
    }

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
}
