<?php

namespace Servebolt\Optimizer\Prefetching;

use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\setDefaultOption;
use function Servebolt\Optimizer\Helpers\smartGetOption;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class Prefetching
 * @package Servebolt\Optimizer\Prefetching
 */
class WpPrefetching extends Prefetching
{
    /**
     * Prefetching constructor.
     */
    public function __construct()
    {
        $this->defaultOptionValues();
        if (self::isActive()) {
            $this->initFeature();
        }
    }

    /**
     * Initialize feature.
     */
    private function initFeature(): void
    {
        if ($this->shouldAddHeaders()) {
            add_action('send_headers', [__NAMESPACE__ . '\\ManifestHeaders', 'printManifestHeaders'], PHP_INT_MAX);
        }

        if (!Prefetching::shouldGenerateManifestData()) {
            return;
        }

        if ($this->shouldRecordScripts()) {
            add_action('wp_print_scripts', [$this, 'getScriptsToPrefetch'], 99);
        }
        if ($this->shouldRecordStyles()) {
            add_action('wp_print_styles', [$this, 'getStylesToPrefetch'], 99);
        }
        if ($this->shouldRecordMenuItems()) {
            add_action('wp_footer', [$this, 'prefetchListMenuItems'], 99);
        }

        if ($this->shouldStoreManifestData()) {
            add_action('wp_footer', [$this, 'generateManifestFilesData'], 99);
        }
        if ($this->shouldDebugManifestData()) {
            add_action('wp_footer', [$this, 'debugManifestFilesData'], 100);
        }
    }

    /**
     * Check whether the prefetching feature is active.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function isActive(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'prefetch_switch'));
    }

    /**
     * Set default option values.
     */
    private function defaultOptionValues(): void
    {
        setDefaultOption('prefetch_file_style_switch', '__return_true');
        setDefaultOption('prefetch_file_script_switch', '__return_true');
        setDefaultOption('prefetch_file_menu_switch', '__return_true');
    }

    private function shouldAddHeaders(): bool
    {
        return apply_filters('sb_optimizer_asset_prefetch_add_headers', true);
    }

    private function shouldRecordScripts(): bool
    {
        return apply_filters('sb_optimizer_asset_prefetch_record_scripts', true);
    }

    private function shouldRecordMenuItems(): bool
    {
        return apply_filters('sb_optimizer_asset_prefetch_record_menu_items', true);
    }

    private function shouldRecordStyles(): bool
    {
        return apply_filters('sb_optimizer_asset_prefetch_record_styles', true);
    }

    private function shouldStoreManifestData(): bool
    {
        return apply_filters('sb_optimizer_asset_prefetch_write_manifest_file_override', false)
        ||
        (
            apply_filters('sb_optimizer_asset_prefetch_write_manifest_file', true)
            || $this->shouldRecordScripts()
            || $this->shouldRecordStyles()
            || $this->shouldRecordMenuItems()
        );
    }

    private function shouldDebugManifestData(): bool
    {
        return apply_filters('sb_optimizer_asset_prefetch_should_debug', false);
    }
}
