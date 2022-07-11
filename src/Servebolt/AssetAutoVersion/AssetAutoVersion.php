<?php

namespace Servebolt\Optimizer\AssetAutoVersion;

if (!defined('ABSPATH')) exit;

use Throwable;

/**
 * Class AssetAutoVersion
 */
class AssetAutoVersion {

    /**
     * @var string
     */
    private $parameterName = 'sb-version';

    public static function init()
    {
        new self;
    }

    /**
     * AssetAutoVersion constructor.
     */
    public function __construct()
    {
        add_filter('style_loader_tag', [$this, 'styleLoaderTag'], 10, 4);
        add_filter('script_loader_tag', [$this, 'scriptLoaderTag'], 10, 3);
    }

    /**
     * Alter the style-tag to include our cache bust.
     *
     * @param $tag
     * @param $handle
     * @param $href
     * @param $media
     * @return string
     */
    public function styleLoaderTag($tag, $handle, $href, $media): string
    {
        if ($this->shouldSkip($handle, 'style')) {
            return $tag;
        }
        $href = $this->alterSrc($href, $handle);
        return $this->replaceAttribute($tag, 'href', $href);
    }

    /**
     * Alter the script-tag to include our cache bust.
     *
     * @param $tag
     * @param $handle
     * @param $src
     * @return string
     */
    public function scriptLoaderTag($tag, $handle, $src): string
    {
        if ($this->shouldSkip($handle, 'script')) {
            return $tag;
        }
        $src = $this->alterSrc($src, $handle);
        return $this->replaceAttribute($tag, 'src', $src);
    }

    /**
     * Replace attribute value.
     *
     * @param $tag
     * @param $attribute
     * @param $value
     * @return string|string[]|null
     */
    private function replaceAttribute($tag, $attribute, $value): string
    {
        $pattern = '/' . $attribute . '=["\']([^"\']+)["\']/';
        $replacement = $attribute . '=\'' . $value . '\'';
        return preg_replace($pattern, $replacement, $tag);
    }

    /**
     * Check whether we should alter the src-attribute.
     *
     * @param $handle
     * @param $type
     * @return bool
     */
    private function shouldSkip($handle, $type): bool
    {
        if (!apply_filters('sb_optimizer_add_version_parameter_to_asset_src', true)) {
            return true;
        }
        if (!apply_filters('sb_optimizer_add_version_parameter_to_' . $type . '_src', true)) {
            return true;
        }
        if (!apply_filters('sb_optimizer_add_version_parameter_to_' . $type . '_src_' . $handle, true)) {
            return true;
        }
        return false;
    }

    /**
     * Alter the src-attribute URL by adding a version parameter (for automatic cache busting).
     *
     * @param $src
     * @param $handle
     * @return string
     */
    private function alterSrc($src, $handle): string
    {
        if ($versionParameter = $this->generateVersionParameter($src, $handle)) {
            $hasQuery = parse_url($src, PHP_URL_QUERY);
            return $src . ($hasQuery ? '&' : '?') . $versionParameter;
        }
        return $src;

    }

    /**
     * Generate the version parameter to be added to the src-attribute URL.
     *
     * @param $src
     * @param $handle
     * @return bool|string
     */
    private function generateVersionParameter($src, $handle)
    {
        $filePath = $this->getAssetPath($src, $handle);
        if (!file_exists($filePath)) {
            return false;
        }
        try {
            $parameterName = apply_filters('sb_optimizer_version_parameter_name', $this->parameterName, $filePath);
            $parameterValue = apply_filters('sb_optimizer_version_parameter_value', filemtime($filePath), $parameterName, $filePath);
            return $parameterName . '=' . $parameterValue; // Store the "filemtime" in a transient to remove disk I/O on every page load?
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get the path of the asset file.
     *
     * @param $src
     * @param $handle
     * @return string
     */
    private function getAssetPath($src, $handle): string
    {
        $parsedUrl = parse_url($src);
        // Use these filters too correct any mistakes related to the asset path
        $basePath = apply_filters('sb_optimizer_asset_base_path', rtrim(ABSPATH, '/'), $src, $parsedUrl);
        $path = $basePath . '/' . apply_filters('sb_optimizer_asset_parsed_url_path', ltrim($parsedUrl['path'], '/'));
        $path = apply_filters('sb_optimizer_asset_url_to_path_conversion', $path, $parsedUrl);
        $path = apply_filters('sb_optimizer_asset_url_to_path_conversion_' . $handle, $path, $parsedUrl);
        return $path;
    }

}
