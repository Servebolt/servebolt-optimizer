<?php

namespace Servebolt\Optimizer\Utils;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;

/**
 * Class ImageUpscale
 * @package Servebolt\Optimizer\Utils
 */
class ImageUpscale
{
    use Singleton;

    /**
     * ImageUpscale constructor.
     */
    public function __construct()
    {
        // Whether we should upscale images that are too small to fill the proportions of an image size
        if (apply_filters('sb_optimizer_upscale_images', true)) {
            add_filter('image_resize_dimensions', [$this, 'imageUpscale'], 10, 6);
        }
    }

    /**
     * When generating cropped image sizes then upscale the image if the original is too small, so that we get the proportion specified in the image size.
     *
     * @param $default
     * @param $origW
     * @param $origH
     * @param $newW
     * @param $newH
     * @param $crop
     *
     * @return array|null
     */
    function imageUpscale($default, $origW, $origH, $newW, $newH, $crop)
    {
        if (!$crop) {
            return $default; // Let the WordPress default function handle this
        }

        $sizeRatio = max($newW / $origW, $newH / $origH);

        $cropW = round($newW / $sizeRatio);
        $cropH = round($newH / $sizeRatio);

        $sx = floor( ($origW - $cropW) / 2 );
        $sy = floor( ($origH - $cropH) / 2 );

        return apply_filters('sb_optimizer_image_resize_upscale_dimensions', [0, 0, (int) $sx, (int) $sy, (int) $newW, (int) $newH, (int) $cropW, (int) $cropH]);
    }
}
