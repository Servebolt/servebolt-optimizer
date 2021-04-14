<?php

namespace Servebolt\Optimizer\CloudflareImageResize;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class CloudflareImageResize
 * @package Servebolt\Optimizer\CloudflareImageResize
 */
class CloudflareImageResize
{

    /**
     * @var string
     */
    private $cdnUriSegment = '/cdn-cgi/image/';

    /**
     * Max image width when resizing with Cloudflare.
     *
     * @var int
     */
    private $maxWidth = 1920;

    /**
     * Max image height when resizing with Cloudflare.
     *
     * @var int
     */
    private $maxHeight = 1080;

    /**
     * An array of sizes that should always be created.
     *
     * @var array
     */
    private $alwaysCreateSizes = [];

    /**
     * Property to store original size array while we alter image sizes during image upload.
     *
     * @var array
     */
    private $originalSizes = [];

    /**
     * CloudflareImageResize constructor.
     * @param bool $init
     */
    public function __construct(bool $init = true)
    {
        if ($init) {
            $this->init();
        }
    }

    /**
     * Initialize image resizing.
     */
    private function init()
    {
        $this->initImageResize();
        $this->initImageUpscale();
    }

    /**
     * Register image resize hooks.
     */
    public function initImageResize(): void
    {

        // Alter srcset-attribute URLs
        if ( apply_filters('sb_optimizer_cf_image_resize_alter_srcset', true ) ) {
            add_filter( 'wp_calculate_image_srcset', [ $this, 'alterSrcsetImageUrls' ] );
        }

        // Alter image src-attribute URL
        if ( apply_filters('sb_optimizer_cf_image_resize_alter_src', true ) ) {
            add_filter( 'wp_get_attachment_image_src', [ $this, 'alterSingleImageUrl' ] );
        }

        // Prevent certain image sizes to be created since we are using Cloudflare for resizing
        if ( apply_filters('sb_optimizer_cf_image_resize_alter_intermediate_sizes', true ) ) {
            add_filter( 'intermediate_image_sizes_advanced', [ $this, 'overrideImageSizeCreation' ], 10, 2 );
        }
    }

    /**
     * Initialize image upscaling when resizing to a cropped size (if the image is too small to fill the cropped proportions).
     */
    public function initImageUpscale(): void
    {

        // Whether we should upscale images that are too small to fill the proportions of an image size
        if (apply_filters('sb_optimizer_cf_image_resize_upscale_images', true)) {
            add_filter('image_resize_dimensions', [$this, 'imageUpscale'], 10, 6);
        }
    }

    /**
     * Only resize images when needed.
     *
     * @param $sizes
     * @param $imageMeta
     *
     * @return array
     */
    public function overrideImageSizeCreation($sizes, $imageMeta)
    {

        // Store the image sizes for later use
        $this->originalSizes = $sizes;

        // Re-add image sizes after file creation
        add_filter( 'wp_get_attachment_metadata', [ $this, 'reAddImageSizes' ] );

        // Determine which image sizes we should generate files for
        $uploadedImageRatio = $imageMeta['width'] / $imageMeta['height'];
        return array_filter($sizes, function ($size, $key) use ($imageMeta, $uploadedImageRatio) {

            // Check if this is a size that we should always generate
            if ( in_array($key, (array) apply_filters('sb_optimizer_cf_image_resize_always_create_sizes', $this->alwaysCreateSizes) ) ) {
                return true;
            }

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

    /**
     * @param $data
     * @return array
     */
    public function reAddImageSizes(array $data): array
    {
        $file = substr($data['file'], strrpos($data['file'], '/') + 1);
        foreach ( $this->originalSizes as $key => $value ) {
            $this->originalSizes[$key] = $value;
            $this->originalSizes[$key]['file'] = $file;
        }
        $data['sizes'] = $this->originalSizes;
        return $data;
    }

    /**
     * Get max width.
     *
     * @return mixed|void
     */
    private function maxWidth(): int
    {
        return apply_filters('sb_optimizer_cf_image_resize_max_width', $this->maxWidth);
    }

    /**
     * Get max height.
     *
     * @return mixed|void
     */
    private function maxHeight(): int
    {
        return apply_filters('sb_optimizer_cf_image_resize_max_height', $this->maxHeight);
    }

    /**
     * Alter image src-attribute.
     *
     * @param $image
     *
     * @return mixed
     */
    public function alterSingleImageUrl($image)
    {

        list($url, $width, $height, $isIntermediate) = $image;

        $cfParams = [];

        // Set max dimensions
        if ($width > $this->maxWidth()) {
            $width = $this->maxWidth();
        }
        if ($height > $this->maxHeight()) {
            $cfParams['height'] = $this->maxHeight();
        }
        $cfParams['width'] = $width;

        $image[0] = $this->buildImageUrl($url, $this->defaultCfParams($cfParams));

        return $image;
    }

    /**
     * Alter srcset-attribute.
     *
     * @param $sources
     *
     * @return mixed
     */
    public function alterSrcsetImageUrls($sources)
    {
        foreach ( $sources as $key => $value ) {
            $descriptor = $value['descriptor'] === 'h' ? 'height' : 'width';
            $cfParams = $this->defaultCfParams([
                $descriptor => $value['value']
            ]);
            $sources[$key]['url'] = $this->buildImageUrl($value['url'], $cfParams);
        }
        return $sources;
    }

    /**
     * Convert Cloudflare image resize parameter array to string.
     *
     * @param array $cfParams
     *
     * @return string
     */
    private function implodeParameters(array $cfParams): string
    {
        $cfParamArray = [];
        foreach ($cfParams as $key => $value) {
            $cfParamArray[] = $key . '=' . $value;
        }
        return implode(',', $cfParamArray);
    }

    /**
     * Build Cloudflare image resize URL.
     *
     * @param string $url
     * @param array $cfParams
     *
     * @return string
     */
    private function buildImageUrl(string $url, array $cfParams = []): string
    {
        $url = wp_parse_url($url);
        $cfParameterString = $this->implodeParameters($cfParams);
        $alteredUrl = $url['scheme'] . '://' . $url['host'] . $this->cdnUriSegment . $cfParameterString . $url['path'];
        return apply_filters('sb_optimizer_cf_image_resize_url', $alteredUrl, $url, $this->cdnUriSegment, $cfParams, $cfParameterString);
    }

    /**
     * Get default Cloudflare image resize parameters.
     *
     * @param array $additionalParams
     *
     * @return array
     */
    private function defaultCfParams(array $additionalParams = []): array
    {
        $additionalParams = apply_filters('sb_optimizer_cf_image_resize_default_params_additional', $additionalParams);
        $defaultParams = apply_filters('sb_optimizer_cf_image_resize_default_params', [
            'quality' => '60',
            'format'  => 'auto',
            'onerror' => 'redirect',
        ]);
        return apply_filters('sb_optimizer_cf_image_resize_default_params_concatenated', wp_parse_args($additionalParams, $defaultParams));
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

        return apply_filters('sb_optimizer_cf_image_resize_upscale_dimensions', [0, 0, (int) $sx, (int) $sy, (int) $newW, (int) $newH, (int) $cropW, (int) $cropH]);
    }
}
