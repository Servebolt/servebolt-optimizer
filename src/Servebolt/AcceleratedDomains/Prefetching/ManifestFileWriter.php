<?php

namespace Servebolt\Optimizer\AcceleratedDomains\Prefetching;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\wpDirectFilesystem;

/**
 * Class ManifestWriter
 * @package Servebolt\Optimizer\AcceleratedDomains\Prefetching
 */
class ManifestFileWriter
{

    /**
     * Manifest item types.
     *
     * @var string[]
     */
    //private static $itemTypes = ['style', 'script', 'menu'];
    private static $itemTypes = ['style', 'script'];

    /**
     * An array containing the files that was written to disk.
     *
     * @var array
     */
    private static $writtenFiles = [];

    /**
     * @var bool
     */
    private static $shouldLimitHostname = true;

    /**
     * Manifest file folder name.
     *
     * @var string
     */
    private static $folderPath = 'acd/prefetch';

    /**
     * Manifest file name mask.
     *
     * @var string
     */
    //private static $fileNameMask = 'manifest-%s-%s.txt';
    private static $fileNameMask = 'manifest-%s.txt';

    /**
     * WP Filesystem instance.
     *
     * @var null|\WP_Filesystem_Direct
     */
    private static $fs = null;

    /**
     * Whether to order the items alphabetically before we order by prioritization.
     *
     * @var bool
     */
    private static $orderAlphabetically = false;

    /**
     * Resolve existing manifest files, their path and their type.
     *
     * @return array
     */
    public static function resolveExistingFiles(): ?array
    {
        $fs = self::getFs();
        $folderPath = self::getFolderPath();
        if (!is_dir($folderPath)) {
            return null;
        }
        $files = $fs->dirlist($folderPath);
        if (!is_array($files) || empty($files)) {
            return null;
        }
        return array_map(function($fileName) use ($folderPath) {
            return $folderPath . $fileName;
        }, array_keys($files));
    }

    /**
     * Execute manifest file writing.
     */
    public static function write(): void
    {
        self::ensureManifestFolderExists();

        self::$writtenFiles = []; // Reset value

        foreach (self::getItemTypes() as $itemType) {

            // Skip if we do not have any data for the item type
            if (
                !WpPrefetching::fileIsActive($itemType)
                || !$data = self::prepareData($itemType)
            ) {
                self::clear($itemType);
                continue;
            }

            // Write the contents to the manifest file
            $fileName = self::generateFileName($itemType);
            $filePath = self::getFilePath($fileName);
            (self::getFs())->put_contents(
                $filePath,
                apply_filters('sb_optimizer_prefetching_prefetch_data', $data, $itemType)
            );

            // Flag that we wrote file for this item type
            self::wroteFile($itemType, $fileName);

        }

        // Store which files we wrote to disk
        ManifestFilesModel::store(self::$writtenFiles);

        // Third-party action
        do_action('sb_optimizer_prefetching_manifest_files_written');
    }

    /**
     * Flag a file as written.
     *
     * @param string $itemType
     * @param string $filePath
     * @return void
     */
    private static function wroteFile(string $itemType, string $filePath): void
    {
        self::$writtenFiles[$itemType] = self::getFileUrl($filePath);
    }

    /**
     * Get manifest folder path.
     *
     * @return string
     */
    private static function getFolderPath(): string
    {
        $uploadDir = wp_get_upload_dir();
        return trailingslashit($uploadDir['basedir']) . self::$folderPath . '/';
    }

    /**
     * Get manifest file path.
     *
     * @param string $fileType
     * @return string
     */
    public static function generateFileName(string $fileType): string
    {
        //return sprintf(self::$fileNameMask, $fileType, time());
        return sprintf(self::$fileNameMask, $fileType);
    }

    /**
     * Get manifest folder path.
     *
     * @return string
     */
    private static function getFolderUrl(): string
    {
        $uploadDir = wp_get_upload_dir();
        return trailingslashit($uploadDir['baseurl']) . self::$folderPath . '/';
    }

    /**
     * Get file path.
     *
     * @param $fileName
     * @return string
     */
    private static function getFilePath($fileName): string
    {
        return self::getFolderPath() . $fileName;
    }

    /**
     * Get manifest file path.
     *
     * @param string $fileName
     * @return string
     */
    private static function getFileUrl(string $fileName): string
    {
        return self::getFolderUrl() . $fileName;
    }

    /**
     * Get instance of WP_Filesystem_Direct.
     *
     * @return \WP_Filesystem_Direct
     */
    private static function getFs(): object
    {
        if (is_null(self::$fs)) {
            self::$fs = wpDirectFilesystem();
        }
        return self::$fs;
    }

    /**
     * Ensure we got the manifest file folder.
     */
    private static function ensureManifestFolderExists(): void
    {
        $fs = self::getFs();
        $folderPath = self::getFolderPath();
        $acdFolderPath = dirname($folderPath);
        if (!$fs->exists($acdFolderPath)) {
            $fs->mkdir($acdFolderPath);
        }
        if (!$fs->exists($folderPath)) {
            $fs->mkdir($folderPath);
        }
    }

