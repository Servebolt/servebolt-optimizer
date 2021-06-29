<?php

namespace Servebolt\Optimizer\AcceleratedDomains\ImageResize;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class WpImageSizeIndex
 * @package Servebolt\Optimizer\AcceleratedDomains\ImageResize
 */
class WpImageSizeIndex
{
    /**
     * WpImageSizeIndex constructor.
     */
    public function __construct()
    {
        if (apply_filters('sb_optimizer_acd_add_custom_sizes_to_srcset', true)) {
            add_filter('wp_calculate_image_srcset', [$this, 'addCustomImageSizesToSrcset'], 10, 3);
        }
    }

    /**
     * @param $sources
     * @param $sizeArray
     * @param $imageSrc
     * @return array
     */
    public function addCustomImageSizesToSrcset($sources, $sizeArray, $imageSrc): array
    {
        if ($extraSizes = ImageSizeIndexModel::getSizes()) {
            foreach ($extraSizes as $size) {
                $size['url'] = $imageSrc;
                $sources[$size['value']] = $size;
            }
        }
        ksort($sources);
        return $sources;
    }
}
