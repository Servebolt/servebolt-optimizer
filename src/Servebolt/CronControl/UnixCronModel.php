<?php

namespace Servebolt\Optimizer\CronControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Throwable;
use Servebolt\Optimizer\Api\Servebolt\Servebolt as ServeboltApi;
use Servebolt\Optimizer\CronControl\Commands\WpCliEventRun;
use Servebolt\Optimizer\CronControl\Commands\WpCliEventRunMultisite;
use Servebolt\Optimizer\CronControl\Commands\ActionSchedulerRun;
use Servebolt\Optimizer\CronControl\Commands\ActionSchedulerRunMultisite;
use function Servebolt\Optimizer\Helpers\getSiteId;

/**
 * Class UnixCronModel
 * @package Servebolt\Optimizer\CronControl
 */
class UnixCronModel
{

    /**
     * @var string[] All our registered cron job commands.
     */
    private static $ourCronJobs = [
        WpCliEventRun::class,
        WpCliEventRunMultisite::class,
        ActionSchedulerRun::class,
        ActionSchedulerRunMultisite::class,
    ];

    /**
     * Check if a command exists in the UNIX cron tab.
     *
     * @param object|string $commandInstance
     * @param bool $onlyActive
     * @return bool
     */
    public static function exists($commandInstance, bool $onlyActive = true): bool
    {
        $commandInstance = self::ensureCommandInstance($commandInstance);
        if (self::resolve($commandInstance, $onlyActive)) {
            return true;
        }
        return false;
    }

    /**
     * Resolve cron job(s) by command instance.
     *
     * @param object|string $commandInstance
     * @param bool $onlyActive
     * @return array|null
     */
    public static function resolve($commandInstance, bool $onlyActive = true): ?array
    {
        $ourCronJobs = self::getOurCronJobs($onlyActive);
        if ($ourCronJobs) {
            $matches = [];
            foreach ($ourCronJobs as $ourCronJob) {
                if (!isset($ourCronJob->attributes->command)) {
                    continue;
                }
                if (get_class($commandInstance) === $ourCronJob->commandInstance) {
                    $matches[] = $ourCronJob;
                }
            }
            if (!empty($matches)) {
                return $matches;
            }
        }
        return null;
    }

    /**
     * Delete cron jobs by command.
     *
     * @param $commandInstance
     * @return bool
     */
    public static function delete($commandInstance): bool
    {
        if ($cronJobs = self::resolve($commandInstance)) {
            $api = ServeboltApi::getInstance();
            foreach ($cronJobs as $cronJob) {
                try {
                    $api->cron->delete($cronJob->id);
                } catch (Throwable $e) {
                    return false; // We could not delete current cron job
                }
            }
            return true; // We deleted cron job(s) without any issues
        }
        return true; // Did not find command, it does not exist, success
    }

    /**
     * Add the command to the UNIX crontab.
     *
     * @param $commandInstance
     * @param bool $preventDuplicate
     * @return bool
     */
    public static function add($commandInstance, bool $preventDuplicate = true): bool
    {
        if ($preventDuplicate && self::resolve($commandInstance)) {
            return true; // We already have that command installed
        }
        try {
            $api = ServeboltApi::getInstance();
            $environmentId = getSiteId();
            $response = $api->cron->create(
                self::buildCronJobDataFromCommand($commandInstance),
                $environmentId
            );
            return $response->wasSuccessful();
        } catch (Throwable $e) {
            return false; // We could not delete current cron job
        }
    }

    /**
     * Get the default active state.
     *
     * @param $commandInstance
     * @return bool
     */
    private static function defaultActiveState($commandInstance): bool
    {
        $legacy = apply_filters('sb_optimizer_unix_cron_model_default_enabled', null, $commandInstance);
        if (is_bool($legacy)) {
            return $legacy;
        }
        return (bool) apply_filters('sb_optimizer_unix_cron_model_default_active', true, $commandInstance);
    }

    /**
     * Build the line that will be placed in the UNIX cron.
     *
     * @param $commandInstance
     * @return array
     */
    public static function buildCronJobDataFromCommand($commandInstance): array
    {
        return [
            'attributes' => [
                'active' => self::defaultActiveState($commandInstance),
                'command' => $commandInstance->generateCommand(),
                'comment' => $commandInstance->generateComment(),
                'schedule' => $commandInstance->getInterval(),
                'notifications' => self::defaultNotification($commandInstance),
            ]
        ];
    }

    /**
     * Get the notification level for this cron.
     *
     * @param $commandInstance
     * @return string
     */
    private static function defaultNotification($commandInstance): string
    {
        /**
         * Determine the level of notifications for this.
         *
         * @param string $notifications A string controlling the notification levels for this cron.
         * @param object $commandInstance An instance of the command being set up as a cron.
         */
        return (string) apply_filters(
            'sb_optimizer_unix_cron_model_default_notification',
            'all', // Should we use 'none' instead?
            $commandInstance
        );
    }

    /**
     * Ensure command instance is actually an instance of command.
     *
     * @param object|string $commandInstance
     * @return object|string
     */
    public static function ensureCommandInstance($commandInstance)
    {
        if (is_string($commandInstance) && class_exists($commandInstance)) {
            return new $commandInstance;
        } else if (is_object($commandInstance)) {
            return $commandInstance;
        }
        return false;
    }

    /**
     * Get cron jobs in the environment via the Servebolt API.
     *
     * @param bool $onlyActive
     * @return array|null
     */
    public static function getCronJobs(bool $onlyActive = true): ?array
    {
        $api = ServeboltApi::getInstance();
        try {
            $response = $api->cron->list();
        } catch (\Throwable $e) {
            echo '<pre>';
            var_dump(get_class($e));die;
            return null;
        }
        if (!$response->wasSuccessful() || !$response->hasResult()) {
            return null;
        }
        $cronJobs = $response->getResultItems();
        if ($cronJobs && is_array($cronJobs)) {
            if ($onlyActive) {
                $filteredCronJobs = array_filter($cronJobs, function($cronjob) {
                    return $cronjob->attributes->active;
                });
                if ($filteredCronJobs) {
                    return $filteredCronJobs;
                }
            } else {
                return $cronJobs;
            }
        }
        return null;
    }

    /**
     * Only get the cron jobs that we have registered (filter them out from all cron jobs).
     *
     * @param bool $onlyActive
     * @return array|null
     */
    public static function getOurCronJobs(bool $onlyActive = true): ?array
    {
        if ($allCronJobs = self::getCronJobs($onlyActive)) {
            $ourCronJobs = [];
            foreach ($allCronJobs as $cronJob) {
                foreach (self::$ourCronJobs as $ourCronJob) {
                    if (!isset($cronJob->attributes->command)) {
                        continue;
                    }
                    if ($ourCronJob::is($cronJob->attributes->command)) {
                        $cronJob->commandInstance = $ourCronJob;
                        $ourCronJobs[] = $cronJob;
                        break;
                    }
                }
            }
            if (!empty($ourCronJobs)) {
                return $ourCronJobs;
            }
        }
        return null;
    }
}
