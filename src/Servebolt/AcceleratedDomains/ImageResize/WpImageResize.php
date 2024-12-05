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
        $this->addSrcOverrideInTheContentHook();
        $this->correctPotentialBadIamgesHook();
    }

    public function addSingleImageUrlHook(): void
    {
        if (apply_filters('sb_optimizer_acd_image_resize_alter_src', true)) {
            add_filter('wp_get_attachment_image_src', [$this, 'alterSingleImageUrl'], 10, 2);
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
     * Scans the_content for images that have un-transformed urls.
     */
    public function addSrcOverrideInTheContentHook(): void
    {
        if (apply_filters('sb_optimizer_acd_image_resize_alter_src', true)) {
            add_filter('the_content', [$this, 'alterImagesIntheContent'], 99, 1 );
        }
    }

    /**
     * Add hook to duplicate all existing sizes in the srcset-array to contain half the size.
     */
    public function addHalfSizesToSrcsetHook(): void
    {
        if (apply_filters('sb_optimizer_acd_image_resize_add_half_sizes', true)) {
            add_filter('wp_calculate_image_srcset', [$this, 'addHalfSizesToSrcset'], 9, 5);
        }
    }

    public function correctPotentialBadIamgesHook(): void
    {
        add_filter('acd_image_resize_force_thumbnail_minimum_width', [$this, 'correctPotentialBadImages'], 10, 3);
    }
}
