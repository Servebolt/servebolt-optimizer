<?php

namespace Servebolt\Optimizer\CronControl\Cronjobs;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Utils\EnvFile\Reader as EnvFileReader;

class ActionScheduler extends AbstractCommand
{

    /**
     * @var string The name of the command.
     */
    public static $commandName = 'action-scheduler-single-site';

    /**
     * @var string The revision number of the command.
     */
    public static $commandRevision = '1';

    /**
     * @var string The command template.
     */
    public static $command = 'wp action-scheduler run --path=%s --quiet';

    /**
     * @var string The interval for the command.
     */
    public static $preferredInterval = '* * * * *';

    /**
     * Generate command to be executed.
     *
     * @return mixed|string
     */
    public static function generateCommand()
    {
        $command = sprintf(static::$command, self::getPublicPath());
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
    public static function getPublicPath(): ?string
    {
        $env = EnvFileReader::getInstance();
        return $env->public_dir;
    }
}
