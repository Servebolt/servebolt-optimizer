<?php

namespace Servebolt\Optimizer\MenuOptimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\convertObjectToArray;
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
     * @var Object The WP_Nav menu arguments object.
     */
    private static $args;

    /**
     * MenuOptimizer init.
     */
    public static function init()
    {
        if (self::shouldRunTiming()) {
            new TimingCheck;
        }
        if (self::shouldCacheMenus()) {
            add_filter('pre_wp_nav_menu', __CLASS__ . '::preWpNavMenu', self::preWpNavMenuCallbackPriority(), 2);
            add_filter('wp_nav_menu', __CLASS__ . '::wpNavMenu', 10, 2);
        }
    }

    /**
     * Whether we should run the timing to inspect the result of the menu optimizer cache.
     *
     * @return bool
     */
    private static function shouldRunTiming(): bool
    {
        return apply_filters('sb_optimizer_menu_optimizer_run_timing', WpMenuOptimizer::automaticCacheRunTiming());
    }

    /**
     * Get the callback priority for the "pre_wp_nav_menu"-filter callback.
     * @return int
     */
    private static function preWpNavMenuCallbackPriority(): int
    {
        return PHP_INT_MAX - 1;
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
     * Hook in early in the menu loading using the "pre_wp_nav_menu"-filter and see if we got a cached version.
     *
     * @param string|null $output
     * @param object $args
     * @return string|null
     */
    public static function preWpNavMenu(?string $output, object $args): ?string
    {
        self::$args = convertObjectToArray($args);
        if (
            self::resolveMenuObject($args)
            && self::getMenuSignatureIndex(self::$menuObject->term_id)
        ) {
            $menuSignature = self::getMenuSignatureFromArgs();
            if ($cachedOutput = self::getMenuCache($menuSignature)) {
                return self::addCacheIndicatorOutput($cachedOutput);
            }
        }
        return $output;
    }

    /**
     * Hook into the "wp_nav_menu"-filter and maybe set cache.
     *
     * @param $navMenu
     * @param $args
     * @return mixed
     */
    public static function wpNavMenu($navMenu, $args)
    {
        if (self::$menuObject) {
            $menuSignature = self::getMenuSignatureFromArgs();
            self::setMenuCache($navMenu, $menuSignature);
            self::addMenuSignatureToIndex($menuSignature, self::$menuObject->term_id);
            return self::addCacheIndicatorOutput($navMenu, self::menuJustCacheMessage());
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
     * The message that flags the menu as cached or not.
     *
     * @return string
     */
    public static function menuJustCacheMessage(): string
    {
        return 'This menu was just cached by Servebolt Optimizer';
    }

    /**
     * Return the cached output adding a cache "hit" indicator.
     *
     * @param string $output
     * @param string|null $text
     * @return mixed|string
     */
    private static function addCacheIndicatorOutput($output, $text = null)
    {
        if (!$text) {
            $text = self::menuCacheMessage();
        }
        if (apply_filters('sb_optimizer_menu_optimizer_print_cached_comment', true)) {
            if (isDevDebug()) {
                $output .= '<h3>' . $text . '</h3>' . PHP_EOL; // For debugging purposes
            } else {
                $output .= '<!-- ' . $text . ' -->' . PHP_EOL;
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
    private static function resolveMenuObject($args): bool
    {
        // Reset menu object
        self::$menuObject = null;

        // This section is from the function "wp_nav_menu" in the WP core files. It is here to find a menu when none is provided.
        // wp-includes/nav-menu-template.php:125 (as of WP v5.8.1)

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

        // @codingStandardsIgnoreEnd

        if ($menu && isset($menu->term_id)) {
            self::$menuObject = $menu; // We had to resolve the menu ourselves (prior to WP doing so)
            return true;
        }

        return false;
    }

    /**
     * Purge menu optimizer cache.
     */
    public static function purgeCache(): void
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE %s', '_transient_sb-menu-cache-%'));
    }
}
