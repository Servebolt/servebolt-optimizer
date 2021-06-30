<?php

namespace Servebolt\Optimizer\Prefetching;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class Prefetching
 * @package Servebolt\Optimizer\Prefetching
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
     * Expiration in seconds for transient.
     *
     * @var int
     */
    private $transientExpiration = MONTH_IN_SECONDS;

    /**
     * Boolean value for whether we should generate the manifest data.
     *
     * @var null|bool
     */
    private static $shouldGenerateManifestData = null;

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
    function prefetchListMenuItems(): void
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
            $this->addToManifestData($type, $handle);
        }

        foreach ($this->manifestDependencies[$type] as $handle => $count) {
            if ($dependencies = $this->resolveDependenciesFromHandle($handle, $type)) {
                foreach ($dependencies as $dependencyHandle) {
                    $this->startOrIncrementDependencyCount($type, $dependencyHandle);
                }
            }
        }

        foreach ($this->manifestDependencies[$type] as $handle => $count) {
            if ($wpAssetsArray->registered[$handle]->src) {
                $this->addToManifestData($type, $handle, $count);
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
     * @param string $type
     * @param string $handle
     * @param int|null $priority
     */
    private function addToManifestData(string $type = 'script', string $handle, ?int $priority = 1): void
    {
        if (!array_key_exists($type, $this->manifestData)) {
            $this->manifestData[$type] = [];
        }
        $wpAssetsArray = $this->getWpAssetsArray($type);
        $registeredAssets = $wpAssetsArray->registered;
        $this->manifestData[$type][$handle] = $this->buildArrayItemFromDep(compact('handle', 'priority'), $registeredAssets[$handle]);
    }

    /**
     * Resolve script/style depedencies by handle.
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
     * Check whether we should write manifest files immediately or afterwards using single schedule event with the WP Cron.
     *
     * @return bool
     */
    private function shouldWriteFilesUsingCron(): bool
    {
        return apply_filters('sb_optimizer_prefetch_should_write_manifest_files_using_cron', true);
    }

    /**
     * Write manifest file data.
     */
    public function generateManifestFilesData(): void
    {
        // Set the transient and expire it in a month
        set_transient(self::$transientKey, time(), $this->transientExpiration);

        // Write the data to option (so that it can be written to files at a later point)
        ManifestDataModel::store($this->manifestData);

        // Write content to files
        if ($this->shouldWriteFilesUsingCron()) {
            wp_schedule_single_event(time(), [__NAMESPACE__ . '\\ManifestFileWriter', 'write']);
        } else {
            ManifestFileWriter::write();
        }
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
     * Check if we should generate manifest data.
     *
     * @return bool
     */
    public static function shouldGenerateManifestData(): bool
    {
        if (is_null(self::$shouldGenerateManifestData)) {
            self::$shouldGenerateManifestData = get_transient(self::$transientKey) === false;
        }
        return apply_filters('sb_optimizer_prefetch_should_generate_manifest_data', self::$shouldGenerateManifestData);
    }
}
