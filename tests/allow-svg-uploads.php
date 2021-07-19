<?php

namespace Servebolt;

/**
 * This code was borrowed from the plugin SVG Support (https://wordpress.org/plugins/svg-support/).
 * Another alternative would be to implement the installation and activation of this plugin during test environment setup, but this did the trick and was quick and easy.
 */

/**
 * Class AllowSvgUploads
 * @package Servebolt
 */
class AllowSvgUploads
{
    /**
     * Allow SVG-uploads.
     *
     * @return bool
     */
    public static function allow(): bool
    {
        if (function_exists('add_filter')) {
            if (!has_filter('wp_check_filetype_and_ext', __CLASS__ . '::svgUploadCheck')) {
                add_filter('wp_check_filetype_and_ext', __CLASS__ . '::svgUploadCheck', 10, 4);
            }
            if (!has_filter('upload_mimes', __CLASS__ .  '::svgUploadMimes')) {
                add_filter('upload_mimes', __CLASS__ .  '::svgUploadMimes', 99);
            }
            if (!has_filter('wp_check_filetype_and_ext', __CLASS__ . '::svgAllowSvgUpload')) {
                add_filter('wp_check_filetype_and_ext', __CLASS__ . '::svgAllowSvgUpload', 10, 4);
            }
        } else {
            tests_add_filter('wp_check_filetype_and_ext', __CLASS__ . '::svgUploadCheck', 10, 4);
            tests_add_filter('upload_mimes', __CLASS__ .  '::svgUploadMimes', 99);
            tests_add_filter('wp_check_filetype_and_ext', __CLASS__ . '::svgAllowSvgUpload', 10, 4);
        }
        return true;
    }

    /**
     * Disallow SVG-uploads.
     *
     * @return bool
     */
    public static function disallow(): bool
    {
        if (!function_exists('remove_filter')) {
            return false;
        }
        remove_filter('wp_check_filetype_and_ext', __CLASS__ . '::svgUploadCheck', 10, 4);
        remove_filter('upload_mimes', __CLASS__ .  '::svgUploadMimes', 99);
        remove_filter('wp_check_filetype_and_ext', __CLASS__ . '::svgAllowSvgUpload', 10, 4);
        return true;
    }

    /**
     * Add SVG MIME-types.
     *
     * @param array $mimes
     * @return array
     */
    public static function svgUploadMimes(array $mimes = []): array
    {
        $mimes['svg'] = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';
        return $mimes;
    }

    /**
     * Check SVG MIME-types.
     *
     * @param $checked
     * @param $file
     * @param $filename
     * @param $mimes
     * @return array
     */
    public static function svgUploadCheck($checked, $file, $filename, $mimes): array
    {
        if (!$checked['type']) {
            $check_filetype = wp_check_filetype($filename, $mimes);
            $ext = $check_filetype['ext'];
            $type = $check_filetype['type'];
            $proper_filename = $filename;
            if ($type && 0 === strpos($type, 'image/') && $ext !== 'svg') {
                $ext = $type = false;
            }
            $checked = compact('ext','type', 'proper_filename');
        }
        return $checked;
    }

    /**
     * Mime Check fix for WP 4.7.1 / 4.7.2
     *
     * Fixes uploads for these 2 version of WordPress.
     * Issue was fixed in 4.7.3 core.
     *
     * @param $data
     * @param $file
     * @param $filename
     * @param $mimes
     * @return array
     */
    public static function svgAllowSvgUpload($data, $file, $filename, $mimes): array
    {
        global $wp_version;
        if ($wp_version !== '4.7.1' || $wp_version !== '4.7.2') {
            return $data;
        }
        $filetype = wp_check_filetype($filename, $mimes);
        return [
            'ext' => $filetype['ext'],
            'type' => $filetype['type'],
            'proper_filename' => $data['proper_filename']
        ];

    }
}
