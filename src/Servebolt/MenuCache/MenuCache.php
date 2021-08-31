<?php

namespace Servebolt\Optimizer\MenuCache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\isDevDebug;

/**
 * Class MenuCache
 * @package Servebolt\Optimizer\MenuCache
 */
class MenuCache
{
    use SharedMethods;

    /**
     * MenuCache init.
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
        if (apply_filters('sb_optimizer_menu_cache_disabled_for_unauthenticated_users', false)) {
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
        self::ensureMenuObjectIsResolved($args);
        if (self::getMenuSignatureIndex($args->menu->term_id)) {
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
        if (isset($args->menu->term_id)) {
            $menuSignature = self::getMenuSignatureFromArgs($args);
            self::setMenuCache($navMenu, $menuSignature);
            self::addMenuSignatureToIndex($menuSignature, $args->menu->term_id);
        }
        return $navMenu;
    }

    /**
     * Return the cached output adding a cache "hit" indicator.
     *
     * @param $output
     * @return mixed|string
     */
    private static function returnCachedOutput($output)
    {
        if (apply_filters('sb_optimizer_menu_cache_print_cached_comment', true)) {
            if (isDevDebug()) {
                $output .= '<h3>This menu is cached by Servebolt Optimizer</h3>' . PHP_EOL; // For debugging purposes
            } else {
                $output .= '<!-- This menu is cached by Servebolt Optimizer -->' . PHP_EOL;
            }
        }
        return $output;
    }

    /**
     * Ensure that we have the menu object in the argument object.
     *
     * @param $args
     */
    private static function ensureMenuObjectIsResolved(&$args): void
    {
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

        // Fallback to catch faulty wp_nav_menu argument filtering (originating from plugin AstraPro). Jira ticket WPSO-400.
        if ( is_numeric( $args->menu ) ) {
            $args->menu = $menu;
        }
    }
}
