<?php

namespace Servebolt\Optimizer\Utils\ScriptInstaller;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

interface ScriptInstallerInterface
{
    /**
     * Get script file content.
     *
     * @return null|string
     */
    function getFileContent(): ?string;

    /**
     * Get script filename.
     *
     * @return string
     */
    function getFilename(): string;
}
