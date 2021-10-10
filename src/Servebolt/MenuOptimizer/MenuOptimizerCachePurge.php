<?php

namespace Servebolt\Optimizer\MenuOptimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\listenForOptionChange;

/**
 * Class MenuOptimizerCachePurge
 * @package Servebolt\Optimizer\MenuOptimizer
 */
class MenuOptimizerCachePurge
{
    use SharedMethods;

    /**
     * @var bool State to prevent cache to be purged multiple times during a request.
     */
    private static $hasPurgedCache = false;

    /**
     * MenuOptimizerCachePurge init.
     */
    public static function init(): void
    {
        if (self::shouldPurgeOnFrontPageChange()) {
            listenForOptionChange('show_on_front', __CLASS__ . '::purgeCacheOnFrontPageChange', false);
            listenForOptionChange('page_on_front', __CLASS__ . '::purgeCacheOnFrontPageChange', false, false);
        }
        if (self::shouldAutoPurgeCachedMenus()) {
            add_action('wp_update_nav_menu', __CLASS__ . '::purgeMenuCache', 10, 1);
            add_action('wp_delete_nav_menu', __CLASS__ . '::purgeMenuCache', 10, 1);
            add_filter('pre_set_theme_mod_nav_menu_locations', __CLASS__ . '::purgeMenuCacheOnDisplayLocationChange', 10, 2);
        }
    }

    /**
     * Purge cache whenever the front page is changed.
     *
     * @param $newValue
     * @param $oldValue
     * @param $option
     */
    public static function purgeCacheOnFrontPageChange($newValue, $oldValue, $option): void
    {
        if (!self::$hasPurgedCache) {
            MenuOptimizer::purgeCache();
            self::$hasPurgedCache = true;
        }

    }

    /**
     * Whether to purge cache automatically whenever the front page changes.
     *
     * @return bool
     */
    public static function shouldPurgeOnFrontPageChange(): bool
    {
        return (bool) apply_filters('sb_optimizer_menu_optimizer_automatic_purge_on_front_page_enabled', WpMenuOptimizer::automaticCachePurgeOnFrontPageSettingsUpdate());
    }

    /**
     * Purge menu cache whenever it is not active at any theme locations anymore.
     *
     * @param array $value
     * @param array $oldValue
     * @return mixed
     */
    public static function purgeMenuCacheOnDisplayLocationChange($value, $oldValue)
    {
        $activeMenus = array_values($value);
        $oldMenus = array_values($oldValue);
        foreach ($oldMenus as $menuId) {
            // Purge cache if the menu is not set as active at any location
            if (!in_array($menuId, $activeMenus)) {
                self::purgeMenuCache($menuId);
            }
        }
        return $value;
    }

    /**
     * Check if we should purge cache for cached menus automatically.
     *
     * @return mixed|void
     */
    private static function shouldAutoPurgeCachedMenus()
    {
        return apply_filters('sb_optimizer_menu_optimizer_automatic_purge_enabled', WpMenuOptimizer::automaticCachePurgeOnMenuChange());
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
