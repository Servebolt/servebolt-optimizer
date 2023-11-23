<?php

namespace Servebolt\Optimizer\Utils;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;

/**
 * Class ImageSizeCreationOverride
 * @package Servebolt\Optimizer\Utils
 */
class ImageSizeCreationOverride
{
    use Singleton;

    /**
     * Property to store original size array while we alter image sizes during image upload.
     *
     * @var array
     */
    private $originalSizes = [];

    /**
     * An array of sizes that should always be created.
     *
     * @var array
     */
    private $alwaysCreateSizes = [];

    /**
     * ImageSizeCreationOverride constructor.
     */
    public function __construct()
    {
        add_filter('intermediate_image_sizes_advanced', [$this, 'overrideImageSizeCreation'], 10, 2);
    }

    /**
     * Only resize images when needed.
     *
     * @param $sizes
     * @param $imageMeta
     *
     * @return array
     */
    public function overrideImageSizeCreation($sizes, $imageMeta): array
    {
        // Store the image sizes for later use
        $this->originalSizes = $sizes;

        // Re-add image sizes after file creation
        add_filter('wp_get_attachment_metadata', [$this, 'reAddImageSizes']);

        // Determine which image sizes we should generate files for
        $uploadedImageRatio = $imageMeta['width'] / $imageMeta['height'];
        return array_filter($sizes, function ($size, $key) use ($imageMeta, $uploadedImageRatio) {
            // Check if this is a size that we should always generate
            if ( in_array($key, (array) apply_filters('sb_optimizer_image_resize_always_create_sizes', $this->alwaysCreateSizes) ) ) {
                return true;
            }

            // Check we can't ever do a divide by 0.
            if($size['width'] == 0 || $size['height'] == 0) return true;

            $imageSizeRatio = $size['width'] / $size['height'];
            $uploadedImageHasSameRatioAsCurrentImageSize = $uploadedImageRatio == $imageSizeRatio;
            $uploadedImageIsBiggerThanCurrentImageSize = $imageMeta['width'] >= $size['width'] && $imageMeta['height'] >= $size['height'];

            // If uploaded image has same the ratio as the original and it is bigger than the current size then we can downscale the original file with Cloudflare instead, therefore we dont need to generate the size
            if ( $uploadedImageHasSameRatioAsCurrentImageSize && $uploadedImageIsBiggerThanCurrentImageSize ) {
                return false;
            }

            // If the image proportions are changed the we need to generate it (and later we can scale the size with Cloudflare using only width since the proportions of the image is correct)
            return $size['crop'];
        }, ARRAY_FILTER_USE_BOTH);
    }
}
