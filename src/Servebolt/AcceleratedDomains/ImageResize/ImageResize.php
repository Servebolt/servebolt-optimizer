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
    public static $defaultImageMetadataOptimizationLevel = 'copyright';

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
     * Check if we should do something with this image based on MIME-type or file extension.
     *
     * @param int $attachmentId
     * @return bool
     */
    private function shouldTouchImage(int $attachmentId): bool
    {
        $mimeType = get_post_mime_type($attachmentId);
        switch ($mimeType) {
            case 'image/svg+xml':
                return apply_filters('sb_optimizer_acd_image_resize_should_touch_svg', false, $mimeType, $attachmentId);
        }
        $fileExtension = $this->shouldTouchImageBasedOnFileExtension($attachmentId);
        if ($fileExtension !== true) {
            return apply_filters('sb_optimizer_acd_image_resize_should_touch_by_file_extension', false, $fileExtension, $mimeType, $attachmentId);
        }
        return apply_filters('sb_optimizer_acd_image_resize_should_touch', true, $mimeType, $attachmentId);
    }

    /**
     * Check if we should do something with this image based on file extension.
     *
     * @param $attachmentId
     * @return bool|string
     */
    private function shouldTouchImageBasedOnFileExtension($attachmentId)
    {
        $filePath = get_attached_file($attachmentId, true);
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
        switch ($fileExtension) {
            case 'svg':
                return $fileExtension;
        }
        return true;
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
     * @param int|string|null $attachmentId
     * @return mixed
     */
    public function alterSingleImageUrl($image, $attachmentId = null)
    {
        $attachmentId = (int) $attachmentId;
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
        // Set a useable width if the file was not readable to get the sizes.
        if(apply_filters('sb_optimizer_acd_image_resize_force_add_width', false, $width, $height)) {
            global $_wp_additional_image_sizes;
            $width = $_wp_additional_image_sizes['post_thumbnail']['width'];
        }

        $additionalParams['width'] = $width;

        // Set max height
        $maxHeight = $this->maxHeight();
        if ($maxHeight && $height > $maxHeight) {
            $height = $this->maxHeight();
            $additionalParams['height'] = $height; // We only want to set height it is bigger than max height
        }
        if (apply_filters('sb_optimizer_acd_image_resize_force_add_height', false)) {
            $additionalParams['height'] = $height;
        }

        return $this->defaultImageResizeParameters($additionalParams);
    }

    /**
     * 
     */
    public function correctPotentialBadImages($forceThumbnailMinimumWidth, $width) {
        error_log(get_option('sb_optimizer_acd_image_resize_force_add_width', false) ? 'trying to set width' : 'not trying to not width');
        if($width < 10 && get_option('sb_optimizer_acd_image_resize_force_add_width', false)) return true;
        return $forceThumbnailMinimumWidth;
    }

    public function correctPotentialBadImagesHook(): void
    {
        error_log('loading filter');
        add_filter('acd_image_resize_force_thumbnail_minimum_width', [$this, 'correctPotentialBadImages'], 10, 3);
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
     * @return null|string
     */
    public function getMetadataOptimizationLevel(): ?string
    {
        return self::determineMetadataOptimizationLevel($this->imageMetadataOptimizationLevel);
    }

    /**
     * Determine image metadata optimization level.
     *
     * @param string|null $imageMetadataOptimizationLevel
     * @param bool $returnNullOnDefault
     * @return string|null
     */
    public static function determineMetadataOptimizationLevel(?string $imageMetadataOptimizationLevel = null, bool $returnNullOnDefault = true):? string
    {
        switch ($imageMetadataOptimizationLevel) {
            case 'keep':
                $level = 'keep';
                break;
            case 'none':
                $level = 'none';
                break;
            case 'copyright':
            default:
                $level = 'copyright';
                break;
        }
        if ($level == self::$defaultImageMetadataOptimizationLevel) {
            return $returnNullOnDefault ? null : self::$defaultImageMetadataOptimizationLevel;
        }
        return $level;
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
        $pathPrefix = '/' . $this->cgiPrefix . '/' . $this->version;
        return apply_filters('sb_optimizer_acd_image_resize_cgi_prefix', $pathPrefix, $this->cgiPrefix, $this->version);
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

    /**
     * Use regex to scan HTML for images, pull out the SRC (image url) and replace it
     * with one for the Acelerated Domains/Servebolt CDN
     * 
     * @since 3.5.6
     * @param string $content html string
     * @return string $content html string
     * 
     */
    public function regexOperation($content)
    {
        if ( preg_match_all( '#<(?P<tag>img)[^<]*?(?:>[\s\S]*?<\/(?P=tag)>|\s*\/>)#', $content, $matches ) )
        {
            foreach ( $matches[0] as $match )
            {
                preg_match( '/ src="([^"]+)"/', $match, $src );
                // If it fails, is not from domain, is and SVG or is already transformed, continue to the next.
                if( $src === false ||
                    strpos($src[1], content_url() ) === false ||
                    strpos($src[1], '.svg') === true ||
                    strpos($src[1], $this->cgiPrefix."/".$this->version) === true ) continue;

                preg_match( '/ width="([0-9]+)"/',  $match, $width  );
                // default to 500px wide if the image does not have a width
                $image_width = (isset($width[1])) ? $width[1] : 500;
                // Build the real url as needed.
                $newsrc = $this->buildImageUrl($src[1], ['width' => $image_width ]);
                // Replace src="content" with src="new content", double replace keeps src=""
                $content = str_replace($src[0], str_replace($src[1], $newsrc, $src[0]), $content);
            }
        }
        return $content;
    }

    /**
     * First checks if in admin or imageless HTML content.
     * If not perfoms a regex operation on the content.
     * 
     * @since 3.5.6
     * @param string $content html string
     * @return string $content html string 
     */
    public function alterImagesIntheContent($content)
    {
        // Exit early so it does not break editing.
        if( is_admin() ) return $content;
        // If no images, return instantly.
        if(strpos($content, '<img') === false) return $content;
        // Front end only replace image URLs.
        return $this->regexOperation($content);
    }

}
