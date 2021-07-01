<?php

namespace Servebolt\Optimizer\MenuCache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\isFrontEnd;

/**
 * Class MenuCache
 * @package Servebolt\Optimizer\MenuCache
 */
class MenuCache
{

    /**
     * @var null|object
     */
    private static $navMenuArgs = null;

    /**
     * @var null
     */
    private static $transientKey = null;

    /**
     * @var int
     */
    private static $transientVersion = 5;

    /**
     * @var bool
     */
    private static $returnCachedMenu = false;

    /**
     * @var int
     */
    private static $menuIndexCacheExpirationTime = 15552000;

    /**
     * MenuCache constructor.
     */
    public static function init()
    {
        if (isFrontEnd()) {
            self::returnCachedMenuIfCached();
        }

        //add_action('wp_update_nav_menu', [__CLASS__ . '::purgeMenuCache'], 10, 2);
    }

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
        if (apply_filters('sb_optimizer_menu_cache_print_cached_comment', true)) {
            $output = '<h1>This menu is cached</h1>' . PHP_EOL . $output;
            //$output = '<!-- This menu is cached by Servebolt Optimizer -->' . PHP_EOL . $output;
        }
        return $output;
    }

    /**
     * @param string|null $output
     * @param object $args
     * @return string|null
     */
    public static function preWpNavMenu(?string $output, object $args): ?string
    {
        if ($cachedOutput = self::getCachedMenu($args)) {
            return self::returnCachedOutput($cachedOutput);
        }
        return $output;
    }

    /**
     * Get cached menu, and it not cached we will cache and return it.
     *
     * @param object $args
     * @return false|string|void
     */
    private static function getCachedMenu(object $args)
    {
        self::$transientKey = self::getTransientKey($args);
        if (self::$returnCachedMenu) {
            $cachedMenuOutput = get_transient(self::$transientKey);
            if ($cachedMenuOutput) {
                return $cachedMenuOutput;
            }
        }
        return self::cacheAndReturnMenu($args);
    }

    private function cacheAndReturnMenu($args)
    {
        self::preWpNavMenuOff();
        $ourArgs = clone $args;
        $ourArgs->echo = false;
        self::wpNavMenuOn();
        $output = wp_nav_menu($ourArgs);
        $navMenuArgs = self::getNavMenuArgs();
        // TODO: Add stuff here
        set_transient(self::$transientKey, $output);
        self::preWpNavMenuOn();
        return $output;
    }

    /**
     * @param object $args
     * @return string
     */
    private static function getTransientKey(object $args): string
    {
        return 'sb-menu-cache-' . self::$transientVersion . '-' . self::getMenuSignature($args);
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
     * Get the expiration in seconds for storing the menu markup.
     *
     * @return int
     */
    private static function getMenuCacheExpirationTime(): int
    {
        return apply_filters('sb_optimizer_menu_cache_expiration_time', self::$menuCacheExpirationTime);
    }

    /**
     * Register menu cache hook.
     */
    private static function wpNavMenuOn(): void
    {
        add_filter('wp_nav_menu', __CLASS__ . '::recordNavMenuArgs', 10, 2);
    }

    private static function getNavMenuArgs()
    {
        self::wpNavMenuOff();
        $navMenuArgs = self::$navMenuArgs;
        self::$navMenuArgs = null;
        return $navMenuArgs;
    }

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
