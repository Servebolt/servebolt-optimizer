<?php

namespace Servebolt\Optimizer\AcceleratedDomains\Prefetching;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_Http;
use Exception;

/**
 * Class Prefetching
 * @package Servebolt\Optimizer\AcceleratedDomains\Prefetching
 */
class Prefetching
{
    /**
     * Manifest files data.
     *
     * @var array
     */
    private $manifestData = [];

    /**
     * Extracted dependencies.
     *
     * @var array
     */
    private $manifestDependencies = [];

    /**
     * Key used to store transient.
     *
     * @var string
     */
    private static $transientKey = 'servebolt_manifest_written';

    /**
     * Check whether we have already loaded the front page during this execution.
     *
     * @var bool
     */
    private static $alreadyRecorded = false;

    /**
     * Check whether we have already refreshed the manifest files (exposed them to Cloudflare).
     *
     * @var bool
     */
    private static $alreadyRefreshed = false;

    /**
     * Expiration in seconds for transient.
     *
     * @var int
     */
    private $transientExpiration = MONTH_IN_SECONDS;

    /**
     * "Record" all scripts so that we can prefetch them.
     */
    public function getScriptsToPrefetch(): void
    {
        $this->handleAssets('script');
    }

    /**
     * "Record" all styles so that we can prefetch them.
     */
    function getStylesToPrefetch(): void
    {
        $this->handleAssets('style');
    }

    /**
     * Add menu item URLs to prefetch list.
     */
    function getPrefetchListMenuItems(): void
    {
        if ($menus = wp_get_nav_menus()) {
            $manifestKey = 'menu';
            if (!array_key_exists($manifestKey, $this->manifestData)) {
                $this->manifestData[$manifestKey] = [];
            }
            foreach ($menus as $menu) {
                $items = wp_get_nav_menu_items($menu);
                foreach ($items as $item) {
                    $key = $item->guid ?: $item->id;
                    $this->manifestData[$manifestKey][$key] = [
                        'priority' => 1,
                        'handle' => $key,
                        'src' => $item->url,
                    ];
                }
            }
        }
    }

    /**
     * Handle assets - works for both scripts and styles.
     *
     * @param string $type
     */
    private function handleAssets(string $type)
    {
        $wpAssetsArray = $this->getWpAssetsArray($type);

        foreach ($wpAssetsArray->queue as $handle) {
            if ($dependencies = $this->resolveDependenciesFromHandle($handle, $type)) {
                foreach ($dependencies as $dependencyHandle) {
                    $this->startOrIncrementDependencyCount($type, $dependencyHandle);
                }
            }
            $this->addToManifestData($handle, $type);
        }

        if (isset($this->manifestDependencies[$type]) && is_array($this->manifestDependencies[$type])) {
            foreach ($this->manifestDependencies[$type] as $handle => $count) {
                if ($dependencies = $this->resolveDependenciesFromHandle($handle, $type)) {
                    foreach ($dependencies as $dependencyHandle) {
                        $this->startOrIncrementDependencyCount($type, $dependencyHandle);
                    }
                }
            }
        }

        if (isset($this->manifestDependencies[$type]) && is_array($this->manifestDependencies[$type])) {
            foreach ($this->manifestDependencies[$type] as $handle => $count) {
                if ($wpAssetsArray->registered[$handle]->src) {
                    $this->addToManifestData($handle, $type, $count);
                }
            }
        }
    }

    /**
     * Get WP assets array conditionally for scripts or styles.
     *
     * @param string $type
     * @return null|object
     */
    private function getWpAssetsArray(string $type): ?object
    {
        switch ($type) {
            case 'script':
                return $GLOBALS['wp_scripts'];
            case 'style':
                return $GLOBALS['wp_styles'];
        }
        return null;
    }

    /**
     * Add asset to manifest data array.
     *
     * @param string $handle
     * @param string $type
     * @param int|null $priority
     */
    private function addToManifestData(string $handle, string $type = 'script', ?int $priority = 1): void
    {
        if (!array_key_exists($type, $this->manifestData)) {
            $this->manifestData[$type] = [];
        }
        $wpAssetsArray = $this->getWpAssetsArray($type);
        $registeredAssets = $wpAssetsArray->registered;
        $this->manifestData[$type][$handle] = $this->buildArrayItemFromDep(compact('handle', 'priority'), $registeredAssets[$handle]);
    }

    /**
     * Resolve script/style dependencies by handle.
     *
     * @param string $handle
     * @param string $type
     * @return array|null
     */
    private function resolveDependenciesFromHandle(string $handle, string $type): ?array
    {
        $wpAssetsArray = $this->getWpAssetsArray($type);
        if (
            isset($wpAssetsArray->registered[$handle])
            && isset($wpAssetsArray->registered[$handle]->deps)
            && is_array($wpAssetsArray->registered[$handle]->deps)
        ) {
            return $wpAssetsArray->registered[$handle]->deps;
        }
        return null;
    }

    /**
     * Build prefetch item from script/style.
     *
     * @param $additional
     * @param $dep
     * @return array
     */
    private function buildArrayItemFromDep($additional, $dep): array
    {
        return array_merge($additional, [
            'src' => $dep->src,
            'ver' => $dep->ver,
            'deps' => $dep->deps,
        ]);
    }

    /**
     * Check whether we should use WP Cron to record prefetch items and write manifest files.
     *
     * @return bool
     */
    public static function shouldUseCron(): bool
    {
        return apply_filters('sb_optimizer_prefetching_use_cron', false);
    }

    /**
     * Write manifest file data.
     */
    public function generateManifestFilesData(): void
    {
        // Write the data to option (so that it can be written to the files at a later point)
        ManifestDataModel::store($this->manifestData);

        // Write content to files
        ManifestFileWriter::write();
    }

