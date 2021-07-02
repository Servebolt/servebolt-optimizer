<?php

namespace Servebolt\Optimizer\MenuCache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\isDevDebug;
use function Servebolt\Optimizer\Helpers\isFrontEnd;

/**
 * Class MenuCache
 * @package Servebolt\Optimizer\MenuCache
 */
class MenuCache
{

    /**
     * Static property for storing navMenu arguments during caching.
     *
     * @var null|object
     */
    private static $navMenuArgs = null;

    /**
     * Static property to store transient key for menu markup.
     *
     * @var null
     */
    private static $menuMarkupTransientKey = null;

    /**
     * The version key used in transient keys so we can easily bust all cache.
     *
     * @var int
     */
    private static $transientVersion = 1111;

    /**
     * Whether to return the cached version of menus (should always be true, unless you're debugging).
     *
     * @var bool
     */
    private static $returnCachedMenu = true;

    /**
     * The TTL for the menu signature index transient.
     *
     * @var int
     */
    private static $menuSignatureIndexCacheExpirationTime = 0;

    /**
     * The TTL for the menu markup transient.
     *
     * @var int
     */
    private static $menuMarkupCacheExpirationTime = 0;

    /**
     * MenuCache constructor.
     */
    public static function init()
    {
        add_action('init', function() {
            if (self::shouldReturnCachedMenu()) {
                self::returnCachedMenuIfCached();
            }
        });
        add_action('admin_init', function() {
            if (apply_filters('sb_optimizer_menu_cache_automatic_purge_enabled', true)) {
                add_action('wp_update_nav_menu', __CLASS__ . '::purgeMenuCache', 10, 2);
            }
        });
    }

    /**
     * Check whether we should return the cached menu.
     *
     * @return bool
     */
    private static function shouldReturnCachedMenu(): bool
    {
        if (isFrontEnd()) {
            if (apply_filters('sb_optimizer_menu_cache_disabled_for_unauthenticated_users', false)) {
                return !is_user_logged_in();
            }
            return true;
        }
        return false;
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
        $cachedVersions = self::getMenuSignatureIndex($menu->term_id);
        if (empty($cachedVersions)) {
            return;
        }
        foreach ($cachedVersions as $transientKey) {
            delete_transient($transientKey);
        }
        self::setMenuSignatureIndex($menu->term_id, []);
    }

    /**
     * Start the caching process.
     */
    public static function returnCachedMenuIfCached(): void
    {
        self::preWpNavMenuOn();
    }

    /**
     * Return the cached output.
     *
     * @param $output
     * @return mixed|string
     */
    private static function returnCachedOutput($output)
    {
        if (apply_filters('sb_optimizer_menu_cache_print_cached_comment', false)) {
            if (isDevDebug()) {
                $output .= '<h1>This menu is cached</h1>' . PHP_EOL;
            }
            $output .= '<!-- This menu is cached by Servebolt Optimizer -->' . PHP_EOL;
        }
        return $output;
    }

    /**
     * Return the newly cached output.
     *
     * @param $output
     * @return mixed|string
     */
    private static function returnNewlyCachedOutput($output)
    {
        if (apply_filters('sb_optimizer_menu_cache_print_cached_comment', false)) {
            if (isDevDebug()) {
                $output .= '<h1>This menu was just cached</h1>' . PHP_EOL;
            }
            $output .= '<!-- This menu was just cached by Servebolt Optimizer -->' . PHP_EOL;
        }
        return $output;
    }

    /**
     * Hook in early in the menu loading and see if we got a cached version.
     *
     * @param string|null $output
     * @param object $args
     * @return string|null
     */
    public static function preWpNavMenu(?string $output, object $args): ?string
    {
        if ($cachedOutput = self::getCachedMenu($args)) {
            return $cachedOutput;
        }
        return $output;
    }

    /**
     * Get cached menu, and it not cached then we'll cache and return it.
     *
     * @param object $args
     * @return false|string|void
     */
    private static function getCachedMenu(object $args)
    {
        self::$menuMarkupTransientKey = self::getTransientKeyByArgs($args);
        if (self::$returnCachedMenu) {
            $cachedMenuOutput = get_transient(self::$menuMarkupTransientKey);
            if ($cachedMenuOutput) {
                return self::returnCachedOutput($cachedMenuOutput);
            }
        }
        return self::cacheAndReturnMenu($args);
    }

    /**
     * Cache and return menu markup.
     *
     * @param $args
     * @return mixed|string
     */
    private function cacheAndReturnMenu($args)
    {
        self::preWpNavMenuOff();
        $ourArgs = clone $args;
        $ourArgs->echo = false;
        self::wpNavMenuOn();
        $output = wp_nav_menu($ourArgs);
        $navMenuArgs = self::getNavMenuArgs();
        self::addMenuSignatureToMenuSignatureIndex(self::$menuMarkupTransientKey, $navMenuArgs);
        set_transient(self::$menuMarkupTransientKey, $output);
        self::preWpNavMenuOn();
        return self::returnNewlyCachedOutput($output);
    }

