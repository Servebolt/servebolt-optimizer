<?php

namespace Servebolt\Optimizer\MenuCache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class MenuCachePurge
 * @package Servebolt\Optimizer\MenuCache
 */
class MenuCachePurge
{
    use SharedMethods;

    /**
     * MenuCachePurge init.
     */
    public static function init(): void
    {
        if (self::shouldAutoPurgeCachedMenus()) {
            add_action('wp_update_nav_menu', __CLASS__ . '::purgeMenuCache', 10, 2);
        }
    }

    /**
     * Check if we should purge cache for cached menus automatically.
     *
     * @return mixed|void
     */
    private static function shouldAutoPurgeCachedMenus()
    {
        return apply_filters('sb_optimizer_menu_cache_automatic_purge_enabled', true);
    }

    /**
     * Purge menu cache.
     *
     * @param int $menuId
     * @param array|null $menuData
     */
    public static function purgeMenuCache(int $menuId, ?array $menuData = null)
    {
        if (!is_array($menuData) || !isset($menuData['menu-name'])) {
            return;
        }
        $menu = wp_get_nav_menu_object($menuData['menu-name']);
        if (!isset($menu->term_id)) {
            return;
        }
        $menuSignatureIndex = self::getMenuSignatureIndex($menu->term_id);
        if (empty($menuSignatureIndex)) {
            return;
        }
        foreach ($menuSignatureIndex as $menuSignature) {
            delete_transient(self::menuCacheTransientKey($menuSignature));
        }
        self::setMenuSignatureIndex([], $menu->term_id);
    }
}
