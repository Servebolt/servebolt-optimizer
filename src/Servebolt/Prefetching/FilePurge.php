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
        if (apply_filters('sb_optimizer_prefetching_should_purge_on_theme_switch', true)) {
            $this->purgeOnThemeSwitch();
        }
    }

    /**
     * Purge menu manifest file on menu update.
     */
    private function purgeOnMenuUpdate(): void
    {
        add_action('wp_update_nav_menu', [$this, 'scheduleMenuManifestFileRegeneration'], 10, 0);
        add_action('wp_delete_nav_menu', [$this, 'scheduleMenuManifestFileRegeneration'], 10, 0);
        add_filter('pre_set_theme_mod_nav_menu_locations', [$this, 'regenerateMenuManifestFileOnDisplayLocationChange'], 10, 1);
    }

    /**
     * Purge manifest files on theme switch.
     */
    private function purgeOnThemeSwitch()
    {
        add_action('switch_theme', [$this, 'scheduleManifestFileRegeneration']);
    }

    /**
     * Schedule manifest files to be regenerated.
     */
    public function scheduleManifestFileRegeneration(): void
    {
        if (WpPrefetching::shouldWriteFilesUsingCron()) {
            WpPrefetching::scheduleRecordPrefetchItems();
        } else {
            WpPrefetching::rescheduleManifestDataGeneration(); // Delete transient so that we will record prefetch items on next page load
            ManifestFileWriter::clear(); // Delete prefetch files
            ManifestFilesModel::clear(); // Prevent prefetch files from being printed to headers
            // We're not using cron, so we need to wait until someone is visiting the site again
        }
    }

    /**
     * Schedule manifest files to be regenerated on menu display location change.
     *
     * @param array $value
     * @return array
     */
    public function regenerateMenuManifestFileOnDisplayLocationChange($value)
    {
        $this->scheduleMenuManifestFileRegeneration();
        return $value;
    }

    /**
     * Schedule menu manifest files to be regenerated.
     */
    public function scheduleMenuManifestFileRegeneration(): void
    {
        if (WpPrefetching::shouldWriteFilesUsingCron()) {
            WpPrefetching::scheduleRecordPrefetchItems();
        } else {
            WpPrefetching::rescheduleManifestDataGeneration(); // Delete transient so that we will record prefetch items on next page load
            ManifestFileWriter::clear('menu'); // Delete menu prefetch files
            ManifestFileWriter::removeFromWrittenFiles('menu'); // Prevent menu prefetch file from being printed to headers
            // We're not using cron, so we need to wait until someone is visiting the site again
        }
    }
}