    /**
     * Get menu signature index transient key.
     *
     * @param int $menuId
     * @return string
     */
    private static function menuSignatureTransientKey(int $menuId): string
    {
        return 'sb-menu-cache-menu-id-' . $menuId . '-v' . self::$transientVersion;
    }

    /**
     * Get menu signature index by menu.
     *
     * @param int $menuId
     * @return array
     */
    private static function getMenuSignatureIndex(int $menuId): array
    {
        $transientKey = self::menuSignatureTransientKey($menuId);
        $cachedVersions = get_transient($transientKey);
        return $cachedVersions === false ? [] : json_decode($cachedVersions);
    }

    /**
     * Set menu signature index for menu.
     *
     * @param int $menuId
     * @param array $cachedVersions
     */
    private static function setMenuSignatureIndex(int $menuId, array $cachedVersions): void
    {
        set_transient(
            self::menuSignatureTransientKey($menuId),
            wp_json_encode($cachedVersions),
            self::getMenuSignatureIndexCacheExpirationTime()
        );
    }

    /**
     * Add menu signature to menu signature index.
     *
     * @param string $menuSignature
     * @param object $args
     */
    private static function addMenuSignatureToMenuSignatureIndex(string $menuSignature, object $args): void
    {
        $cachedVersions = self::getMenuSignatureIndex($args->menu->term_id);
        if (!in_array($menuSignature, $cachedVersions, true)) {
            $cachedVersions[] = $menuSignature;
        }
        self::setMenuSignatureIndex($args->menu->term_id, $cachedVersions);
    }

    /**
     * Get transient key for storing menu markup.
     *
     * @param string $menuSignature
     * @return string
     */
    private static function getTransientKey(string $menuSignature): string
    {
        return 'sb-menu-cache-' . self::$transientVersion . '-' . $menuSignature;
    }

    /**
     * Get transient key by menu orguments for storing menu markup.
     *
     * @param object $args
     * @return string
     */
    private static function getTransientKeyByArgs(object $args): string
    {
        return self::getTransientKey(self::getMenuSignature($args));
    }

    /**
     * Get menu signature.
     *
     * @param object $args
     * @return string
     */
    private static function getMenuSignature(object $args): string
    {
        global $wp_query;
        return md5(wp_json_encode($args) . $wp_query->query_vars_hash);
    }

    /**
     * Get the expiration in seconds for storing the menu signature index.
     *
     * @return int
     */
    private static function getMenuSignatureIndexCacheExpirationTime(): int
    {
        return apply_filters('sb_optimizer_menu_cache_menu_signature_index_expiration_time', self::$menuSignatureIndexCacheExpirationTime);
    }

    /**
     * Get the expiration in seconds for storing the menu markup.
     *
     * @return int
     */
    private static function getMenuMarkupCacheExpirationTime(): int
    {
        return apply_filters('sb_optimizer_menu_cache_menu_markup_expiration_time', self::$menuMarkupCacheExpirationTime);
    }

    /**
     * Register menu cache hook.
     */
    private static function wpNavMenuOn(): void
    {
        add_filter('wp_nav_menu', __CLASS__ . '::recordNavMenuArgs', 10, 2);
    }

    /**
     * Get result from nav menu object "recording" during menu caching.
     *
     * @return object|null
     */
    private static function getNavMenuArgs()
    {
        self::wpNavMenuOff();
        $navMenuArgs = self::$navMenuArgs;
        self::$navMenuArgs = null;
        return $navMenuArgs;
    }

    /**
     * "Record" nav menu object during menu caching.
     *
     * @param string|null $navMenu
     * @param object $args
     * @return string|null
     */
    public static function recordNavMenuArgs(?string $navMenu, object $args)
    {
        self::$navMenuArgs = $args;
        return $navMenu;
    }

    /**
     * De-register menu cache hook.
     */
    private static function wpNavMenuOff(): void
    {
        remove_filter('wp_nav_menu', __CLASS__ . '::recordNavMenuArgs', 10, 2);
    }

    /**
     * Register menu cache hook.
     */
    private static function preWpNavMenuOn(): void
    {
        add_filter('pre_wp_nav_menu', __CLASS__ . '::preWpNavMenu', 10, 2);
    }

    /**
     * De-register menu cache hook.
     */
    private static function preWpNavMenuOff(): void
    {
        remove_all_filters('pre_wp_nav_menu');
        //remove_filter('pre_wp_nav_menu', __CLASS__ . '::preWpNavMenu', 10, 2);
    }
}
