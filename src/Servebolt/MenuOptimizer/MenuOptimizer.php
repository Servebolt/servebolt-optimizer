<?php

namespace Servebolt\Optimizer\MenuOptimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\isDevDebug;

/**
 * Class MenuOptimizer
 * @package Servebolt\Optimizer\MenuOptimizer
 */
class MenuOptimizer
{
    use SharedMethods;

    /**
     * @var null|\WP_Term Property to hold menu term object.
     */
    private static $menuObject;

    /**
     * MenuOptimizer init.
     */
    public static function init()
    {
        if (self::shouldCacheMenus()) {
            add_filter('pre_wp_nav_menu', __CLASS__ . '::preWpNavMenu', 10, 2);
            add_filter('wp_nav_menu', __CLASS__ . '::wpNavMenu', 10, 2);
        }
    }

    /**
     * Check whether we should cache menus.
     *
     * @return bool
     */
    private static function shouldCacheMenus()
    {
        if (apply_filters('sb_optimizer_menu_optimizer_disabled_for_unauthenticated_users', false)) {
            return !is_user_logged_in();
        }
        return true;
    }

    /**
     * Hook in early in the menu loading using the 'pre_wp_nav_menu'-filter and see if we got a cached version.
     *
     * @param string|null $output
     * @param object $args
     * @return string|null
     */
    public static function preWpNavMenu(?string $output, object $args): ?string
    {
        if (
            self::resolveMenuObject($args)
            && self::getMenuSignatureIndex(self::$menuObject->term_id)
        ) {
            $menuSignature = self::getMenuSignatureFromArgs($args);
            if ($cachedOutput = self::getMenuCache($menuSignature)) {
                return self::returnCachedOutput($cachedOutput);
            }
        }
        return $output;
    }

    /**
     * Hook into the 'wp_nav_menu'-filter and maybe set cache.
     *
     * @param $navMenu
     * @param $args
     * @return mixed
     */
    public static function wpNavMenu($navMenu, $args)
    {
        if (self::$menuObject) {
            $menuSignature = self::getMenuSignatureFromArgs($args);
            self::setMenuCache($navMenu, $menuSignature);
            self::addMenuSignatureToIndex($menuSignature, self::$menuObject->term_id);
        }
        return $navMenu;
    }

    /**
     * The message that flags the menu as cached or not.
     *
     * @return string
     */
    public static function menuCacheMessage(): string
    {
        return 'This menu is cached by Servebolt Optimizer';
    }

    /**
     * Return the cached output adding a cache "hit" indicator.
     *
     * @param $output
     * @return mixed|string
     */
    private static function returnCachedOutput($output)
    {
        if (apply_filters('sb_optimizer_menu_optimizer_print_cached_comment', true)) {
            if (isDevDebug()) {
                $output .= '<h3>' . self::menuCacheMessage() . '</h3>' . PHP_EOL; // For debugging purposes
            } else {
                $output .= '<!-- ' . self::menuCacheMessage() . ' -->' . PHP_EOL;
            }
        }
        return $output;
    }

    /**
     * Ensure that we have the menu object in the argument object.
     *
     * @param $args
     * @return bool
     */
    private static function resolveMenuObject(&$args): bool
    {
        // Reset menu object
        self::$menuObject = null;

        /* This section is from the function "wp_nav_menu" in the WP core files. It is here to find a menu when none is provided. */

        // @codingStandardsIgnoreStart

        // Get the nav menu based on the requested menu.
        $menu = wp_get_nav_menu_object( $args->menu );

        // Get the nav menu based on the theme_location.
        $locations = get_nav_menu_locations();
        if ( ! $menu && $args->theme_location && $locations && isset( $locations[ $args->theme_location ] ) ) {
            $menu = wp_get_nav_menu_object( $locations[ $args->theme_location ] );
        }

        // Get the first menu that has items if we still can't find a menu.
        if ( ! $menu && ! $args->theme_location ) {
            $menus = wp_get_nav_menus();
            foreach ( $menus as $menu_maybe ) {
                $menu_items = wp_get_nav_menu_items( $menu_maybe->term_id, array( 'update_post_term_cache' => false ) );
                if ( $menu_items ) {
                    $menu = $menu_maybe;
                    break;
                }
            }
        }

        if ( empty( $args->menu ) ) {
            $args->menu = $menu;
        }

        // @codingStandardsIgnoreEnd

        if ($menu && isset($menu->term_id)) {
            self::$menuObject = $menu;
            return true;
        }

        return false;
    }
}
