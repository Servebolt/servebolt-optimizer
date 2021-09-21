<?php

namespace Servebolt\Optimizer\CronControl\Cronjobs;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CronControl\Scripts\WpCronMultisiteScript\WpCronMultisiteScript;

class WpCliEventRunMultisite extends AbstractCommand
{

    /**
     * @var string The name of the command.
     */
    public static $commandName = 'wp-cron-multisite';

    /**
     * @var string The revision number of the command.
     */
    public static $commandRevision = '1';

    /**
     * @var string The command template.
     */
    public static $command = '%s';

    /**
     * @var string The interval for the command.
     */
    public static $preferredInterval = '*/30 * * * *';

    /**
     * Generate command to be executed.
     *
     * @return mixed|string
     */
    public static function generateCommand()
    {
        $command = sprintf(static::$command, self::getScriptPath());
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
        return (new WpCronMultisiteScript)->getFilePath();
    }
}
