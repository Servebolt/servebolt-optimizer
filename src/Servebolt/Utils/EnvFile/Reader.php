<?php

namespace Servebolt\Optimizer\Utils\EnvFile;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Exception;
use Throwable;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\deleteOption;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;
use function Servebolt\Optimizer\Helpers\updateOption;

/**
 * Class Reader
 * @package Servebolt\Optimizer\Utils\EnvFile
 */
class Reader
{
    use Singleton;

    /**
     * @var array|string[] The possible file extensions of the environment files.
     */
    private $allowedFileExtensions = ['json', 'ini'];

    /**
     * @var array Array containing the extracted data from the environment file.
     */
    private $extractedData = [];

    /**
     * @var bool Whether we could read the file or not.
     */
    private $success = false;

    /**
     * @var string Regex-pattern used to resolve user home folder when we can not resolve it from Wordpress-paths.
     */
    private $folderLocateRegex = '/(\/kunder\/[a-z_0-9]+\/[a-z_]+(\d+))/';

    /**
     * @var string The basename of the environment file.
     */
    private $basename = 'environment';

    /**
     * @var string The environment file type to read (JSON or INI) (optional, defaults to auto resolution).
     */
    private $selectedFileExtension;

    /**
     * @var string The type of file that was resolved.
     */
    private $resolvedFileType;

    /**
     * @var string The path to the folder that contains the environment file (used for overrides).
     */
    private $folderPath;

    /**
     * @var bool Used for testing.
     */
    private static $enabled = true;

    /**
     * @var string The key used to cache the env file path.
     */
    private $optionsKey = 'env_file_path3';

    public function __construct($folderPath = null, $selectedFileExtension = 'auto', $basename = null)
    {
        if (!isHostedAtServebolt()) {
            return;
        }
        if (!is_null($basename)) {
            $this->setBasename($basename);
        }
        if ($folderPath) {
            $this->setFolderPath($folderPath);
        }
        try {
            $this->setSelectedFileExtension($selectedFileExtension);
            $envFilePath = $this->resolveEnvFilePath();
            if (!$envFilePath) {
                throw new Exception('Could not resolve environment file path.', 69);
            }
            if (!$this->fileFound($envFilePath)) {
                deleteOption($this->optionsKey);
                throw new Exception('Environment file not accessible.');
            }
            $this->readEnvironmentFile($envFilePath);
        } catch (Throwable $e) {
            do_action('sb_optimizer_env_file_reader_failure', $e);
        }
    }

    /**
     * Resolve path to environment file, either by getting it from cache or by looking for it on the disk.
     *
     * @return false|string
     */
    private function resolveEnvFilePath()
    {
        $pathFromCache = $this->resolveEnvFilePathFromCache();
        if ($pathFromCache && $this->fileFound($pathFromCache)) {
            var_dump('from-cache');
            return $pathFromCache;
        }

        $pathFromDiskLookup = $this->resolveEnvironmentFilePathFromDisk();
        if ($pathFromDiskLookup) {
            var_dump('from-disk');
            updateOption($this->optionsKey, $pathFromDiskLookup);
            return $pathFromDiskLookup;
        }

        return false;
    }

    /**
     * Resolve alleged path to environment file from cache.
     *
     * @return false|string
     */
    private function resolveEnvFilePathFromCache()
    {
        $path = getOption($this->optionsKey);
        return (is_string($path) && !empty($path)) ? $path : false;
    }

    /**
     * Resolve the environment file on the disk, either by using WordPress-paths or by looking for it manually.
     *
     * @return false|string
     */
    private function resolveEnvironmentFilePathFromDisk()
    {
        foreach ($this->allowedFileExtensions as $type) {
            if ($resolvedFile = $this->lookupFileByType($type)) {
                return $resolvedFile;
            }
        }
        return false;
    }

    /**
     * Lookup environment file by type/extension.
     *
     * @param $type
     * @return false|string|void
     */
    private function lookupFileByType($type)
    {
        if ($this->shouldSkipFileType($type)) {
            return false; // Skip this if we're only looking for a certain type of env file
        }
        $envFileName = $this->basename . '.' . $type;
        $filePath = $this->attemptToLocateFile($envFileName);
        return $filePath ?: false;
    }

    /**
     * Get default folder path to the environment files (according to WordPress).
     *
     * @return string
     * @throws Exception
     */
    private function getDefaultFolderPath() : string
    {
        if (isset($_SERVER['DOCUMENT_ROOT'])) {
            return trailingslashit(dirname($_SERVER['DOCUMENT_ROOT']));
        }
        if (defined('ABSPATH')) {
            return trailingslashit(dirname(ABSPATH));
        }
        throw new Exception('Could not determine default environment file folder path.');
    }

