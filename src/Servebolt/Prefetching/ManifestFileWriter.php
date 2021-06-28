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
     * Pre-file write line storage.
     *
     * @var string
     */
    private static $fileLines = [];

    /**
     * @var bool
     */
    private static $shouldLimitHostname = true;

    /**
     * Manifest file folder name.
     *
     * @var string
     */
    private static $folderName = 'prefetch';

    /**
     * Manifest file name.
     *
     * @var string
     */
    private static $fileName = 'manifest.txt';

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
    private static $clearDataAfterFileWrite = true;

    /**
     * Get manifest folder path.
     *
     * @return string
     */
    private static function getFolderPath(): string
    {
        $uploadDir = wp_get_upload_dir();
        return trailingslashit($uploadDir['basedir']) . self::$folderName . '/';
    }

    /**
     * Get manifest file path.
     *
     * @return string
     */
    public static function getFilePath(): string
    {
        return self::getFolderPath() . self::$fileName;
    }

    /**
     * Get manifest folder path.
     *
     * @return string
     */
    private static function getFolderUrl(): string
    {
        $uploadDir = wp_get_upload_dir();
        return trailingslashit($uploadDir['baseurl']) . self::$folderName . '/';
    }

    /**
     * Get manifest file path.
     *
     * @return string
     */
    private static function getFileUrl(): string
    {
        return self::getFolderUrl() . self::$fileName;
    }

    /**
     * Get instance of WP_Filesystem_Direct.
     *
     * @return \WP_Filesystem_Direct
     */
    private static function getFs()
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
        if (!$fs->exists($folderPath)) {
            $fs->mkdir($folderPath);
        }
    }

    /**
     * Clear the manifest file, and optionally the folder.
     */
    public static function clear(bool $removeFolder = false): void
    {
        $fs = self::getFs();
        $filePath = self::getFilePath();
        if ($fs->exists($filePath)) {
            $fs->delete($filePath);
        }
        if ($removeFolder) {
            $folderPath = self::getFolderPath();
            if ($fs->exists($folderPath)) {
                $fs->delete($folderPath);
            }
        }
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

            $line = $url['scheme'] . '://' . $url['host'] . $url['path'];

            // If a version string exists we most likely need to add that to the url
            if ($prefetchItem['ver']) {
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
     * Format and prioritize manifest data items.
     *
     * @return bool
     */
    private static function prepareData(): bool
    {
        $data = ManifestModel::get();
        foreach(['style', 'script', 'menu'] as $itemType) {
            if (!array_key_exists($itemType, $data)) {
                continue;
            }

            $prefetchItems = $data[$itemType];

            // Order the files by priority. Highest priority first.
            usort($prefetchItems, function ($a, $b) {
                return $b['priority'] <=> $a['priority'];
            });

            foreach ($prefetchItems as $prefetchItem) {
                if ($prefetchItem = self::handlePrefetchItem($prefetchItem)) {
                    self::$fileLines[] = $prefetchItem;
                }
            }
        }
        return !empty(self::$fileLines);
    }

    /**
     * Get prepared manifest data.
     *
     * @return string
     */
    private static function getData(): string
    {
        return implode(PHP_EOL, self::$fileLines);
    }

    /**
     * Execute manifest file writing
     */
    public static function write()
    {
        if (!self::prepareData()) {
            return;
        }

        self::ensureManifestFolderExists();

        // Write the contents to the manifest file
        (self::getFs())->put_contents(
            self::getFilePath(),
            self::getData()
        );

        // Maybe clear data in options
        if (apply_filters('sb_optimizer_clear_manifest_data_after_file_write', self::$clearDataAfterFileWrite)) {
            ManifestModel::clear();
        }
    }
}
