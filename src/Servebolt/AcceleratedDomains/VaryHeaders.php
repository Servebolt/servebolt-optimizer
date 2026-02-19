<?php

namespace Servebolt\Optimizer\AcceleratedDomains;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\AcceleratedDomains\AcceleratedDomains;
use Servebolt\Optimizer\AcceleratedDomains\VaryHeadersConfig;

/**
 * Handle Vary headers for Accelerated Domains.
 */
class VaryHeaders
{
    use Singleton;

    /**
     * Alias for "getInstance".
     */
    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * VaryHeaders constructor.
     */
    public function __construct()
    {
        add_filter('wp_headers', [$this, 'addVaryHeaders']);
    }

    /**
     * Add a Vary header when Accelerated Domains is active.
     *
     * @param array $headers
     * @return array
     */
    public function addVaryHeaders(array $headers): array
    {
        if (!AcceleratedDomains::isActive()) {
            return $headers;
        }

        $varyHeaders = $this->getHeaderNamesForVary();
        if (empty($varyHeaders)) {
            return $headers;
        }

        $existing = [];
        if (!empty($headers['Vary'])) {
            $existing = array_map('trim', explode(',', $headers['Vary']));
        }

        $headers['Vary'] = implode(', ', array_unique(array_filter(array_merge($existing, $varyHeaders))));
        return $headers;
    }

    /**
     * Determine which header names should be added to the Vary header.
     *
     * @return array
     */
    private function getHeaderNamesForVary(): array
    {
        $selection = VaryHeadersConfig::selection();
        return array_values(array_intersect_key(VaryHeadersConfig::availableHeaders(), array_flip($selection)));
    }
}
