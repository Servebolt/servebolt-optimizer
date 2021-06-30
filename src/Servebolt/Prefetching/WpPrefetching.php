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
            $this->registerCronHook();
            $this->initFeature();
        }
    }

    /**
     * Register cron action so that we can use cron to write manifest files.
     */
    private function registerCronHook(): void
    {
        if ($this->shouldWriteFilesUsingCron()) {
            add_action('sb_optimizer_prefetch_write_manifest_files', __NAMESPACE__ . '\\ManifestFileWriter::write');
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

        $this->setMaxNumberOfLines();

        if ($this->shouldRecordStyles()) {
            add_action('wp_print_styles', [$this, 'getStylesToPrefetch'], 99);
        }
        if ($this->shouldRecordScripts()) {
            add_action('wp_print_scripts', [$this, 'getScriptsToPrefetch'], 99);
        }
        if ($this->shouldRecordMenuItems()) {
            add_action('wp_footer', [$this, 'prefetchListMenuItems'], 99);
        }

        if ($this->shouldStoreManifestData()) {
            add_action('wp_footer', [$this, 'generateManifestFilesData'], 99);
        }
    }

    /**
     * Set max number of lines.
     */
    private function setMaxNumberOfLines(): void
    {
        if ($maxNumberOfLines = self::getMaxNumberOfLines()) {
            add_filter('sb_optimizer_prefetch_max_number_of_lines', function() use ($maxNumberOfLines) {
                return $maxNumberOfLines;
            });
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
     * Check if we should generate a given manifest file type.
     *
     * @param string $type
     * @param int|null $blogId
     * @return bool
     */
    public static function fileIsActive(string $type, ?int $blogId = null): bool
    {
        switch ($type) {
            case 'style':
            case 'script':
            case 'menu':
                break;
            default:
                return false;
        }
        return checkboxIsChecked(smartGetOption($blogId, 'prefetch_file_' . $type . '_switch'));
    }

    /**
     * Check if we have set a limitation for the number of lines per manifest file.
     *
     * @param int|null $blogId
     * @return string|int|null
     */
    public static function getMaxNumberOfLines(?int $blogId = null)
    {
        return smartGetOption($blogId, 'prefetch_max_number_of_lines', null);
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

    private function shouldRecordStyles(): bool
    {
        return apply_filters('sb_optimizer_asset_prefetch_record_styles', self::fileIsActive('style'));
    }

    private function shouldRecordScripts(): bool
    {
        return apply_filters('sb_optimizer_asset_prefetch_record_scripts', self::fileIsActive('script'));
    }

    private function shouldRecordMenuItems(): bool
    {
        return apply_filters('sb_optimizer_asset_prefetch_record_menu_items', self::fileIsActive('menu'));
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
}
