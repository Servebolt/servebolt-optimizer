<?php

namespace Servebolt\Optimizer\AcceleratedDomains;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class AcceleratedDomainsHeaders
 * @package Servebolt\Optimizer\AcceleratedDomains
 */
class AcceleratedDomainsHeaders
{

    /**
     * @var int Default TTL for ACD cache.
     */
    private $defaultTtl = 43200;

    /**
     * AcceleratedDomainsHeaders constructor.
     */
    public function __construct()
    {
        add_action('wp_headers', [$this, 'addAcdHeaders']);
    }

    /**
     * Set TTL conditionally based on the FullPageCache-class.
     */
    private function handleTtlHeaders(): void
    {
        $headerKey = 'x-acd-ttl';
        add_action('sb_optimizer_fpc_no_cache_headers', function ($fpc) use ($headerKey) {
            $fpc->header($headerKey, 'no-cache');
        });
        add_action('sb_optimizer_fpc_cache_headers', function ($fpc) use ($headerKey) {
            $fpc->header($headerKey, $this->defaultTtl);
        });
    }

    /**
     * Add headers to control the ACD-feature.
     *
     * @param $headers
     * @return array
     */
    public function addAcdHeaders($headers): array
    {
        if (AcceleratedDomains::isActive()) {
            $this->handleTtlHeaders();
            $headers['x-acd-cms'] = 'wordpress';
            if (AcceleratedDomains::htmlMinifyIsActive()) {
                $headers['x-acd-minify'] = true;
            }
        }
        return $headers;
    }
}
