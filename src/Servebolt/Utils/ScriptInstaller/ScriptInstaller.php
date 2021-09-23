<?php

namespace Servebolt\Optimizer\Utils\ScriptInstaller;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Utils\EnvFile\Reader as EnvFileReader;
use function Servebolt\Optimizer\Helpers\wpDirectFilesystem;

/**
 * Class ScriptInstaller
 * @package Servebolt\Optimizer\Utils
 */
class ScriptInstaller
{

    /**
     * @var string The file permissions for the script.
     */
    protected $fileChmod = 0754;

    /**
     * @var null The path where the script is/should be located.
     */
    protected $installPath = null;

    /**
     * ScriptInstaller constructor.
     *
     * @param null|string $installPath
     */
    public function __construct($installPath = null)
    {
        if ($installPath) {
            $this->setInstallPath($installPath);
        }
    }

    /**
     * Execute script file installation.
     *
     * @return string|null
     */
    public function install(): ?string
    {
        if (!$this->fileExists()) {
            if (!$this->installFile()) {
                return null;
            }
        }
        if (!$this->fileIsExecutable()) {
            if (!$this->makeFileExecutable()) {
                return null;
            }
        }
        return $this->getFilePath();
    }

    /**
     * Attempt to uninstall script.
     *
     * @return bool
     */
    public function uninstall(): bool
    {
        if ($this->fileExists()) {
            return $this->deleteFile();
        }
        return true;
    }

    /**
     * Set the path where the script is/should be located.
     *
     * @param null|string $installPath
     */
    public function setInstallPath(?string $installPath): void
    {
        $this->installPath = $installPath;
    }

    /**
     * Delete file.
     *
     * @return bool
     */
    private function deleteFile(): bool
    {
        $fs = wpDirectFilesystem();
        return $fs->delete($this->getFilePath());
    }

    /**
     * Install script-file.
     *
     * @return bool
     */
    private function installFile(): bool
    {
        $fs = wpDirectFilesystem();

        if (!$fileContent = $this->getFileContent()) {
            return false; // We could not generate script file content
        }

        return $fs->put_contents(
            $this->getFilePath(),
            $fileContent,
            $this->fileChmod
        ) === true;
    }

    /**
     * Make file executable.
     *
     * @return bool
     */
    private function makeFileExecutable()
    {
        $fs = wpDirectFilesystem();
        return $fs->chmod(
            $this->getFilePath(),
            $this->fileChmod
        ) === true;
    }

    /**
     * Resolve the install folder path.
     *
     * @return string
     */
    private function resolveInstallPath(): string
    {
        if (is_string($this->installPath)) {
            return $this->installPath;
        }
        $env = EnvFileReader::getInstance();
        return $env->home_dir;
    }

    /**
     * Get the path of the script file.
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return trailingslashit($this->resolveInstallPath()) . $this->getFileName();
    }

    /**
     * Check whether the script file is executable.
     *
     * @return bool
     */
    public function fileIsExecutable(): bool
    {
        return is_executable($this->getFilePath());
    }

    /**
     * Check whether the script file exists.
     *
     * @return bool
     */
    public function fileExists(): bool
    {
        $fs = wpDirectFilesystem();
        return $fs->exists(
            $this->getFilePath()
        );
    }

    /**
     * Check if script file is installed correctly.
     *
     * @return bool
     */
    public function isInstalled(): bool
    {
        return $this->fileExists()
            && $this->fileIsExecutable();
    }
}
