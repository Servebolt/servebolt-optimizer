<?php

namespace Servebolt\Optimizer\AssetPrefetching;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class AssetPrefetching
 * @package Servebolt\Optimizer\AssetPrefetching
 */
class AssetPrefetching
{
    /**
     * @var array
     */
    private $manifestFiles = [];

    /**
     * @var array
     */
    private $manifestDeps = [];

    /**
     * @var array
     */
    private $prefetchListStylesCount = [];

    /**
     * @var array
     */
    private $prefetchListScriptsCount = [];

    /**
     * Key used to store transient.
     *
     * @var string
     */
    private $transientKey = 'servebolt_manifest_written_test'; // Change to "servebolt_manifest_written" in production

    /**
     * Expiration in seconds for transient.
     *
     * @var float|int
     */
    private $transientExpiration = MONTH_IN_SECONDS;

    /**
     * Boolean value for whether we have already stored the manifest data.
     *
     * @var null|bool
     */
    private $hasManifest = null;

    /**
     *
     */
    public function prefetchListScripts(): void
    {
        if (!$this->hasManifest()) {
            global $wp_scripts;

            // Loop through enqueued scripts
            foreach ($wp_scripts->queue as $handle) {

                // Resolve dependencies from enqueued script
                if ($dependencies = $this->resolveDependenciesFromEnqueueScript($handle)) {
                    foreach ($dependencies as $dependency) {
                        // Add script count to manifest dependencies
                        $this->manifestDeps['script'][$handle] = $this->startOrIncrementDependencyCount($this->prefetchListScriptsCount, $dependency);
                    }
                }

                // Add enqueued script to manifest file
                $this->manifestFiles['script'][$handle] = $this->buildArrayItemFromDep([
                    'handle' => $handle,
                    'priority' => 1,
                ], $wp_scripts->registered[$handle]);
            }

            // Look through dependencies already in manifest
            foreach ($this->manifestDeps['script'] as $handle => $count) {
                // Check if the dependency has dependencies
                if ($wp_scripts->registered[$handle]->deps) {
                    foreach ($wp_scripts->registered[$handle]->deps as $dependency) {
                        $this->manifestDeps['script'][$handle] = $this->startOrIncrementDependencyCount($this->prefetchListScriptsCount, $dependency);
                    }
                }
            }

            // Make sure we get the all the deps to the manifest global
            foreach ($this->manifestDeps['script'] as $handle => $count) {
                if ($wp_scripts->registered[$handle]->src) {
                    $this->manifestFiles['script'][$handle] = $this->buildArrayItemFromDep([
                        'handle' => $handle,
                        'priority' => $count
                    ], $wp_scripts->registered[$handle]);
                }
            }
        }
    }

    /**
     * Extract dependencies from a registered style.
     *
     * @param array $countArray
     * @param string $handle
     */
    private function extractStyleDependenciesFromHandle(array &$countArray, string $handle): void
    {
        if ($dependencies = $this->resolveDependenciesFromRegisteredStyle($handle)) {
            foreach ($dependencies as $dependency) {
                $this->manifestDeps['style'][$handle] = $this->startOrIncrementDependencyCount($countArray, $dependency);
            }
        }
    }

    /**
     *
     */
    function prefetchListStyles(): void
    {
        if (!$this->hasManifest()) {
            global $wp_styles;

            print_r($wp_styles);

            foreach ($wp_styles->queue as $handle) {
                $this->extractStyleDependenciesFromHandle($this->prefetchListStylesCount, $handle);
                $this->manifestFiles['style'][$handle] = $this->buildArrayItemFromDep([
                    'handle' => $handle,
                    'priority' => 1
                ], $wp_styles->registered[$handle]);
            }

            // Make sure we get the deps too
            foreach ($this->manifestDeps['style'] as $handle => $count) {
                // Check if the dep has deps
                if (isset($wp_styles->registered[$handle]->deps) && is_array($wp_styles->registered[$handle]->deps)) {
                    foreach ($wp_styles->registered[$handle]->deps as $dep) {
                        $this->manifestDeps['style'][$dep] = $this->startOrIncrementDependencyCount($this->prefetchListStylesCount, $dep);
                    }
                }
            }

            // Make sure we get the all the deps to the manifest global
            foreach ($this->manifestDeps['style'] as $handle => $count) {
                if ($wp_styles->registered[$handle]->src) {
                    $this->manifestFiles['style'][$handle] = $this->buildArrayItemFromDep([
                        'handle' => $handle,
                        'priority' => $count
                    ], $wp_styles->registered[$handle]);
                }
            }
        }
    }

