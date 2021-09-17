<?php

namespace Servebolt\Optimizer\CronControl\Cronjobs;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Utils\EnvFile\Reader as EnvFileReader;

class WpCliEventRun extends AbstractCommand
{

    /**
     * @var string The name of the command.
     */
    public static $commandName = 'wp-cron-single-site';

    /**
     * @var string The revision number of the command.
     */
    public static $commandRevision = '1';

    /**
     * @var string The command template.
     */
    public static $command = 'wp cron event run --due-now --path=%s --quiet';

    /**
     * @var string The interval for the command.
     */
    public static $preferredInterval = '*/10 * * * *';

    /**
     * Get command interval.
     *
     * @return mixed|string
     */
    public static function getInterval()
    {
        /**
         * @param string $preferredInterval At what interval the command should be run.
         * @param string $context The name of the command.
         */
        return apply_filters(
            'sb_optimizer_cron_control_command_interval',
            self::$preferredInterval,
            self::$commandName
        );
    }

    /**
     * Get full crontab row for command.
     *
     * @return mixed|string
     */
    public static function generateCronjob($includeComment = true)
    {
        $cronjob = self::getInterval() . ' ' . self::generateCommand();
        if ($includeComment) {
            $cronjob .= ' ' . self::generateComment();
        }
        /**
         * @param string $cronjob The full row to be inserted into the crontab.
         * @param string $context The name of the command.
         */
        return apply_filters(
            'sb_optimizer_cron_control_command_interval',
            $cronjob,
            self::$commandName
        );
    }

    /**
     * Generate command to be executed.
     *
     * @return mixed|string
     */
    public static function generateCommand()
    {
        $command = sprintf(self::$command, self::getPublicPath());
        /**
         * @param string $command The comment to be executed.
         * @param string $context The name of the command.
         */
        return apply_filters(
            'sb_optimizer_cron_control_command_interval',
            $command,
            self::$commandName
        );
    }

    /**
     * Get sites public path.
     *
     * @return string|null
     */
    public static function getPublicPath()
    {
        $env = EnvFileReader::getInstance();
        return $env->public_dir;
    }
}
