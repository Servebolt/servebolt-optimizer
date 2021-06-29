<?php

namespace Servebolt\Optimizer\AcceleratedDomains\ImageResize;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class ImageResize
 * @package Servebolt\Optimizer\AcceleratedDomains\ImageResize
 */
class ImageResize
{

    /**
     * Resize service base URL.
     *
     * @var string
     */
    private $cgiPrefix = 'acd-cgi/img';

    /**
     * Resize service version.
     *
     * @var string
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
    public static $defaultImageQuality = 85;

    /**
     * The image quality.
     *
     * @var int
     */
    private $imageQuality;

    /**
     * The level of metadata optimization.
     *
     * @var string
     */
    private $imageMetadataOptimizationLevel;

    /**
     * @var string The level of metadata optimization.
     */
    public static $defaultImageMetadataOptimizationLevel = null;

    /**
     * Duplicate all existing sizes in the srcset-array to contain half the size.
     *
     * @param array $sources
     * @param array $sizeArray
     * @param string $imageSrc
     * @param array $imageMeta
     * @param int $attachmentId
     * @return array
     */
    public function addHalfSizesToSrcset(array $sources, array $sizeArray, string $imageSrc, array $imageMeta, int $attachmentId): array
    {
        if (!$this->shouldTouchImage($attachmentId)) {
            return $sources;
        }
        foreach ($sources as $key => $value) {
            $newKey = (int) round($key / 2);
            if (!array_key_exists($newKey, $sources)) {
                $sources[$newKey] = array_merge($value, [
                    //'half-size' => true,
                    'value' => round($value['value'] / 2)
                ]);
            }
        }
        ksort($sources);
        return $sources;
    }

    /**
     * Check if we should do something with this image.
     *
     * @param int $attachmentId
     * @return bool
     */
    private function shouldTouchImage(int $attachmentId): bool
    {
        $mimeType = get_post_mime_type($attachmentId);
        switch ($mimeType) {
            case 'image/svg+xml':
                return apply_filters('sb_optimizer_acd_image_resize_should_touch', false, $mimeType, $attachmentId);
        }
        return apply_filters('sb_optimizer_acd_image_resize_should_touch', true, $mimeType, $attachmentId);
    }

    /**
     * @param array $sources
     * @param array $sizeArray
     * @param string $imageSrc
     * @param array $imageMeta
     * @param int $attachmentId
     * @return array
     */
    public function alterSrcsetImageUrls(array $sources, array $sizeArray, string $imageSrc, array $imageMeta, int $attachmentId): array
    {
        if (!$this->shouldTouchImage($attachmentId)) {
            return $sources;
        }
        foreach ($sources as $key => $value) {
            $descriptor = $value['descriptor'] === 'h' ? 'height' : 'width';
            $resizeParameters = $this->defaultImageResizeParameters([
                $descriptor => $value['value']
            ]);
            $sources[$key]['url'] = $this->buildImageUrl($sources[$key]['url'], $resizeParameters);
        }
        return $sources;
    }

    /**
     * Set metadata optimization level.
     *
     * @param null|string $optimizationLevel
     * @return $this
     */
    public function setMetadataOptimizationLevel(?string $optimizationLevel)
    {
        $this->imageMetadataOptimizationLevel = $optimizationLevel;
        return $this;
    }

    /**
     * Set image quality.
     *
     * @param null|int $imageQuality
     * @return $this
     */
    public function setImageQuality(?int $imageQuality)
    {
        $this->imageQuality = $imageQuality;
        return $this;
    }

    /**
     * Alter image src-attribute.
     *
     * @param array $image
     * @param int|null $attachmentId
     * @return mixed
     */
    public function alterSingleImageUrl($image, ?int $attachmentId = null)
    {
        if ($attachmentId && !$this->shouldTouchImage($attachmentId)) {
            return $image;
        }
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
     * Get image quality.
     *
     * @return int
     */
    private function getImageQuality():? int
    {
        if (is_int($this->imageQuality)) {
            if ($this->imageQuality === self::$defaultImageQuality) {
                return null; // Fallback to default value in ACD Worker
            }
            return $this->imageQuality;
        }
        return null; // Fallback to default value in ACD Worker
    }

    /**
     * Get image metadata optimization level.
     *
     * @return string
     */
    private function getMetadataOptimizationLevel():? string
    {
        switch ($this->imageMetadataOptimizationLevel) {
            case 'keep':
            case 'keep_all':
                return 'keep';
            case 'copyright':
            case 'keep_copyright':
                return null; // This is the default value, we don't need to pass
                //return 'copyright';
            case 'no_metadata':
            case 'none':
                return 'none';
        }
        return self::$defaultImageMetadataOptimizationLevel;
    }

    /**
     * @param $additionalParams
     * @return array
     */
    private function defaultImageResizeParameters($additionalParams): array
    {
        $additionalParams = apply_filters('sb_optimizer_acd_image_resize_additional_params', $additionalParams);
        $params = [];
        if ($imageQuality = $this->getImageQuality()) {
            $params['quality'] = $imageQuality;
        }
        if ($metadata = $this->getMetadataOptimizationLevel()) {
            $params['metadata'] = $metadata;
        }
        $defaultParams = apply_filters('sb_optimizer_acd_image_resize_default_params', $params);
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
