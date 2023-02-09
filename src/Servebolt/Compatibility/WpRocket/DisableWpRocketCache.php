<?php

namespace Servebolt\Optimizer\Compatibility\WpRocket;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

//use Servebolt\Optimizer\AcceleratedDomains\AcceleratedDomains;
use Servebolt\Optimizer\FullPageCache\FullPageCacheSettings;
use function Servebolt\Optimizer\Helpers\wpDirectFilesystem;

/**
 * Class DisableWpRocketCache
 * @package Servebolt\Optimizer\Compatibility\WpRocket
 */
class DisableWpRocketCache
{
    /**
     * DisableWpRocketCache constructor.
     */
    public function __construct()
    {
        if ($this->shouldDisableCache()) {
            // Disable WP Rocket cache
            add_filter('do_rocket_generate_caching_files', '__return_false');
            add_filter('rocket_cache_mandatory_cookies', '__return_empty_array');
            add_filter('rocket_display_varnish_options_tab', '__return_false');
            add_filter('rocket_set_wp_cache_constant', '__return_false');
            add_filter('rocket_generate_advanced_cache_file', '__return_false');
            add_filter('rocket_disable_htaccess', '__return_true');
        }

        // Clear WP Rocket cache every time we enable ACD or HTML Cache (formerly FPC / Full Page Cache)
        add_action('sb_optimizer_html_cache_enable', [$this, 'wpRocketClearAllCache']);
    }

    /**
     * Check whether we should disable WP Rocket cache.
     *
     * @return bool
     */
    private function shouldDisableCache(): bool
    {
        //return AcceleratedDomains::isActive();
        return FullPageCacheSettings::htmlCacheIsActive();
    }

    /**
     * Clear all cache in WP Rocket (given that WP Rocket is present).
     *
     * @return bool
     */
    public function wpRocketClearAllCache(): bool
    {
        // Delete the cache folder completely
        /*
        if ($this->deleteWpRocketCacheFolder()) {
            return true;
        }
        */
        if (function_exists('rocket_clean_domain')) {
            // Purge all WP Rocket cache for current domain
            $purgeResult = rocket_clean_domain();
            if (is_bool($purgeResult)) {
                return $purgeResult;
            }
            return true; // We don't really know how the purge went, so unfortunately we have to assume that it went okay
        }
        return false;
    }

    /**
     * Delete the WP Rocket cache folders.
     *
     * @return bool
     */
    private function deleteWpRocketCacheFolder(): bool
    {
        if (!apply_filters('sb_optimizer_wp_rocket_compatibility_should_delete_cache_folders', true)) {
            return false;
        }
        $foldersToDelete = apply_filters('sb_optimizer_wp_rocket_compatibility_cache_folders', [
            'WP_ROCKET_CACHE_PATH',
            'WP_ROCKET_MINIFY_CACHE_PATH',
            'WP_ROCKET_CACHE_BUSTING_PATH',
            'WP_ROCKET_CRITICAL_CSS_PATH',
        ]);
        if (!$filesystem = wpDirectFilesystem()) {
            return false;
        }
        $allFoldersDeleted = true;
        foreach ($foldersToDelete as $folderToDelete) {
            if (!defined($folderToDelete)) {
                continue;
            }
            $folderPath = constant($folderToDelete);
            if (!$filesystem->exists($folderPath)) {
                continue;
            }
            if (!$filesystem->delete($folderPath, true)) {
                $allFoldersDeleted = false;
            }
        }
        return $allFoldersDeleted;
    }
}
