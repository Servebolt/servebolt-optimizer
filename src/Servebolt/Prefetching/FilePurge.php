<?php

namespace Servebolt\Optimizer\Prefetching;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class FilePurge
 * @package Servebolt\Optimizer\Prefetching
 */
class FilePurge
{
    /**
     * FilePurge constructor.
     */
    public function __construct()
    {
        $this->handleMenuUpdate();
    }

    private function handleMenuUpdate()
    {
        add_action('wp_update_nav_menu', __CLASS__ . '::purgeMenuCache', 10, 0);
        add_action('wp_delete_nav_menu', __CLASS__ . '::purgeMenuCache', 10, 0);
        add_filter('pre_set_theme_mod_nav_menu_locations', __CLASS__ . '::purgeMenuCacheOnDisplayLocationChange', 10, 0);
    }

    public static function purgeMenuCacheOnDisplayLocationChange()
    {
        self::removeMenuManifestFile();
    }

    public static function purgeMenuCache()
    {
        self::removeMenuManifestFile();
    }

    private static function removeMenuManifestFile()
    {
        ManifestFileWriter::clear('menu');
        ManifestFileWriter::removeFromWrittenFiles('menu');
    }
}