    /**
     * Clear manifest file(s), and optionally the folder.
     *
     * @param string|null $itemType
     * @param bool $removeFolder
     * @return bool
     */
    public static function clear(?string $itemType = null, bool $removeFolder = false): bool
    {
        $fs = self::getFs();
        if ($itemType) {
            $itemTypes = self::getItemTypes();
            if (!in_array($itemType, $itemTypes)) {
                return false;
            }
            $existingFiles = ManifestFilesModel::get();
            if (array_key_exists($itemType, $existingFiles)) {
                $filePath = self::getFilePath(basename($existingFiles[$itemType]));
                $fs->delete($filePath);
            }
            ManifestFilesModel::remove($itemType);
        } else {

            // Delete using the files model
            if ($existingFiles = ManifestFilesModel::get()) {
                foreach ($existingFiles as $file) {
                    $filePath = self::getFilePath(basename($file));
                    $fs->delete($filePath);
                }
            }
            ManifestFilesModel::clear();

            // Delete any remaining orphaned files
            if ($files = self::resolveExistingFiles()) {
                foreach ($files as $file) {
                    $fs->delete($file);
                }
            }

            // Remove folder (optional)
            if ($removeFolder) {
                $folderPath = self::getFolderPath();
                if ($fs->exists($folderPath)) {
                    $fs->delete($folderPath);
                }
            }
        }

        return true;

    }

    /**
     * Get item types.
     *
     * @return array
     */
    private static function getItemTypes(): array
    {
        return apply_filters('sb_optimizer_prefetching_active_item_types', self::$itemTypes);
    }

    /**
     * Convert a single prefetch item from array to URL.
     *
     * @param $prefetchItem
     * @return false|string
     */
    private static function handlePrefetchItem($prefetchItem)
    {
        $domain = parse_url(get_site_url());

        // Just continue if src is not set
        if (!array_key_exists('src', $prefetchItem) || empty($prefetchItem['src'])) {
            return false;
        }

        // We need the src to be a parsed URL for later
        $url = parse_url($prefetchItem['src']);

        // Set host to current domain if it's not set
        if (empty($url['host'])) {
            $url['host'] = $domain['host'];
        }

        // We can force to HTTPS since ACD only runs on HTTPS
        if (empty($url['scheme'])) {
            $url['scheme'] = apply_filters('sb_optimizer_prefetching_item_scheme', 'https');
        }

        // We only want to prefetch stuff from current domain for now
        if (!self::shouldLimitHostname() || strpos($url['host'], $domain['host']) !== false) {

            if (apply_filters('sb_optimizer_prefetching_include_domain', false)) {
                $line = $url['scheme'] . '://' . $url['host'] . $url['path'];
            } else {
                $line = $url['path'];
            }
            $line = apply_filters('sb_optimizer_prefetching_handle_item', $line, $url);

            // If a version string exists we most likely need to add that to the url
            if (array_key_exists('ver', $prefetchItem) && $prefetchItem['ver']) {
                $line .= '?ver=' . $prefetchItem['ver'];
            }

            // TODO: Add support for sbversion string?

            return $line;
        }

        return false;
    }

    /**
     * Check if we should limit the files to the hostname of the current site.
     *
     * @param bool|null $state
     * @return bool
     */
    public static function shouldLimitHostname(?bool $state = null): ?bool
    {
        if (is_null($state)) {
            return self::$shouldLimitHostname === true;
        }
        self::$shouldLimitHostname = $state;
        return null;
    }

    /**
     * Check if we should limit the files to the hostname of the current site.
     *
     * @param bool|null $state
     * @return bool
     */
    public static function shouldOrderAlphabetically(?bool $state = null): ?bool
    {
        if (is_null($state)) {
            return self::$orderAlphabetically === true;
        }
        self::$orderAlphabetically = $state;
        return null;
    }

    /**
     * Format and prioritize manifest data items.
     *
     * @param string $itemType
     * @return string|false
     */
    private static function prepareData(string $itemType)
    {

        $data = ManifestDataModel::get();
        if (!array_key_exists($itemType, $data)) {
            return false;
        }

        $lines = [];
        $prefetchItems = $data[$itemType];

        if (self::$orderAlphabetically) {
            // Order alphabetically
            usort($prefetchItems, function($a, $b) {
                return strnatcasecmp($a['handle'], $b['handle']);
            });
        }

        // Order the files by priority. Highest priority first.
        usort($prefetchItems, function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        $maxLineNumber = apply_filters('sb_optimizer_prefetching_max_number_of_lines', false, $itemType);
        if (is_numeric($maxLineNumber)) {
            $prefetchItems = array_slice($prefetchItems, 0, $maxLineNumber);
        }

        foreach ($prefetchItems as $prefetchItem) {
            if ($prefetchItem = self::handlePrefetchItem($prefetchItem)) {
                $lines[] = $prefetchItem;
            }
        }
        return empty($lines) ? false : implode(PHP_EOL, $lines);
    }
}
