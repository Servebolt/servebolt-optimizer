<?php

namespace Servebolt;

/**
 * Add SVG MIME-types.
 *
 * @param array $mimes
 * @return array
 */
function svgsUploadMimes(array $mimes = []): array
{
    $mimes['svg'] = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';
    return $mimes;
}
tests_add_filter('upload_mimes', __NAMESPACE__ .  '\\svgsUploadMimes', 99);

/**
 * Check SVG MIME-types.
 *
 * @param $checked
 * @param $file
 * @param $filename
 * @param $mimes
 * @return array
 */
function svgsUploadCheck($checked, $file, $filename, $mimes): array
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
tests_add_filter('wp_check_filetype_and_ext', __NAMESPACE__ . '\\svgsUploadCheck', 10, 4);

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
function svgsAllowSvgUpload($data, $file, $filename, $mimes): array
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
tests_add_filter('wp_check_filetype_and_ext', __NAMESPACE__ . '\\svgsAllowSvgUpload', 10, 4);
