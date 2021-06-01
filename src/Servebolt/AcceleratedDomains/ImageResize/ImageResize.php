<?php

namespace Servebolt\Optimizer\AcceleratedDomains\ImageResize;

/**
 * Class ImageResize
 * @package Servebolt\Optimizer\AcceleratedDomains\ImageResize
 */
class ImageResize
{

    private $cgiPrefix = 'acd-cgi/img/';
    private $version = 'v1';

    /**
     * ImageResize constructor.
     */
    public function __construct()
    {
        $this->imageResize();
    }

    private function imageResize()
    {
        // Alter srcset-attribute URLs
        if ( apply_filters('sb_optimizer_acd_image_resize_alter_srcset', true ) ) {
            add_filter( 'wp_calculate_image_srcset', [ $this, 'alterSrcsetImageUrls' ] );
        }

        // Alter image src-attribute URL
        if ( apply_filters('sb_optimizer_acd_image_resize_alter_src', true ) ) {
            add_filter('wp_get_attachment_image_src', [$this, 'alterSingleImageUrl']);
        }

        // Prevent certain image sizes to be created since we are using Cloudflare for resizing
        if ( apply_filters('sb_optimizer_acd_image_resize_alter_intermediate_sizes', true ) ) {
            add_filter( 'intermediate_image_sizes_advanced', [ $this, 'overrideImageSizeCreation' ], 10, 2 );
        }
    }
}
