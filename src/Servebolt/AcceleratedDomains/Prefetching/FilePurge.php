<?php

namespace Servebolt\Optimizer\AcceleratedDomains\Prefetching;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;

/**
 * Class FilePurge
 * @package Servebolt\Optimizer\AcceleratedDomains\Prefetching
 */
class FilePurge
{
    use Singleton;

    /**
     * Whether we have already initiated purge (prevent from purging multiple times per execution).
     *
     * @var bool
     */
    private $isAlreadyPurged = false;

    /**
     * Alias for "getInstance".
     */
    public static function init()
    {
        self::getInstance();
    }

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
        if (apply_filters('sb_optimizer_prefetching_should_purge_on_plugin_activation_toggle', true)) {
            $this->purgeOnPluginActivationToggle();
        }
    }

    /**
     * Purge manifest files on plugin activation/deactivation.
     */
    private function purgeOnPluginActivationToggle(): void
    {
        add_action('activated_plugin', [$this, 'scheduleManifestFileRegeneration']);
        add_action('deactivated_plugin', [$this, 'scheduleManifestFileRegeneration']);
    }

    /**
     * Purge menu manifest file on menu update.
     */
    private function purgeOnMenuUpdate(): void
    {
        add_action('wp_update_nav_menu_item', [$this, 'scheduleMenuManifestFileRegeneration'], 10, 0);
        add_action('wp_update_nav_menu', [$this, 'scheduleMenuManifestFileRegeneration'], 10, 0);
        add_action('wp_delete_nav_menu', [$this, 'scheduleMenuManifestFileRegeneration'], 10, 0);
        add_filter('pre_set_theme_mod_nav_menu_locations', [$this, 'regenerateMenuManifestFileOnDisplayLocationChange'], 10, 1);
    }

    /**
     * Purge manifest files on theme switch.
     */
    private function purgeOnThemeSwitch(): void
    {
        add_action('switch_theme', [$this, 'scheduleManifestFileRegeneration'], 10, 0);
    }

    /**
     * Schedule manifest files to be regenerated.
     */
    public function scheduleManifestFileRegeneration(): void
    {
        WpPrefetching::rescheduleManifestDataGeneration(); // Delete transient so that we will record prefetch items on next page load
        ManifestFilesModel::clear(); // Prevent prefetch files from being printed to headers
        ManifestFileWriter::clear(); // Delete prefetch files
        if (WpPrefetching::shouldWriteFilesUsingCron()) {
            WpPrefetching::scheduleRecordPrefetchItems();
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
        if ($this->isAlreadyPurged) {
            return;
        }
        $this->isAlreadyPurged = true;
        WpPrefetching::rescheduleManifestDataGeneration(); // Delete transient so that we will record prefetch items on next page load
        ManifestFileWriter::clear('menu'); // Delete menu prefetch files
        ManifestFileWriter::removeFromWrittenFiles('menu'); // Prevent menu prefetch file from being printed to headers

        if (WpPrefetching::shouldWriteFilesUsingCron()) {
            WpPrefetching::scheduleRecordPrefetchItems();
        }
    }
}
