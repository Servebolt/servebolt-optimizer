<?php

namespace Servebolt\Optimizer\Prefetching;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class ManifestHeaders
 * @package Servebolt\Optimizer\Prefetching
 */
class ManifestHeaders
{
    /**
     * Print manifest files headers.
     */
    public static function printManifestHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        if ($files = ManifestFilesModel::get()) {
            $prefetchFiles = [];
            foreach ($files as $file) {
                $prefetchFiles[] = '<' . $file . '>; rel="prefetch"';
            }
            header('Link: ' . implode(', ', $prefetchFiles));
        }
    }
}
