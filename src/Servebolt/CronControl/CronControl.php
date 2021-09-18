<?php

namespace Servebolt\Optimizer\CronControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Exception;
use Servebolt\Optimizer\Api\Servebolt\Servebolt as ServeboltApi;
use Servebolt\Optimizer\CronControl\Cronjobs\WpCliEventRun;
use Servebolt\Optimizer\CronControl\Cronjobs\WpCliEventRunMultisite;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\arrayGet;

class CronControl
{
    use Singleton;

    /**
     * Parse cronjob comment.
     *
     * @param string $comment
     * @return array|null
     */
    public static function parseComment(string $comment): ?array
    {
        preg_match('/\((.*)\)/', $comment, $matches);
        if (!isset($matches[1])) {
            return null;
        }
        $items = array_map(function($item) {
            return explode('=', $item);
        }, array_map('trim', explode(',', $matches[1])));
        $array = [];
        foreach ($items as $item) {
            $array[$item[0]] = $item[1];
        }
        if (empty($array)) {
            return null;
        }
        return $array;
    }

    /**
     * Check whether UNIX cron is set up correctly using the Servebolt API.
     *
     * @return bool
     */
    public static function unixCronIsSetup(): bool
    {
        $api = ServeboltApi::getInstance();
        try {
            $response = $api->cron->list();
        } catch (Exception $e) {
            return false;
        }
        if (!$response->wasSuccessful() || !$response->hasResult()) {
            return false;
        }

        if (is_multisite()) {
            $commandNameToCheck = WpCliEventRunMultisite::$commandName;
            $commandToCheck = WpCliEventRunMultisite::generateCommand();
        } else {
            $commandNameToCheck = WpCliEventRun::$commandName;
            $commandToCheck = WpCliEventRun::generateCommand();
        }

        $cronjobs = $response->getResultItems();
        if ($cronjobs && is_array($cronjobs)) {
            foreach ($cronjobs as $cronjob) {
                if (!isset($cronjob->attributes->enabled) || !$cronjob->attributes->enabled) {
                    continue; // Cronjob not active
                }
                if (!isset($cronjob->attributes->command)) {
                    continue;
                }
                $command = $cronjob->attributes->command;
                if ($command === $commandToCheck) {
                    return true; // We found the command in the crontab
                }

                if (!isset($cronjob->attributes->comment)) {
                    continue;
                }
                $comment = $cronjob->attributes->comment;
                $parsedComment = self::parseComment($comment);
                if (arrayGet('name', $parsedComment) == $commandNameToCheck) {
                    return true; // We found the command name in the crontab
                }
            }
        }
        return false;
    }
}
