<?php

namespace Servebolt\Optimizer\CronControl\Commands;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\generateRandomInteger;

abstract class AbstractCommand
{
    use Singleton;

    /**
     * @var int Run cron every nth minute.
     */
    private static $minuteInterval = 5;

    /**
     * @var string Template used when generating cronjob comment.
     */
    private static $commentTemplate = 'Generated by Servebolt Optimizer (name=%s,rev=%s)';

    /**
     * Generate the comment that will be passed as meta data for the cronjob. This is later used for identification.
     *
     * @return string
     */
    public static function generateComment(): string
    {
        return sprintf(
            self::$commentTemplate,
            static::$commandName,
            static::$commandRevision
        );
    }

    /**
     * Get command interval.
     *
     * @return mixed|string
     */
    public static function getInterval()
    {
        $minuteInterval = self::createCronMinuteIntervals(
            self::$minuteInterval,
            generateRandomInteger(0, (self::$minuteInterval-1))
        );
        $intervalPattern = '%s * * * *';
        $intervalString = sprintf($intervalPattern, $minuteInterval);

        /**
         * @param string $intervalString At what interval the command should be run.
         * @param string $minuteInterval At what minute interval the command should be run.
         * @param string $intervalPattern The template being used when building the full interval string.
         * @param string $context The name of the command.
         */
        return apply_filters(
            'sb_optimizer_cron_control_command_interval',
            $intervalString,
            $minuteInterval,
            $intervalPattern,
            static::$commandName
        );
    }

    /**
     * Create cron minute interval string with support for offset.
     *
     * @param int $interval
     * @param int|null $offset
     * @return string|null
     */
    private static function createCronMinuteIntervals(int $interval, ?int $offset = null): ?string
    {
        if ($offset >= $interval) {
            return null;
            //throw new \Exception('Offset cannot be equal to or bigger than interval.');
        }
        $steps = [];
        for ($i = 0; $i <= 59; $i = $i + $interval) {
            $steps[] = $i + $offset;
        }
        return implode(',', $steps);
    }
}
