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
        add_filter('pre_set_theme_mod_nav_menu_locations', [$this, 'removeMenuManifestFile'], 10, 0);
    }

    /**
     * Purge menu manifest file.
     */
    public function removeMenuManifestFile(): void
    {
        ManifestFileWriter::clear('menu');
        ManifestFileWriter::removeFromWrittenFiles('menu');
    }
}