    /**
     * Attempt to locate the environment file by either folder path override, using WordPress-paths or by manually looking it up using our the patterns of Servebolt infrastructure.
     *
     * @param $fileName
     * @return false|string
     * @throws Exception
     */
    private function attemptToLocateFile($fileName)
    {
        // Look for file in specified folder path (optional)
        if ($this->folderPath) {
            $attemptPath = $this->folderPath . $fileName;
            if ($this->fileFound($attemptPath)) {
                return $attemptPath;
            }
        }

        // Locate the file in standard folder (will work with most WP installations).
        $defaultFolderPath = $this->getDefaultFolderPath();
        $attemptPath = $defaultFolderPath . $fileName;
        if ($this->fileFound($attemptPath)) {
            return $attemptPath;
        }

        // Locate file manually (for non-standard WP installations).
        $locatedFolderPath = $this->locateFolderPathFromDefaultPath($defaultFolderPath);
        if (!$locatedFolderPath) {
            return false;
        }
        $attemptPath = $locatedFolderPath . $fileName;
        if ($this->fileFound($attemptPath)) {
            return $attemptPath;
        }

        return false; // Could not resolve file by filename
    }

    /**
     * Locate users home folder from WordPress-path by using pattern matching.
     *
     * @param $searchFolderPath
     * @return false|string
     */
    private function locateFolderPathFromDefaultPath($searchFolderPath)
    {
        if (
            preg_match(apply_filters('sb_optimizer_env_file_reader_folder_regex_pattern', $this->folderLocateRegex), $searchFolderPath, $matches)
            && isset($matches[1])
            && !empty($matches[1])
        ) {
            return trailingslashit($matches[1]);
        }
        return false;
    }

    /**
     * Determine whether we found a file and if it is readable.
     *
     * @param $path
     * @return bool
     */
    private function fileFound($path): bool
    {
        return file_exists($path) && is_readable($path);
    }

    /**
     * Set folder path to look for environment files (used for overriding).
     *
     * @param $folderPath
     * @return void
     */
    private function setFolderPath($folderPath): void
    {
        $this->folderPath = trailingslashit($folderPath);
    }

    /**
     * Disable feature (used for testing).
     *
     * @return void
     */
    public static function disable()
    {
        self::$enabled = false;
    }

    /**
     * Enable feature (used for testing).
     *
     * @return void
     */
    public static function enable()
    {
        self::$enabled = true;
    }

    /**
     * Check if the resolved file type is a given type.
     *
     * @param $type
     * @return bool
     */
    public function isFileType($type) : bool
    {
        return $type === $this->resolvedFileType;
    }

    /**
     * Set the basename of the environment file.
     *
     * @param $basename
     * @return void
     */
    private function setBasename($basename)
    {
        $this->basename = $basename;
    }

    /**
     * Whether we could successfully read the environment file.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success === true;
    }

    /**
     * Read environment file in JSON-format.
     *
     * @param $filePath
     * @return bool
     * @throws Exception
     */
    private function readJsonFile($filePath) : bool
    {
        $fileContent = file_get_contents($filePath);
        $decoded = json_decode($fileContent, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Could not read environment file (.json) content.');
        }
        $this->extractedData = $decoded;
        $this->success = true;
        return true;
    }

    /**
     * Read environment file in INI-format.
     *
     * @param $filePath
     * @return bool
     * @throws Exception
     */
    private function readIniFile($filePath) : bool
    {
        if (($parsedData = @parse_ini_file($filePath)) == false) {
            throw new Exception('Could not read environment file (.ini) content.');
        }
        $this->extractedData = $parsedData;
        $this->success = true;
        return true;
    }

    /**
     * Read environment-file (automatic file extension handling).
     *
     * @param $filePath
     * @return bool
     * @throws Exception
     */
    private function readEnvironmentFile($filePath) : bool
    {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        switch ($ext) {
            case 'json':
                return $this->readJsonFile($filePath);
            case 'ini':
                return $this->readIniFile($filePath);
        }
        return false;
    }

    /**
     * Get property from env-file.
     *
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        if (!self::$enabled) {
            return null;
        }
        if (array_key_exists($name, $this->extractedData)) {
            /**
             * Filter the output of the environment file.
             *
             * @param mixed $value The value about to be returned.
             * @param string $name The name of the value.
             * @param object $instance The instance of the environment file reader class.
             */
            return apply_filters(
                'sb_optimizer_env_file_reader_get_' . $name,
                $this->extractedData[$name],
                $name,
                $this
            );
        }
        return null;
    }

    /**
     * Set the file extension of the environment file we should look for (default to auto resolution).
     *
     * @param $selectedFileExtension
     * @return bool
     * @throws Exception
     */
    private function setSelectedFileExtension($selectedFileExtension): bool
    {
        if ($selectedFileExtension === 'auto' || in_array($selectedFileExtension, $this->allowedFileExtensions)) {
            $this->selectedFileExtension = $selectedFileExtension;
            return true;
        }
        throw new Exception;
    }

    /**
     * Check whether we should skip a certain file type when looking for the environment file.
     *
     * @param $type
     * @return bool
     */
    private function shouldSkipFileType($type) : bool
    {
        if ($this->selectedFileExtension === 'auto') {
            return false;
        }
        return $type !== $this->selectedFileExtension;
    }
}
