<?php

namespace Servebolt\Optimizer\CronControl\Scripts\ActionSchedulerMultisiteScript;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CronControl\Scripts\SharedMethods;
use Servebolt\Optimizer\Utils\ScriptInstaller\ScriptInstaller;
use Servebolt\Optimizer\Utils\ScriptInstaller\ScriptInstallerInterface;

/**
 * Class ActionSchedulerMultisiteScript
 * @package Servebolt\Optimizer\CronControl\Scripts\ActionSchedulerMultisiteScript
 */
class ActionSchedulerMultisiteScript extends ScriptInstaller implements ScriptInstallerInterface
{
    use SharedMethods;

    /**
     * @var string The filename of the script.
     */
    protected $filename = 'run-action-scheduler.sh';

    /**
     * Get output filename.
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->filename;
    }

    /**
     * Get the raw file content.
     *
     * @return string
     */
    protected function getFileRawContent(): string
    {
        return require __DIR__ . '/ScriptTemplate.php';
    }
}
