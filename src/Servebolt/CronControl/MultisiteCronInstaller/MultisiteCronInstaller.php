<?php

namespace Servebolt\Optimizer\CronControl\MultisiteCronInstaller;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Utils\EnvFile\Reader as EnvFileReader;
use function Servebolt\Optimizer\Helpers\wpDirectFilesystem;

/**
 * Class MultisiteCronInstaller
 * @package Servebolt\Optimizer\CronControl\MultisiteCronInstaller
 */
class MultisiteCronInstaller
{
    /**
     * @var string The filename of the script.
     */
    private static $filename = 'run-wp-cron.sh';

    /**
     * @var string The file permissions for the script.
     */
    private static $fileChmod = 0754;

    /**
     * Execute script file installation.
     *
     * @param null $installPath
     * @return string|null
     */
    public static function install($installPath = null): ?string
    {
        if (!self::fileExists($installPath)) {
            if (!self::installFile($installPath)) {
                return null;
            }
        }
        if (!self::fileIsExecutable($installPath)) {
            if (!self::makeFileExecutable($installPath)) {
                return null;
            }
        }
        return self::getFilePath($installPath);
    }

    /**
     * Get the raw file content.
     *
     * @return string
     */
    public static function getFileRawContent(): string
    {
        return require __DIR__ . '/ScriptTemplate.php';
    }

    /**
     * Get the file content and populate variables.
     *
     * @return string
     */
    public static function getFileContent(): ?string
    {
        return self::populateVariables(
            self::getFileRawContent()
        );
    }

    /**
     * Get the path to the root WP directory.
     *
     * @return mixed
     */
    private static function getWpPath()
    {
        $env = EnvFileReader::getInstance();
        return $env->public_dir;
    }

    /**
     * Populate script variables.
     *
     * @param $fileContent
     * @return string
     */
    private static function populateVariables($fileContent): ?string
    {
        // Set WP path
        if (!$wpPath = self::getWpPath()) {
            return null;
        }
        $fileContent = str_replace('/path/to/wp', $wpPath, $fileContent);

        return $fileContent;
    }

    /**
     * Install script-file.
     *
     * @param null $installPath
     * @return bool
     */
    private static function installFile($installPath = null): bool
    {
        $fs = wpDirectFilesystem();

        if (!$fileContent = self::getFileContent()) {
            return false; // We could not generate script file content
        }

        return $fs->put_contents(
            self::getFilePath($installPath),
            $fileContent,
            self::$fileChmod
        ) === true;
    }

    /**
     * Make file executable.
     *
     * @param null $installPath
     * @return bool
     */
    private static function makeFileExecutable($installPath = null)
    {
        $fs = wpDirectFilesystem();
        return $fs->chmod(
            self::getFilePath($installPath),
            self::$fileChmod
        ) === true;
    }

    /**
     * Resolve the install folder path.
     *
     * @return string
     */
    private static function resolveInstallPath(): string
    {
        $env = EnvFileReader::getInstance();
        return $env->home_dir;
    }

    /**
     * Get the path of the script file.
     *
     * @param null $installPath
     * @return string
     */
    public static function getFilePath($installPath = null): string
    {
        if (!$installPath) {
            $installPath = self::resolveInstallPath();
        }
        return $installPath. '/' . self::$filename;
    }

    /**
     * Check whether the script file is executable.
     *
     * @param null $installPath
     * @return bool
     */
    public static function fileIsExecutable($installPath = null): bool
    {
        return is_executable(self::getFilePath($installPath));
    }

    /**
     * Check whether the script file exists.
     *
     * @param null $installPath
     * @return bool
     */
    public static function fileExists($installPath = null): bool
    {
        $fs = wpDirectFilesystem();
        return $fs->exists(
            self::getFilePath($installPath)
        );
    }

    /**
     * Check if script file is installed correctly.
     *
     * @param null $installPath
     * @return bool
     */
    public static function isInstalled($installPath = null): bool
    {
        return self::fileExists($installPath)
            && self::fileIsExecutable($installPath);
    }
}
