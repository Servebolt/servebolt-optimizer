<?php

namespace Servebolt\Optimizer\Helpers;

/**
 * Display a view, Laravel style.
 *
 * @param string $templatePath
 * @param array $arguments
 * @param bool $echo
 * @return string|null
 */
function view(string $templatePath, $arguments = [], $echo = true): ?string
{
    $templatePath = str_replace('.', '/', $templatePath);
    $suffix = '.php';
    $basePath = SERVEBOLT_PSR4_PATH . 'Views/';
    $filePath = $basePath . $templatePath . $suffix;
    if (file_exists($filePath) && is_readable($filePath)) {
        extract($arguments, EXTR_SKIP);
        if (!$echo) {
            ob_start();
        }
        include $filePath;
        if (!$echo) {
            $output = ob_get_contents();
            ob_end_clean();
            return $output;
        }
    }
    return null;
}

/**
 * Get a link to the Servebolt admin panel.
 *
 * @return string
 */
function sbGetAdminUrl() :string
{
    if (!function_exists('get_home_path')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }
    $webRootPath = sb_is_dev_debug() ? '/kunder/serveb_1234/custom_4321/public' : get_home_path();
    if (preg_match("@kunder/[a-z_0-9]+/[a-z_]+(\d+)/@", $webRootPath, $matches) && isset($matches[1])) {
        return 'https://admin.servebolt.com/siteredirect/?site='. $matches[1];
    }
    return false;
}