    /**
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
     *
     */
    function writeManifestFile(): void
    {
        if (!$this->hasManifest()) {

            if (!is_dir(ABSPATH . '/acd')) {
                // dir doesn't exist, make it
                mkdir(ABSPATH . '/acd');
            }

            // If this is a multisite, make a directory for this site and get the site_id
            if (is_multisite()) {
                $site_id = get_current_site()->id;

                if (!is_dir(ABSPATH . '/acd/' . $site_id)) {
                    // dir doesn't exist, make it
                    mkdir(ABSPATH . '/acd/' . $site_id);
                }

            }

            // Loop through the manifest array
            foreach ($this->manifestFiles as $type => $files) {

                $manifest = '';
                $domain = parse_url(get_site_url());

                if (is_multisite()) {
                    $filename = ABSPATH . 'acd/acd-' . $site_id . '-' . $type . '.txt';
                } else {
                    $filename = ABSPATH . 'acd/acd-' . $type . '.txt';
                }

                // Order the files by priority. Highest priority first.
                usort($files, function ($a, $b) {
                    return $b['priority'] <=> $a['priority'];
                });

                // Loop through the files
                foreach ($files as $file) {

                    // Just continue if src is not set
                    if (!$file['src']) {
                        continue;
                    }

                    // We need the src to be a parsed URL for later
                    $url = parse_url($file['src']);

                    // Set host to current domain if it's not set
                    if (empty($url['host'])) {
                        $url['host'] = $domain['host'];
                    }

                    // We can force to HTTPS since ACD only runs on HTTPS
                    if (empty($url['scheme'])) {
                        $url['scheme'] = 'https';
                    }

                    // We only want to prefetch stuff from current domain for now
                    if (strpos($url['host'], $domain['host']) !== false) {
                        $line = '';
                        $line .= $url['scheme'] . '://' . $url['host'] . $url['path'];

                        // If a version string exists we most likely need to add that to the url
                        if ($file['ver']) {
                            $line .= '?ver=' . $file['ver'];
                        }
                        $line .= PHP_EOL;
                        $manifest .= $line;

                        // TODO: add support for sbversion string?
                    }
                }
                // Write the manifest file to file
                file_put_contents($filename, $manifest);

                // TODO: This needs some kind of security
            }

            // Set the transient and expire it in a month
            set_transient($this->transientKey, time(), $this->transientExpiration);
        }
    }

    /**
     * Add menu item URLs to prefetch list.
     */
    function prefetchListMenuItems(): void
    {
        $menus = wp_get_nav_menus();
        if (!$this->hasManifest()) {
            foreach ($menus as $menu) {
                $items = wp_get_nav_menu_items($menu);
                foreach ($items as $item) {
                    $key = $item->guid ?: $item->id;
                    $this->manifestFiles['menu'][$key] = [
                        'handle' => $key,
                        'src' => $item->url,
                        'priority' => 1
                    ];
                }
            }
        }
    }

    /**
     * Resolve dependencies from a registered script handle.
     *
     * @param string $handle
     * @return array|null
     */
    private function resolveDependenciesFromEnqueueScript(string $handle): ?array
    {
        global $wp_scripts;
        if ($wp_scripts->registered[$handle]->deps && is_array($wp_scripts->registered[$handle]->deps)) {
            return $wp_scripts->registered[$handle]->deps;
        }
        return null;
    }

    /**
     * Resolve dependencies from a registered style handle.
     *
     * @param string $handle
     * @return array|null
     */
    private function resolveDependenciesFromRegisteredStyle(string $handle): ?array
    {
        global $wp_styles;
        if (isset($wp_styles->registered[$handle]->deps) && is_array($wp_styles->registered[$handle]->deps)) {
            return $wp_styles->registered[$handle]->deps;
        }
        return null;
    }

    /**
     * Create count array or increment value if exists.
     *
     * @param $countArray
     * @param $dependency
     * @return int
     */
    private function startOrIncrementDependencyCount(&$countArray, $dependency): int
    {
        if (array_key_exists($dependency, $countArray)) {
            $countArray[$dependency] = 1;
        } else {
            $countArray[$dependency]++;
        }
        return $countArray[$dependency];
    }

    /**
     * Check if we have manifest data.
     *
     * @return bool
     */
    private function hasManifest(): bool
    {
        return false;
        if (is_null($this->hasManifest)) {
            $this->hasManifest = get_transient($this->transientKey) !== false;
        }
        return $this->hasManifest;
    }
}
