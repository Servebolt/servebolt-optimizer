<?php

namespace Servebolt\Optimizer\MenuOptimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class MenuOptimizerCachePurge
 * @package Servebolt\Optimizer\MenuOptimizer
 */
class MenuOptimizerCachePurge
{
    use SharedMethods;

    /**
     * MenuOptimizerCachePurge init.
     */
    public static function init(): void
    {
        if (self::shouldAutoPurgeCachedMenus()) {
            add_action('wp_update_nav_menu', __CLASS__ . '::purgeMenuCache', 10, 1);
            add_action('wp_delete_nav_menu', __CLASS__ . '::purgeMenuCache', 10, 1);
            add_filter('pre_set_theme_mod_nav_menu_locations', __CLASS__ . '::purgeMenuCacheOnDisplayLocationChange', 10, 2);
        }
    }

    /**
     * Purge menu cache whenever it is not active at any theme locations anymore.
     *
     * @param $new
     * @param $old
     * @return mixed
     */
    public static function purgeMenuCacheOnDisplayLocationChange($new, $old)
    {
        $activeMenus = array_values($new);
        $oldMenus = array_values($old);
        foreach ($oldMenus as $menuId) {
            // Purge cache if the menu is not set as active at any location
            if (!in_array($menuId, $activeMenus)) {
                self::purgeMenuCache($menuId);
            }
        }
        return $new;
    }

    /**
     * Check if we should purge cache for cached menus automatically.
     *
     * @return mixed|void
     */
    private static function shouldAutoPurgeCachedMenus()
    {
        return apply_filters('sb_optimizer_menu_optimizer_automatic_purge_enabled', true);
    }

    /**
     * Purge menu cache.
     *
     * @param int $menuId
     */
    public static function purgeMenuCache($menuId)
    {
        $menuSignatureIndex = self::getMenuSignatureIndex($menuId);
        self::deleteMenuSignatureIndex($menuId);
        if (empty($menuSignatureIndex)) {
            return;
        }
        foreach ($menuSignatureIndex as $menuSignature) {
            delete_transient(self::menuCacheTransientKey($menuSignature));
        }
    }
}
