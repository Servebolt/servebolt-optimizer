<?php

namespace Servebolt\Optimizer\CronControl\Commands;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Utils\EnvFile\Reader as EnvFileReader;
use function Servebolt\Optimizer\Helpers\strContains;

/**
 * Class ActionSchedulerRun
 * @package Servebolt\Optimizer\CronControl\Commands
 */
class ActionSchedulerRun extends AbstractCommand
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
     * @var string The base command template.
     */
    public static $command = 'wp action-scheduler run';

    /**
     * @var string The command argument template.
     */
    public static $arguments = '--path=%s --quiet';

    /**
     * Try to match the current command with a given command.
     *
     * @param string $command
     * @return bool
     */
    public static function is($command): bool
    {
        if (
            strContains($command, self::$command) // Check if base command is present
            && strContains($command, self::getPublicPath()) // Check that correct path is present
        ) {
            return true;
        }
        return false;
    }

    /**
     * Generate command to be executed.
     *
     * @return mixed|string
     */
    public static function generateCommand()
    {
        $command = self::generateBaseCommand() . ' ' . self::generateArguments();

        /**
         * @param string $command The comment to be executed.
         * @param string $context The name of the command.
         */
        return apply_filters(
            'sb_optimizer_cron_control_command',
            $command,
            self::$commandName
        );
    }

    /**
     * Generate base command.
     *
     * @return string
     */
    public static function generateBaseCommand(): string
    {
        /**
         * @param string $baseCommand The base comment to be executed.
         * @param string $context The name of the command.
         */
        return apply_filters(
            'sb_optimizer_cron_control_base_command',
            static::$command,
            self::$commandName
        );
    }

    /**
     * Generate command arguments.
     *
     * @return string
     */
    public static function generateArguments(): string
    {
        $arguments = sprintf(static::$arguments, self::getPublicPath());

        /**
         * @param string $baseCommand The base comment to be executed.
         * @param string $context The name of the command.
         */
        return apply_filters(
            'sb_optimizer_cron_control_command_arguments',
            $arguments,
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
