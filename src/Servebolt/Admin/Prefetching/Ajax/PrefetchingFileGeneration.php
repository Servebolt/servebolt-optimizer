<?php

namespace Servebolt\Optimizer\Admin\Prefetching\Ajax;

use Servebolt\Optimizer\Admin\SharedAjaxMethods;
use Servebolt\Optimizer\Prefetching\WpPrefetching;
use function Servebolt\Optimizer\Helpers\ajaxUserAllowed;

/**
 * Class ImageSizeIndex
 * @package Servebolt\Optimizer\Admin\AcceleratedDomainsImageControl\Ajax
 */
class PrefetchingFileGeneration extends SharedAjaxMethods
{
    /**
     * ImageSizeIndex constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_servebolt_prefetching_generate_files', [$this, 'generateFiles']);
    }

    /**
     * AJAX callback for prefetch file generation.
     */
    public function generateFiles(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();
        WpPrefetching::rescheduleManifestDataGeneration(); // We've changed settings, let's regenerate the data
        WpPrefetching::recordPrefetchItems();
        wp_send_json_success();
    }
}
