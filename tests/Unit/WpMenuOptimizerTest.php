<?php

namespace Unit;

use ServeboltWPUnitTestCase;
use Servebolt\Optimizer\MenuOptimizer\MenuOptimizer;

/**
 * Class WpMenuOptimizerTest
 * @package Unit
 */
class WpMenuOptimizerTest extends ServeboltWPUnitTestCase
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
        $menuCacheMessage = MenuOptimizer::menuCacheMessage();
        $this->assertNotContains($menuCacheMessage, $menu);
        MenuOptimizer::init();
        wp_nav_menu($menuArguments); // Load it once to warm the cache
        $menu = wp_nav_menu($menuArguments);
        $this->assertContains($menuCacheMessage, $menu);
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
