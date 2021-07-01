<?php

namespace Servebolt\Optimizer\AcceleratedDomains\ImageResize;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\sbCalculateImageSizes;

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
            add_filter('wp_calculate_image_srcset', [$this, 'addCustomImageSizesToSrcset'], 10, 5);
        }
    }

    /**
     * @param $sources
     * @param $sizeArray
     * @param $imageSrc
     * @param $imageMeta
     * @param $attachmentId
     * @return array
     */
    public function addCustomImageSizesToSrcset($sources, $sizeArray, $imageSrc, $imageMeta, $attachmentId): array
    {
        $width = sbCalculateImageSizes($sizeArray, $imageSrc, $imageMeta, $attachmentId);
        if ($extraSizes = ImageSizeIndexModel::getSizes()) {
            foreach ($extraSizes as $size) {
                if (apply_filters('sb_optimizer_acd_limit_srcset_width_to_image_width', false) && $this->sizeTooBig($size, $width)) {
                    continue;
                }
                $size['url'] = $imageSrc;
                $sources[$size['value']] = $size;
            }
        }

        if (apply_filters('sb_optimizer_acd_limit_all_srcset_width_to_image_width', true)) {
            $sources = $this->limitSizesToImageSize($sources, $width);
        }
        
        ksort($sources);

        return $sources;
    }

    /**
     * Check if the current size is bigger than the image.
     *
     * @param array $size
     * @param int $width
     * @return bool
     */
    private function sizeTooBig(array $size, int $width): bool
    {
        if ($size['descriptor'] == 'w' && $size['value'] <= $width) {
            return false; // Size ok
        }
        return true;
    }

    /**
     * Limit array of srcset-sizes to the width of the image.
     *
     * @param array $sources
     * @param int $width
     * @return array
     */
    private function limitSizesToImageSize(array $sources, int $width): array
    {
        return array_filter($sources, function($size) use ($width) {
            return !$this->sizeTooBig($size, $width);
        });
    }
}
