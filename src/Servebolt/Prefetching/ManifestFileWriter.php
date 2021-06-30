<?php

namespace Servebolt\Optimizer\Prefetching;

use function Servebolt\Optimizer\Helpers\wpDirectFilesystem;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class ManifestWriter
 * @package Servebolt\Optimizer\Prefetching
 */
class ManifestFileWriter
{

    /**
     * Manifest item types.
     *
     * @var string[]
     */
    private static $itemTypes = ['style', 'script', 'menu'];

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
    private static $fileNameMask = 'manifest-%s.txt';

    /**
     * WP Filesystem instance.
     *
     * @var null|\WP_Filesystem_Direct
     */
    private static $fs = null;

    /**
     * Whether to clear the manifest data from options after writing to files.
     *
     * @var bool
     */
    private static $clearDataAfterFileWrite = false;

    /**
     * Whether to order the items alphabetically before we order by prioritization.
     *
     * @var bool
     */
    private static $orderAlphabetically = false;

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
            (self::getFs())->put_contents(
                self::getFilePath($itemType),
                apply_filters('sb_optimizer_prefetch_data', $data, $itemType)
            );

            // Flag that we wrote file for this item type
            self::wroteFile($itemType);

        }

        // Store which files we wrote to disk
        self::storeWrittenFiles();

        do_action('sb_optimizer_prefetch_manifest_files_written');

        // Maybe clear data in options
        if (self::shouldClearDataAfterFileWrite()) {
            ManifestDataModel::clear();
        }
    }

    /**
     * Store the URLs of the files we wrote to the disk.
     *
     */
    private static function storeWrittenFiles()
    {
        ManifestFilesModel::store(self::$writtenFiles);
    }

    /**
     * Remove a given manifest file for written files.
     *
     * @param string $itemType
     */
    public function removeFromWrittenFiles(string $itemType): void
    {
        ManifestFilesModel::remove(self::getFileUrl($itemType));
    }

    /**
     * Flag a file as written.
     *
     * @param string $itemType
     */
    private static function wroteFile(string $itemType): void
    {
        self::$writtenFiles[] = self::getFileUrl($itemType);
    }

    /**
     * Check whether we should clear the options data after file write.
     *
     * @return bool
     */
    private static function shouldClearDataAfterFileWrite(): bool
    {
        return apply_filters('sb_optimizer_prefetch_clear_manifest_data_after_file_write', self::$clearDataAfterFileWrite);
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
    public static function getFilePath(string $fileType): string
    {
        return self::getFolderPath() . sprintf(self::$fileNameMask, $fileType);
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
     * Get manifest file path.
     *
     * @param string $fileType
     * @return string
     */
    private static function getFileUrl(string $fileType): string
    {
        return self::getFolderUrl() . sprintf(self::$fileNameMask, $fileType);
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
        $itemTypes = self::getItemTypes();
        if ($itemType) {
            if (!in_array($itemType, $itemTypes)) {
                return false;
            }
            $itemTypes = [$itemType];
        }

        foreach ($itemTypes as $itemType) {
            $filePath = self::getFilePath($itemType);
            if ($fs->exists($filePath)) {
                $fs->delete($filePath);
            }
        }

        if ($removeFolder) {
            $folderPath = self::getFolderPath();
            if ($fs->exists($folderPath)) {
                $fs->delete($folderPath);
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
        return apply_filters('sb_optimizer_prefetch_active_item_types', self::$itemTypes);
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
            $url['scheme'] = 'https';
        }

        // We only want to prefetch stuff from current domain for now
        if (!self::shouldLimitHostname() || strpos($url['host'], $domain['host']) !== false) {

            //$line = $url['scheme'] . '://' . $url['host'] . $url['path'];
            $line = apply_filters('sb_optimizer_prefetch_handle_item', $url['path'], $url);

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

        $maxLineNumber = apply_filters('sb_optimizer_prefetch_max_number_of_lines', false, $itemType);
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
