<?php

namespace Servebolt\Optimizer\AcceleratedDomains\ImageResize;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Utils\ImageSizeCreationOverride;

/**
 * Class RegisterImageResize
 * @package Servebolt\Optimizer\AcceleratedDomains\ImageResize
 */
class WpImageResize extends ImageResize
{

    /**
     * Add image resize hooks with WordPress.
     */
    public function addHooks(): void
    {
        $this->addSingleImageUrlHook();
        $this->addSrcsetImageUrlsHook();
        $this->addOverrideImageSizeCreationHook();
    }

    public function addSingleImageUrlHook(): void
    {
        if (apply_filters('sb_optimizer_acd_image_resize_alter_src', true)) {
            add_filter('wp_get_attachment_image_src', [$this, 'alterSingleImageUrl']);
        }
    }

    public function addSrcsetImageUrlsHook(): void
    {
        if (apply_filters('sb_optimizer_acd_image_resize_alter_srcset', true)) {
            add_filter('wp_calculate_image_srcset', [$this, 'alterSrcsetImageUrls'], 10, 5);
        }
    }

    /**
     * Prevent certain image sizes to be created since we are using Cloudflare for resizing.
     */
    public function addOverrideImageSizeCreationHook(): void
    {
        if (apply_filters('sb_optimizer_acd_image_resize_alter_intermediate_sizes', true)) {
            ImageSizeCreationOverride::getInstance();
        }
    }

    /**
     * Add hook to duplicate all existing sizes in the srcset-array to contain half the size.
     */
    public function addHalfSizesToSrcsetHook(): void
    {
        if (apply_filters('sb_optimizer_acd_image_resize_add_half_sizes', true)) {
            add_filter('wp_calculate_image_srcset', [$this, 'addHalfSizesToSrcset'], 9);
        }
    }
}
