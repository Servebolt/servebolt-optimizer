<?php

namespace Servebolt\Optimizer\Admin\Prefetching\Ajax;

use Exception;
use Servebolt\Optimizer\AcceleratedDomains\Prefetching\WpPrefetching;
use Servebolt\Optimizer\Admin\SharedAjaxMethods;
use function Servebolt\Optimizer\Helpers\ajaxUserAllowed;

/**
 * Class PrefetchingControlAjax
 * @package Servebolt\Optimizer\Admin\AcceleratedDomainsImageControl\Ajax
 */
class PrefetchingControlAjax extends SharedAjaxMethods
{
    /**
     * PrefetchingControlAjax constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_servebolt_prefetching_generate_files', [$this, 'generateFiles']);
        add_action('wp_ajax_servebolt_prefetching_generate_files_instructions', [$this, 'generateFilesInstructions']);
        add_action('wp_ajax_servebolt_prefetching_generate_files_using_cron', [$this, 'generateFilesUsingCron']);
        add_action('wp_ajax_servebolt_prefetching_prepare_for_manual_generation', [$this, 'prepareForManualManifestFileGeneration']);
    }

    /**
     * AJAX callback for prefetch file generation.
     *
     * @return void
     */
    public function generateFiles(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();
        if (!WpPrefetching::isActive()) {
            wp_send_json_error();
            return;
        }
        try {
            WpPrefetching::recordPrefetchItemsAndExposeManifestFiles();
        } catch (Exception $e) {
            wp_send_json_error();
        }
    }

    /**
     * AJAX callback for prefetch file generation instructions.
     *
     * @return void
     */
    public function generateFilesInstructions(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();
        if (!WpPrefetching::isActive()) {
            wp_send_json_error();
            return;
        }
        try {
            WpPrefetching::clearDataAndFiles();
            wp_logout();
            wp_send_json_success([
                'generation_url' => WpPrefetching::getFrontPageUrlWithParameters(),
                'manifest_files_expose_url' => WpPrefetching::getCloudflareRefreshUrlWithParameters(),
                'should_expose_manifest_files_after_prefetch_items_record' => WpPrefetching::shouldExposeManifestFilesAfterPrefetchItemsRecord(),
                'login_url' => WpPrefetching::loginUrl(),
            ]);
        } catch (Exception $e) {
            wp_send_json_error();
        }
    }

    /**
     * AJAX callback for prefetch file generation using cron.
     *
     * @return void
     */
    public function generateFilesUsingCron(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();
        if (!WpPrefetching::isActive()) {
            wp_send_json_error();
            return;
        }
        try {
            WpPrefetching::scheduleRecordPrefetchItems();
            wp_send_json_success();
        } catch (Exception $e) {
            wp_send_json_error();
        }
    }

    /**
     * Log the current user out.
     *
     * @return void
     */
    public function prepareForManualManifestFileGeneration()
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();
        if (!WpPrefetching::isActive()) {
            wp_send_json_error();
            return;
        }
        WpPrefetching::clearDataAndFiles();
        wp_logout();
        wp_send_json_success();
    }
}
