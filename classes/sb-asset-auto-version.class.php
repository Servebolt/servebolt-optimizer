<?php

/**
 * Class SB_Automatic_Asset_Versioning
 */
class SB_Automatic_Asset_Versioning {

    /**
     * @var string
     */
    private $parameter_name = 'sb-version';

    /**
     * SB_WP_Automatic_Asset_Versioning constructor.
     */
    public function __construct() {
        add_filter('style_loader_tag', [$this, 'style_loader_tag'], 10, 4);
        add_filter('script_loader_tag', [$this, 'script_loader_tag'], 10, 3);
    }

    /**
     * Alter the style-tag to include our cache bust.
     *
     * @param $tag
     * @param $handle
     * @param $href
     * @param $media
     * @return mixed
     */
    public function style_loader_tag($tag, $handle, $href, $media) {
        if ( $this->should_skip($handle, 'style') ) {
            return $tag;
        }
        $href = $this->alter_src($href, $handle);
        return $this->replace_attribute($tag, 'href', $href);
    }

    /**
     * Alter the script-tag to include our cache bust.
     *
     * @param $tag
     * @param $handle
     * @param $src
     * @return mixed
     */
    public function script_loader_tag($tag, $handle, $src) {
        if ( $this->should_skip($handle, 'script') ) {
            return $tag;
        }
        $src = $this->alter_src($src, $handle);
        return $this->replace_attribute($tag, 'src', $src);
    }

    /**
     * Replace attribute value.
     *
     * @param $tag
     * @param $attribute
     * @param $value
     * @return string|string[]|null
     */
    private function replace_attribute($tag, $attribute, $value) {
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
    private function should_skip($handle, $type) {
        if ( ! apply_filters('sb_optimizer_add_version_parameter_to_asset_src', true) ) return true;
        if ( ! apply_filters('sb_optimizer_add_version_parameter_to_' . $type . '_src', true) ) return true;
        if ( ! apply_filters('sb_optimizer_add_version_parameter_to_' . $type . '_src_' . $handle, true) ) return true;
        return false;
    }

    /**
     * Alter the src-attribute URL by adding a version parameter (for automatic cache busting).
     *
     * @param $src
     * @param $handle
     * @return string
     */
    private function alter_src($src, $handle) {
        $version_parameter = $this->generate_version_parameter($src, $handle);
        if ( $version_parameter ) {
            $has_query = parse_url($src, PHP_URL_QUERY);
            return $src . ( $has_query ? '&' : '?') . $version_parameter;
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
    private function generate_version_parameter($src, $handle) {
        $file_path = $this->get_asset_path($src, $handle);
        if ( ! file_exists($file_path) ) {
            return false;
        }
        try {
            return apply_filters('sb_optimizer_version_parameter_name', $this->parameter_name, $file_path) . '=' . filemtime($file_path);
        } catch (Exception $e) {
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
    private function get_asset_path($src, $handle) {
        $parsed_url = parse_url($src);
        // Use these filters too correct any mistakes related to the asset path
        $base_path = apply_filters('sb_optimizer_asset_base_path', rtrim(ABSPATH, '/'), $src, $parsed_url);
        $src = $base_path . '/' . apply_filters('sb_optimizer_asset_parsed_url_path', ltrim($parsed_url['path'], '/'));
        $src = apply_filters('sb_optimizer_asset_url_to_path_conversion', $src, $parsed_url);
        $src = apply_filters('sb_optimizer_asset_url_to_path_conversion_' . $handle, $src, $parsed_url);
        return $src;
    }

}
new SB_Automatic_Asset_Versioning;
