<?php

namespace Unit;

use ServeboltWPUnitTestCase;
use Servebolt\Optimizer\MenuCache\MenuCache;

/**
 * Class WpAssetTest
 * @package Unit
 */
class WpMenuCacheTest extends ServeboltWPUnitTestCase
{
    public function testThatMenuGetsCached(): void
    {
        $themeLocation = 'primary';
        $this->createDummyMenu($themeLocation);
        $menuArguments = [
            'theme_location' => $themeLocation,
            'menu_class' => 'menu-wrapper',
            'container_class' => 'primary-menu-container',
            'items_wrap' => '<ul id="primary-menu-list" class="%2$s">%3$s</ul>',
            'fallback_cb' => false,
            'echo' => false,
        ];
        $menu = wp_nav_menu($menuArguments);
        $this->assertNotContains('cache', $menu);
        add_filter('sb_optimizer_is_dev_debug', '__return_true');
        add_filter('sb_optimizer_menu_cache_print_cached_comment', '__return_true');
        MenuCache::init();
        $menu = wp_nav_menu($menuArguments);
        $this->assertContains('cache', $menu);
    }

    private function createDummyMenu(?string $themeLocation = null): void
    {
        $menuName = 'Main menu - ' . uniqid();
        $menuId = wp_create_nav_menu($menuName);
        for ($i = 1; $i <= 10; $i++) {
            wp_update_nav_menu_item($menuId, 0, [
                'menu-item-title' =>  'Menu item ' . $i,
                'menu-item-classes' => 'cm-item-' . $i,
                'menu-item-url' => home_url( '/' ),
                'menu-item-status' => 'publish'
            ]);
        }
        if ($themeLocation) {
            $locations = get_theme_mod('nav_menu_locations');
            $locations['primary'] = $menuId;
            set_theme_mod('nav_menu_locations', $locations);
        }
    }
}
