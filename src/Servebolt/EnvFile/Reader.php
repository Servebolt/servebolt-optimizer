<?php

namespace Servebolt\Optimizer\EnvFile;

use Servebolt\Optimizer\Traits\Singleton;

class Reader
{

    use Singleton;

    /**
     * The possible file extensions of the environment files.
     *
     * @var array|string[]
     */
    private $fileExtensions = ['json', 'ini'];

    /**
     * Array containing the extracted data.
     *
     * @var array
     */
    private $extractedData = [];

    /**
     * Whether we could read the file or not.
     *
     * @var bool
     */
    private bool $success = false;

    /**
     * The basename of the environment file.
     *
     * @var string
     */
    private $basename = 'environment';

    private $desiredFileType;
    private $resolvedFileType;
    private $folderPath;

    public function __construct($folderPath = null, $desiredFileType = 'auto', $basename = null)
    {
        $this->setBasename($basename);
        $this->setDesiredFileType($desiredFileType);
        $this->resolveFolderPath($folderPath);
        if ($filePath = $this->resolveEnvironmentFilePath()) {
            $this->readEnvironmentFile($filePath);
        }
    }

    public function isFileType($type) : bool
    {
        return $type === $this->resolvedFileType;
    }

    public function getExtractedData() : array
    {
        return $this->extractedData;
    }

    private function setBasename($basename)
    {
        if (!is_null($basename)) {
            $this->basename = $basename;
        }
    }

    public function isSuccess(): bool
    {
        return $this->success === true;
    }

    private function readJsonFile($filePath) : bool
    {
        $fileContent = file_get_contents($filePath);
        $decoded = json_decode($fileContent, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        $this->extractedData = $decoded;
        $this->success = true;
        return true;
    }

    private function readIniFile($filePath) : bool
    {
        try {
            if (($parsedData = @parse_ini_file($filePath)) == false) {
                throw new Exception('Invalid INI file');
            }
            $this->extractedData = $parsedData;
            $this->success = true;
            return true;
        }
        catch (Exception $e) {
            return false;
        }
    }

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
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->extractedData)) {
            return $this->extractedData[$name];
        }
        return null;
    }

    private function setDesiredFileType($desiredFileType) : bool
    {
        if ($desiredFileType === 'auto' || in_array($desiredFileType, $this->fileExtensions)) {
            $this->desiredFileType = $desiredFileType;
            return true;
        }
        return false;
    }

    /**
     * Check whether we should skip a certain file type when looking for the environment file.
     *
     * @param $type
     * @return bool
     */
    private function shouldSkipFileType($type) : bool
    {
        if ($this->desiredFileType === 'auto') {
            return false;
        }
        return $type !== $this->desiredFileType;
    }

    /**
     * Resolve the environment file, in prioritized order.
     *
     * @return false|string
     */
    private function resolveEnvironmentFilePath()
    {
        foreach ($this->fileExtensions as $type) {
            $envFileName = $this->basename . '.' . $type;
            if ($this->shouldSkipFileType($type)) {
                continue; // Skip this if we're only looking for a certain type of env file
            }
            $path = $this->folderPath . $envFileName;
            if (file_exists($path) && is_readable($path)) {
                $this->resolvedFileType = $type;
                return $path;
            }
        }
        return false;
    }

    /**
     * Resolve the path to the folder where the environment files are stored.
     *
     * @param $folderPath
     * @return string
     */
    private function resolveFolderPath($folderPath) : string
    {
        if (is_null($folderPath)) {
            $this->folderPath = rtrim($this->getDefaultFolderPath(), '/')  . '/';
        } else {
            $this->folderPath = rtrim($folderPath, '/') . '/';
        }
        return $this->folderPath;
    }

    /**
     * Get default folder path to the environment files.
     *
     * @return string
     */
    private function getDefaultFolderPath() : string
    {
        return defined('ABSPATH') ? dirname(ABSPATH) : '';
    }
}
