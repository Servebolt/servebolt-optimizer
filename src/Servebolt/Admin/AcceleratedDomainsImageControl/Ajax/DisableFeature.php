<?php

namespace Servebolt\Optimizer\Admin\AcceleratedDomainsImageControl\Ajax;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\AcceleratedDomains\ImageResize\AcceleratedDomainsImageResize;
use Servebolt\Optimizer\Admin\SharedAjaxMethods;
use function Servebolt\Optimizer\Helpers\ajaxUserAllowed;

/**
 * Class DisableFeature
 * @package Servebolt\Optimizer\Admin\AcceleratedDomainsImageControl\Ajax
 */
class DisableFeature extends SharedAjaxMethods
{
    /**
     * DisableFeature constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_servebolt_acd_image_resize_disable', [$this, 'disableAcceleratedDomainsImageResize']);
    }

    /**
     * AJAX handling to disable the Accelerated Domains Image Resize-feature.
     */
    public function disableAcceleratedDomainsImageResize(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();
        AcceleratedDomainsImageResize::toggleActive(false);
        wp_send_json_success();
    }
}
