<?php

namespace Servebolt\Optimizer\AcceleratedDomains\Prefetching;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\isFrontEnd;
use function Servebolt\Optimizer\Helpers\setDefaultOption;
use function Servebolt\Optimizer\Helpers\smartGetOption;

/**
 * Class WpPrefetching
 * @package Servebolt\Optimizer\AcceleratedDomains\Prefetching
 */
class WpPrefetching extends Prefetching
{

    /**
     * Default max number of lines in a manifest file.
     *
     * @var int
     */
    public static $defaultMaxNumberOfLines = 100;

    /**
     * WpPrefetching constructor.
     */
    public function __construct()
    {
        $this->defaultOptionValues();
        if (self::isActive()) {
            $this->registerCronHook();
            add_action('init', [$this, 'initFeature']);
            add_action('admin_init', __NAMESPACE__ . '\\FilePurge::init');
        }
    }

    /**
     * Register cron action so that we can use cron to write manifest files.
     */
    private function registerCronHook(): void
    {
        if (self::shouldWriteFilesUsingCron()) {
            add_action('sb_optimizer_prefetching_write_manifest_files', __NAMESPACE__ . '\\ManifestFileWriter::write');
        }
        add_action('sb_optimizer_prefetching_record_prefetch_items', [$this, 'handleScheduledPrefetchItemsRecording']);
    }

    /**
     * Schedule the regeneration of prefetch items using WP Cron.
     */
    public static function scheduleRecordPrefetchItems()
    {
        $hook = 'sb_optimizer_prefetching_record_prefetch_items';
        if (!wp_next_scheduled($hook)) {
            wp_schedule_single_event(time(), $hook);
        }
    }

    /**
     * Handle the regeneration of prefetch items using WP Cron.
     */
    public function handleScheduledPrefetchItemsRecording()
    {
        self::recordPrefetchItems(); // Load the front page to record prefetch items
    }

    /**
     * Initialize feature.
     */
    public function initFeature(): void
    {
        if ($this->shouldAddHeaders()) {
            add_action('send_headers', [__NAMESPACE__ . '\\ManifestHeaders', 'printManifestHeaders'], PHP_INT_MAX);
        }
        if (self::shouldGenerateManifestData()) {
            add_action('template_redirect', [$this, 'recordPrefetchItemsDuringTemplateLoading']);
        }
    }

    /**
     * Add filters to record prefetch items, then write them to the model.
     */
    public function recordPrefetchItemsDuringTemplateLoading(): void
    {
        $this->setMaxNumberOfLines();
        $this->setRelativeOrFullUrls();
        if ($this->shouldRecordStyles()) {
            add_action('wp_print_styles', [$this, 'getStylesToPrefetch'], 99);
        }
        if ($this->shouldRecordScripts()) {
            add_action('wp_print_scripts', [$this, 'getScriptsToPrefetch'], 99);
        }
        if ($this->shouldRecordMenuItems()) {
            add_action('wp_footer', [$this, 'getPrefetchListMenuItems'], 99);
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
            add_filter('sb_optimizer_prefetching_max_number_of_lines', function() use ($maxNumberOfLines) {
                return $maxNumberOfLines;
            });
        }
    }

    /**
     * Set full or relative URLs.
     */
    private function setRelativeOrFullUrls(): void
    {
        if (self::writeFullUrls()) {
            add_filter('sb_optimizer_prefetching_include_domain', '__return_true'); // Use full URLs
        } else {
            // Use relative URLs
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
     * Check whether we should write full URLs in the manifest files.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function writeFullUrls(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'prefetch_full_url_switch'));
    }

    /**
     * Check if we have set a limitation for the number of lines per manifest file.
     *
     * @param int|null $blogId
     * @return null|int
     */
    public static function getMaxNumberOfLines(?int $blogId = null): ?int
    {
        $maxNumberOfLines = smartGetOption($blogId, 'prefetch_max_number_of_lines');
        if (is_numeric($maxNumberOfLines)) {
            $maxNumberOfLines = (int) $maxNumberOfLines;
        } else {
            $maxNumberOfLines = self::getDefaultMaxNumberOfLines();
        }
        return apply_filters('sb_optimizer_prefetcht_max_number_of_lines', $maxNumberOfLines);
    }

    /**
     * Get default number of lines for each prefetch manifest file.
     *
     * @return null|int
     */
    public static function getDefaultMaxNumberOfLines(): ?int
    {
        return apply_filters('sb_optimizer_prefetching_default_max_number_of_lines', self::$defaultMaxNumberOfLines);
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

    /**
     * Whether to add the prefetching headers to the response.
     *
     * @return bool
     */
    private function shouldAddHeaders(): bool
    {
        return (bool) apply_filters('sb_optimizer_prefetching_add_headers', (isFrontEnd() && !is_user_logged_in()));
    }

    private function shouldRecordStyles(): bool
    {
        return (bool) apply_filters('sb_optimizer_prefetching_record_styles', self::fileIsActive('style'));
    }

    private function shouldRecordScripts(): bool
    {
        return (bool) apply_filters('sb_optimizer_prefetching_record_scripts', self::fileIsActive('script'));
    }

    private function shouldRecordMenuItems(): bool
    {
        return (bool) apply_filters('sb_optimizer_prefetching_record_menu_items', self::fileIsActive('menu'));
    }

    private function shouldStoreManifestData(): bool
    {
        return apply_filters('sb_optimizer_prefetching_write_manifest_file_override', false)
        ||
        (
            apply_filters('sb_optimizer_prefetching_write_manifest_file', true)
            || $this->shouldRecordScripts()
            || $this->shouldRecordStyles()
            || $this->shouldRecordMenuItems()
        );
    }
}