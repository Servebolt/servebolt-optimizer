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
    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * FilePurge constructor.
     */
    public function __construct()
    {
        /*
        if (apply_filters('sb_optimizer_prefetching_should_purge_on_menu_update', WpPrefetching::shouldRecordMenuItems())) {
            $this->purgeOnMenuUpdate();
        }
        */
        if (apply_filters('sb_optimizer_prefetching_should_purge_on_theme_switch', true)) {
            $this->purgeOnThemeSwitch();
        }
        if (apply_filters('sb_optimizer_prefetching_should_purge_on_plugin_activation_toggle', true)) {
            $this->purgeOnPluginActivationToggle();
        }
    }

    /**
     * Purge menu manifest file on menu update.
     */
    /*
    private function purgeOnMenuUpdate(): void
    {
        add_filter('pre_set_theme_mod_nav_menu_locations', [$this, 'scheduleManifestFileRegenerationOnDisplayLocationChange'], 10, 1); // When a menu changes
        add_action('transition_post_status', [$this, 'wpRestAutoAddToMenuHandling'], 9, 3); // During automatic addition of a page to one or more menus
    }
    */

    /**
     * Schedule manifest file regeneration when a menu item is automatically added to one or more menus from a page creation-context.
     *
     * @return void
     */
    /*
    public function wpRestAutoAddToMenuHandling($newStatus, $oldStatus, $post)
    {
        if ($this->shouldAutoAddPagesToMenus($newStatus, $oldStatus, $post) === true) {
            $this->scheduleManifestFileRegeneration();
        }
    }
    */

    /**
     * Check whether we should automatically add new pages to menus.
     * This code was taken from WP Core (v5.8.3): wp-includes/nav-menu.php:1090
     *
     * @param $new_status
     * @param $old_status
     * @param $post
     * @return bool|void
     */
    private function shouldAutoAddPagesToMenus($new_status, $old_status, $post)
    {
        if ( 'publish' !== $new_status || 'publish' === $old_status || 'page' !== $post->post_type ) {
            return;
        }
        if ( ! empty( $post->post_parent ) ) {
            return;
        }
        $auto_add = get_option( 'nav_menu_options' );
        if ( empty( $auto_add ) || ! is_array( $auto_add ) || ! isset( $auto_add['auto_add'] ) ) {
            return;
        }
        $auto_add = $auto_add['auto_add'];
        if ( empty( $auto_add ) || ! is_array( $auto_add ) ) {
            return;
        }

        foreach ( $auto_add as $menu_id ) {
            $items = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'publish,draft' ) );
            if ( ! is_array( $items ) ) {
                continue;
            }
            foreach ( $items as $item ) {
                if ( $post->ID == $item->object_id ) {
                    continue 2;
                }
            }
            return true;
        }
    }

    /**
     * Purge manifest files on theme switch.
     */
    private function purgeOnThemeSwitch(): void
    {
        add_action('switch_theme', [$this, 'scheduleManifestFileRegeneration'], 10, 0);
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
     * Schedule manifest files to be regenerated on menu display location change.
     *
     * @param mixed $value
     * @return mixed
     */
    /*
    public function scheduleManifestFileRegenerationOnDisplayLocationChange($value)
    {
        $this->scheduleManifestFileRegeneration();
        return $value;
    }
    */

    /**
     * Schedule all manifest files to be regenerated.
     */
    public function scheduleManifestFileRegeneration(): void
    {
        if ($this->isAlreadyPurged === true) {
            return;
        }
        $this->isAlreadyPurged = true;
        WpPrefetching::scheduleRecordPrefetchItems();
    }
}
