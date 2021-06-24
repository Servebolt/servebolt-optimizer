<?php

namespace Servebolt\Optimizer\AssetPrefetching;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class AssetPrefetching
 * @package Servebolt\Optimizer\AssetPrefetching
 */
class WpAssetPrefetching extends AssetPrefetching
{
    /**
     * AssetPrefetching constructor.
     */
    public function __construct()
    {
        if ($recordScripts = apply_filters('sb_optimizer_asset_prefetch_record_scripts', true)) {
            add_action('wp_print_scripts', [$this, 'prefetchListScripts'], 99);
        }
        if ($recordStyles = apply_filters('sb_optimizer_asset_prefetch_record_styles', true)) {
            add_action('wp_print_styles', [$this, 'prefetchListStyles'], 99);
        }
        if ($recordMenuItems = apply_filters('sb_optimizer_asset_prefetch_record_menu_items', true)) {
            add_action('wp_footer', [$this, 'prefetchListMenuItems'], 99);
        }
        if (
            apply_filters('sb_optimizer_asset_prefetch_write_manifest_file', true)
            || $recordScripts
            || $recordStyles
            || $recordMenuItems
        ) {
            add_action('wp_footer', [$this, 'writeManifestFile'], 99);
        }
    }
}
