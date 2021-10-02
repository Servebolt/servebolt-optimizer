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
        if (apply_filters('sb_optimizer_prefetching_should_purge_on_menu_update', true)) {
            $this->purgeOnMenuUpdate();
        }
    }

    /**
     * Purge menu manifest file on menu update.
     */
    private function purgeOnMenuUpdate(): void
    {
        add_action('wp_update_nav_menu', [$this, 'removeMenuManifestFile'], 10, 0);
        add_action('wp_delete_nav_menu', [$this, 'removeMenuManifestFile'], 10, 0);
        add_filter('pre_set_theme_mod_nav_menu_locations', [$this, 'removeMenuManifestFileOnDisplayLocationChange'], 10, 1);
    }

    /**
     * Purge menu manifest file on menu display location change.
     *
     * @param array $value
     * @return array
     */
    public function removeMenuManifestFileOnDisplayLocationChange($value)
    {
        $this->removeMenuManifestFile();
        return $value;
    }

    /**
     * Purge menu manifest file.
     */
    public function removeMenuManifestFile(): void
    {
        WpPrefetching::rescheduleManifestDataGeneration(); // We've changed settings, let's regenerate the data
        WpPrefetching::recordPrefetchItems();
    }
}
