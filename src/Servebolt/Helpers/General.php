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
