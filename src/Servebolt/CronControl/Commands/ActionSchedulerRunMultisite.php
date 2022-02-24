<?php

namespace Servebolt\Optimizer\CronControl\Commands;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CronControl\Scripts\ActionSchedulerMultisiteScript\ActionSchedulerMultisiteScript;
use function Servebolt\Optimizer\Helpers\strContains;

/**
 * Class ActionSchedulerRunMultisite
 * @package Servebolt\Optimizer\CronControl\Commands
 */
class ActionSchedulerRunMultisite extends AbstractCommand
{

    /**
     * @var string The name of the command.
     */
    public static $commandName = 'action-scheduler-multisite';

    /**
     * @var string The revision number of the command.
     */
    public static $commandRevision = '1';

    /**
     * @var string The command template.
     */
    public static $baseCommand = '%s';

    /**
     * Try to match the current command with a specified command.
     *
     * @param $command
     * @return bool
     */
    public static function is($command): bool
    {
        if (strContains($command, self::generateCommand())) {
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
        $command = sprintf(static::$baseCommand, self::getScriptPath());

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
     * Get script path.
     *
     * @return string|null
     */
    public static function getScriptPath(): ?string
    {
        return (new ActionSchedulerMultisiteScript())->getFilePath();
    }
}
