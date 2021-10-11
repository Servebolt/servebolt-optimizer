<?php

namespace Servebolt\Optimizer\CloudflareImageResize;

use Servebolt\Optimizer\Utils\ImageSizeCreationOverride;
use Servebolt\Optimizer\Utils\ImageUpscale;

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
        ImageUpscale::getInstance();
    }

    /**
     * Register image resize hooks.
     */
    public function initImageResize(): void
    {

        // Alter srcset-attribute URLs
        if ( apply_filters('sb_optimizer_cf_image_resize_alter_srcset', true ) ) {
            add_filter('wp_calculate_image_srcset', [$this, 'alterSrcsetImageUrls']);
        }

        // Alter image src-attribute URL
        if ( apply_filters('sb_optimizer_cf_image_resize_alter_src', true ) ) {
            add_filter('wp_get_attachment_image_src', [$this, 'alterSingleImageUrl']);
        }

        // Prevent certain image sizes to be created since we are using Cloudflare for resizing
        if ( apply_filters('sb_optimizer_cf_image_resize_alter_intermediate_sizes', true ) ) {
            ImageSizeCreationOverride::getInstance();
        }
    }

    /**
     * @param array $data
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
     * @return array|false
     */
    public function alterSingleImageUrl($image)
    {
        if (!$image) {
            return $image;
        }

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
        foreach ($sources as $key => $value) {
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
            'format' => 'auto',
            'onerror' => 'redirect',
        ]);
        return apply_filters('sb_optimizer_cf_image_resize_default_params_concatenated', wp_parse_args($additionalParams, $defaultParams));
    }
}
