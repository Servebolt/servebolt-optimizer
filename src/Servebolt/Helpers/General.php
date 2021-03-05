<?php

namespace Servebolt\Optimizer\Helpers;

/**
 * Display a view, Laravel style.
 *
 * @param $path
 * @param array $arguments
 * @return bool
 */
function view($path, $arguments = []) : bool
{
    $path = str_replace('.', '/', $path);
    $filePath = SERVEBOLT_PSR4_PATH . 'Views/' . $path . '.php';
    if (file_exists($filePath) && is_readable($filePath)) {
        if (array_key_exists('filePath', $arguments)) {
            unset($arguments['filePath']);
        }
        extract($arguments);
        require $filePath;
        return true;
    }
    return false;
}
