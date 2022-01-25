<?php

namespace Servebolt\Optimizer\AcceleratedDomains\Prefetching;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\isFrontEnd;
use function Servebolt\Optimizer\Helpers\isWpRest;
use function Servebolt\Optimizer\Helpers\javascriptRedirect;
use function Servebolt\Optimizer\Helpers\setDefaultOption;
use function Servebolt\Optimizer\Helpers\smartGetOption;

/**
 * Class WpPrefetching
 * @package Servebolt\Optimizer\AcceleratedDomains\Prefetching
 */
class WpPrefetching extends Prefetching
{
    use Singleton;

    /**
     * Default max number of lines in a manifest file.
     *
     * @var int
     */
    public static $defaultMaxNumberOfLines = 100;

    /**
     * @var string The action hook used for scheduling a WP Cron-event.
     */
    public static $hook = 'sb_optimizer_prefetching_record_prefetch_items';

    /**
     * Alias for "getInstance".
     */
    public static function init()
    {
        self::getInstance();
    }

    /**
     * WpPrefetching constructor.
     */
    public function __construct()
    {
        $this->defaultOptionValues();
        if (self::isActive()) {
            $this->registerCronActionHooks();
            $this->filePurgeHandling();
            add_action('init', [$this, 'initFeature']);
        }
    }

    /**
     * Initialize feature.
     */
    public function initFeature(): void
    {
        if ($this->shouldAddHeaders()) {
            add_action('send_headers', [__NAMESPACE__ . '\\ManifestHeaders', 'printManifestHeaders'], PHP_INT_MAX);
            if ($this->shouldDisplaySuccessAlert()) {
                add_action('wp_footer', [$this, 'successAlert']);
            }
        }
        if (self::shouldGenerateManifestData()) {
            add_action('template_redirect', [$this, 'recordPrefetchItemsDuringTemplateLoading']);
        }
    }

    /**
     * Display success-message after we have loaded the front page with newly updated manifest files.
     *
     * @return void
     */
    public function successAlert()
    {
        ?>
        <script>
            alert('<?php _e('The manifest files are now generated and should be updated in Accelerated Domains. You will now be sent back to WP Admin.', 'servebolt-wp'); ?>');
        </script>
        <?php
        $this->redirectBackToWpAdmin();
    }

    /**
     * Redirect back to WP Admin after prefetch items record.
     *
     * @return void
     */
    private function redirectBackToWpAdmin(): void
    {
        javascriptRedirect(wp_login_url(get_admin_url(null, 'admin.php?page=servebolt-prefetching&manual-prefetch-success')));
    }

    /**
     * Check whether we should display a success-alert.
     *
     * @return bool
     */
    private function shouldDisplaySuccessAlert(): bool
    {
        return self::isCloudflareManifestFilesRefreshRequest();
    }

    /**
     * Handle purging of files when updates happen.
     *
     * @return void
     */
    private function filePurgeHandling()
    {
        if (isWpRest()) {
            add_action('rest_api_init', __NAMESPACE__ . '\\FilePurge::init');
        } else {
            add_action('admin_init', __NAMESPACE__ . '\\FilePurge::init');
        }
    }

    /**
     * Register cron actions so that we can use cron to record prefetch items and write manifest files.
     */
    private function registerCronActionHooks(): void
    {
        add_action(self::$hook, __CLASS__ . '::recordPrefetchItemsAndExposeManifestFiles');
    }

    /**
     * Schedule the regeneration of prefetch items using WP Cron.
     */
    public static function scheduleRecordPrefetchItems()
    {
        if (!wp_next_scheduled(self::$hook)) {
            wp_schedule_single_event(time(), self::$hook);
        }
    }

    /**
     * De-schedule the regeneration of prefetch items using WP Cron.
     */
    public static function unscheduleRecordPrefetchItems()
    {
        $next = wp_next_scheduled(self::$hook);
        if ($next) {
            wp_unschedule_event($next, self::$hook);
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
            add_action('wp_footer', [$this, 'generateManifestFilesData'], 100);
        }

        if ($this->shouldRedirect()) {
            add_action('wp_footer', [$this, 'redirectAfterPrefetchItemsRecord'], 100);
        }
    }

    /**
     * Redirect/reload to expose the manifest file to Cloudflare.
     *
     * @return void
     */
    public function redirectAfterPrefetchItemsRecord(): void
    {
        if (self::shouldExposeManifestFilesAfterPrefetchItemsRecord()) {
            javascriptRedirect(self::getCloudflareRefreshUrlWithParameters());
        } else {
            $this->redirectBackToWpAdmin();
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
        return apply_filters('sb_optimizer_prefetch_max_number_of_lines', $maxNumberOfLines);
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
        $isFrontEnd = isFrontEnd();
        $isUserLoggedIn = is_user_logged_in();
        $isRecordPrefetchItemsRequest = self::isRecordPrefetchItemsRequest();
        $shouldAddHeaders = false;

        if (
            $isFrontEnd
            && !$isUserLoggedIn
            && !$isRecordPrefetchItemsRequest
        ) {
            $shouldAddHeaders = true;
        }

        /**
         * @param bool $shouldAddHeaders Whether we should add the manifest-file headers.
         * @param bool $isFrontEnd Whether we are in a front-end context.
         * @param bool $isUserLoggedIn Whether the user is currently logged in.
         * @param bool $isRecordPrefetchItemsRequest Whether the current request is meant to record prefeth items.
         */
        return (bool) apply_filters('sb_optimizer_prefetching_add_headers', $shouldAddHeaders, $isFrontEnd, $isUserLoggedIn, $isRecordPrefetchItemsRequest);
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

    /**
     * Check whether we should redirect after having recorded prefetch items / generated manifest files.
     *
     * @return bool
     */
    private function shouldRedirect(): bool
    {
        return arrayGet('redirect', $_GET) === 'true';
    }

    private function shouldStoreManifestData(): bool
    {
        $override = apply_filters('sb_optimizer_prefetching_write_manifest_file_override', null);
        if (is_bool($override)) {
            return $override;
        }
        return apply_filters('sb_optimizer_prefetching_write_manifest_file', true)
            || $this->shouldRecordScripts()
            || $this->shouldRecordStyles()
            || $this->shouldRecordMenuItems();
    }
}
