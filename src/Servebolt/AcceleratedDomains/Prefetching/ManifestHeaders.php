<?php

namespace Servebolt\Optimizer\AcceleratedDomains\Prefetching;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class ManifestHeaders
 * @package Servebolt\Optimizer\AcceleratedDomains\Prefetching
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
        if ($headerItems = self::getHeaderItems()) {
            foreach ($headerItems as $headerItem) {
                header($headerItem, false);
            }
        }
    }

    /**
     * Prepare array of headers.
     *
     * @return array|null
     */
    public static function getHeaderItems(): ?array
    {
        if ($files = ManifestFilesModel::get()) {
            $headerItems = [];
            foreach ($files as $file) {
                $headerItems[] = 'Link: <' . $file . '>; rel="prefetch"';
            }
            return $headerItems;
        }
        return null;
    }
}
