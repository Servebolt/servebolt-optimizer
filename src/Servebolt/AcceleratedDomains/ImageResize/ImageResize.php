<?php

namespace Servebolt\Optimizer\AcceleratedDomains\ImageResize;

/**
 * Class ImageResize
 * @package Servebolt\Optimizer\AcceleratedDomains\ImageResize
 */
class ImageResize
{

    /**
     * @var string Resize service base URL.
     */
    private $cgiPrefix = 'acd-cgi/img';

    /**
     * @var string Resize service version.
     */
    private $version = 'v1';

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
     * The default image quality.
     *
     * @var int
     */
    private $defaultQuality = 85;

    /**
     * ImageResize constructor.
     */
    public function __construct(bool $init = true)
    {
        if ($init) {
            $this->imageResize();
        }
    }

    /**
     * Register image resize hooks.
     */
    private function imageResize(): void
    {
        // Alter image src-attribute URL
        if (apply_filters('sb_optimizer_acd_image_resize_alter_src', true)) {
            add_filter('wp_get_attachment_image_src', [$this, 'alterSingleImageUrl']);
        }

        // Alter srcset-attribute URLs
        if (apply_filters('sb_optimizer_acd_image_resize_alter_srcset', true)) {
            add_filter('wp_calculate_image_srcset', [$this, 'alterSrcsetImageUrls']);
        }

        // Prevent certain image sizes to be created since we are using ACD / Cloudflare for resizing
        if (apply_filters('sb_optimizer_acd_image_resize_alter_intermediate_sizes', true)) {
            add_filter('intermediate_image_sizes_advanced', [$this, 'overrideImageSizeCreation'], 10, 2);
        }
    }

    /**
     * Alter srcset-attribute.
     *
     * @param $sources
     *
     * @return array
     */
    public function alterSrcsetImageUrls($sources): array
    {
        foreach ($sources as $key => $value) {
            $descriptor = $value['descriptor'] === 'h' ? 'height' : 'width';
            $resizeParameters = $this->defaultImageResizeParameters([
                $descriptor => $value['value']
            ]);
            $sources[$key]['url'] = $this->buildImageUrl($value['url'], $resizeParameters);
        }
        return $sources;
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
        if ($image) {
            $image[0] = $this->buildImageUrl($image[0], $this->generateImageResizeParameters($image));
        }
        return $image;
    }

    /**
     * Generate image resize parameters.
     *
     * @param $image
     * @return array
     */
    private function generateImageResizeParameters($image): array
    {
        list($url, $width, $height, $isIntermediate) = $image;
        $additionalParams = [];

        // Set max width
        $maxWidth = $this->maxWidth();
        if ($maxWidth && $width > $maxWidth) {
            $width = $this->maxWidth();
        }
        $additionalParams['width'] = $width;

        // Set max height
        $maxHeight = $this->maxHeight();
        if ($maxHeight && $height > $maxHeight) {
            $height = $this->maxHeight();
            $additionalParams['height'] = $height; // We only want to set
        }
        if (apply_filters('sb_optimizer_acd_image_resize_force_add_height', false)) {
            $additionalParams['height'] = $height;
        }

        return $this->defaultImageResizeParameters($additionalParams);
    }

    /**
     * @param $additionalParams
     * @return array
     */
    private function defaultImageResizeParameters($additionalParams): array
    {
        $additionalParams = apply_filters('sb_optimizer_acd_image_resize_additional_params', $additionalParams);
        $defaultParams = apply_filters('sb_optimizer_acd_image_resize_default_params', [
            'quality' => $this->defaultQuality,
            'format'  => 'auto',
        ]);
        return apply_filters('sb_optimizer_acd_image_resize_params_concatenated', wp_parse_args($additionalParams, $defaultParams),$additionalParams, $defaultParams);
    }

    /**
     * Get max width.
     *
     * @return mixed|void
     */
    private function maxWidth(): int
    {
        return apply_filters('sb_optimizer_acd_image_resize_max_width', $this->maxWidth);
    }

    /**
     * Get max height.
     *
     * @return mixed|void
     */
    private function maxHeight(): int
    {
        return apply_filters('sb_optimizer_acd_image_resize_max_height', $this->maxHeight);
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
            if ( in_array($key, (array) apply_filters('sb_optimizer_acd_image_resize_always_create_sizes', $this->alwaysCreateSizes) ) ) {
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
     * Get image resize service path prefix.
     *
     * @return string
     */
    private function pathPrefix(): string
    {
        return '/' . $this->cgiPrefix . '/' . $this->version;
    }

    /**
     * Build image URL.
     *
     * @param string $url
     * @param array $resizeParameters
     *
     * @return string
     */
    public function buildImageUrl(string $url, array $resizeParameters = []): string
    {
        $urlParts = wp_parse_url($url);
        $resizeParametersString = $this->buildQueryStringFromArray($resizeParameters);
        $pathPrefix = $this->pathPrefix();
        $alteredUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . $pathPrefix . $urlParts['path'] . $this->concatenateQueryString($urlParts, $resizeParametersString);
        return apply_filters('sb_optimizer_acd_image_resize_url', $alteredUrl, $url, $urlParts, $pathPrefix, $resizeParameters, $resizeParametersString);
    }

    /**
     * Build query string from existing query with resize parameters.
     *
     * @param array $urlParts
     * @param string $parameterQueryString
     * @return string
     */
    private function concatenateQueryString(array $urlParts, string $parameterQueryString): string
    {
        $hasQuery = isset($urlParts['query']);
        $queryString = '';
        if ($hasQuery || $parameterQueryString) {
            $queryString .= '?';
        }
        if ($hasQuery) {
            $queryString .= $urlParts['query'];
        }
        if ($hasQuery && $parameterQueryString) {
            $queryString .= '&';
        }
        if ($parameterQueryString) {
            $queryString .= $parameterQueryString;
        }
        return $queryString;
    }

    /**
     * Convert associative array to query string.
     *
     * @param array $params
     *
     * @return string
     */
    private function buildQueryStringFromArray(array $params): string
    {
        return http_build_query($params);
    }
}
