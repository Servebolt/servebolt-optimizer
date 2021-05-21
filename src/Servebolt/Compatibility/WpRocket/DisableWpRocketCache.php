<?php

namespace Servebolt\Optimizer\Compatibility\WpRocket;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

//use Servebolt\Optimizer\AcceleratedDomains\AcceleratedDomains;
use Servebolt\Optimizer\FullPageCache\FullPageCacheSettings;

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
        }

        // Clear WP Rocket cache every time we enable FPC / ACD
        add_action('sb_optimizer_fpc_enable', [$this, 'wpRocketClearAllCache']);
    }

    /**
     * Check whether we should disable WP Rocket cache.
     *
     * @return bool
     */
    private function shouldDisableCache(): bool
    {
        //return AcceleratedDomains::isActive();
        return FullPageCacheSettings::fpcIsActive();
    }

    /**
     * Clear all cache in WP Rocket (given that WP Rocket is present).
     *
     * @return bool
     */
    public function wpRocketClearAllCache(): bool
    {
        // Delete the cache folder completely
        if ($this->deleteWpRocketCacheFolder()) {
            return true;
        }
        if (function_exists('rocket_clean_domain')) {
            // Purge all WP Rocket cache for current domain
            return rocket_clean_domain();
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
        if (!$filesystem = $this->wpDirectFilesystem()) {
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

    /**
     * Instanciate the filesystem class
     *
     * @return object WP_Filesystem_Direct instance
     */
    private function wpDirectFilesystem(): object
    {
        require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
        return new \WP_Filesystem_Direct(new \StdClass());
    }
}