    /**
     * Check whether the request is intending to update/record the prefetch items.
     *
     * @return bool
     */
    protected static function isRecordPrefetchItemsRequest(): bool
    {
        return array_key_exists('record-prefetch-items', $_GET);
    }

    /**
     * Check whether the request is a Cloudflare manifest file refresh-request.
     *
     * @return bool
     */
    protected static function isCloudflareManifestFilesRefreshRequest(): bool
    {
        return array_key_exists('cloudflare-manifest-files-refresh', $_GET);
    }

    /**
     * Create count array or increment value if exists.
     *
     * @param string $type
     * @param string $dependency
     */
    private function startOrIncrementDependencyCount(string $type, string $dependency): void
    {
        if (!array_key_exists($type, $this->manifestDependencies)) {
            $this->manifestDependencies[$type] = [];
        }
        if (array_key_exists($dependency, $this->manifestDependencies[$type])) {
            $this->manifestDependencies[$type][$dependency]++;
        } else {
            $this->manifestDependencies[$type][$dependency] = 1;
        }
    }

    /**
     * Whether we should expose manifest file after prefetch items record.
     *
     * @return bool
     */
    public static function shouldExposeManifestFilesAfterPrefetchItemsRecord(): bool
    {
        return (bool) apply_filters(
            'sb_optimizer_prefetching_expose_manifest_files_after_prefetch_items_record',
            true
        );
    }

    /**
     * Record prefetch items by loading the front page, followed by another request to trigger manifest file update in Cloudflare.
     *
     * @return void
     */
    public static function recordPrefetchItemsAndExposeManifestFiles(): void
    {
        self::clearDataAndFiles(); // Clear all previous data
        self::recordPrefetchItems(); // Record new data
        if (self::shouldExposeManifestFilesAfterPrefetchItemsRecord()) {
            self::exposeManifestFiles(); // Attempt to expose the new data to Cloudflare
        }
    }

    /**
     * Record prefetch items by loading the front page.
     */
    public static function recordPrefetchItems(): void
    {
        if (!self::$alreadyRecorded) {
            self::loadFrontPage(); // Record prefetch items
            self::$alreadyRecorded = true;
        }
    }

    /**
     * Expose manifest files to Cloudflare by loading the front page.
     */
    public static function exposeManifestFiles(): void
    {
        if (!self::$alreadyRefreshed) {
            self::refreshCloudflareManifest(); // Expose manifest files to Cloudflare
            self::$alreadyRefreshed = true;
        }
    }

    /**
     * Get the full URL for the front page loading that will expose the manifest files to Cloudflare.
     *
     * @return string
     */
    public static function getCloudflareRefreshUrlWithParameters(): string
    {
        $path = '?cachebuster=' . time() . '&cloudflare-manifest-files-refresh';
        return self::getFrontPageUrl($path);
    }

    /**
     * Load the front page for the purposes of exposing the updated manifest files to Cloudflare.
     */
    private static function refreshCloudflareManifest(): void
    {
        if ($frontPageUrl = self::getCloudflareRefreshUrlWithParameters()) {
            self::sendRequest($frontPageUrl);
        }
    }

    /**
     * Get the full URL for the front page loading.
     *
     * @param bool $redirect
     * @return string
     */
    public static function getFrontPageUrlWithParameters($redirect = false): string
    {
        $path = '?cachebuster=' . time() . '&record-prefetch-items';
        if ($redirect) {
            $path .= '&redirect=true';
        }
        return self::getFrontPageUrl($path);
    }

    /**
     * Load the front page for the purposes of record the prefetch items.
     *
     * @return void
     */
    private static function loadFrontPage(): void
    {
        if ($frontPageUrl = self::getFrontPageUrlWithParameters()) {
            self::sendRequest($frontPageUrl);
        }
    }

    /**
     * Get front page URL with including parameters.
     *
     * @param $path
     * @return mixed|void
     */
    private static function getFrontPageUrl($path)
    {
        return apply_filters('sb_optimizer_prefetching_site_url', get_site_url(null, $path), $path);
    }

    /**
     * Send GET-request.
     *
     * @param string $url
     * @return void
     */
    private static function sendRequest($url)
    {
        try {
            (new WP_Http)->request($url, [
                'timeout' => 10,
                'sslverify' => false,
            ]);
            return true;
        } catch (Exception $e) {}
        return false;
    }

    /**
     * Check if we should generate manifest data.
     *
     * @return bool
     */
    public static function shouldGenerateManifestData(): bool
    {
        /**
         * @param bool $shouldGenerateManifestData Whether we should generate manifest data during this request.
         * @param bool $isRecordPrefetchItemsRequest
         * @param bool $isCloudflareManifestFilesRefreshRequest
         */
        return (bool) apply_filters(
            'sb_optimizer_prefetching_should_generate_manifest_data',
            (
                self::isRecordPrefetchItemsRequest()
                && !self::isCloudflareManifestFilesRefreshRequest()
            ),
            self::isRecordPrefetchItemsRequest(),
            self::isCloudflareManifestFilesRefreshRequest()
        );
    }

    /**
     * Clean the current manifest data, optionally only for one type of manifest file.
     *
     * @param $type
     * @return void
     */
    public static function clearDataAndFiles($type = null): void
    {
        if ($type) {
            ManifestDataModel::clear($type); // Delete menu data from prefetch items/manifest data
            ManifestFileWriter::clear($type); // Delete menu manifest file from disk
            ManifestFileWriter::removeFromWrittenFiles($type); // Delete menu manifest file from file model
        } else {
            ManifestDataModel::clear(); // Delete prefetch items data
            ManifestFilesModel::clear(); // Delete manifest file index
            ManifestFileWriter::clear(); // Delete manifest files
        }
    }
}
