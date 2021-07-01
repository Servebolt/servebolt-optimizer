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
    private $defaultTtl = 86000;

    /**
     * @var string The header name used to specify TTL for ACD cache.
     */
    private $ttlHeaderkey = 'x-acd-ttl';

    /**
     * AcceleratedDomainsHeaders constructor.
     */
    public function __construct()
    {
        if (AcceleratedDomains::isActive()) {
            add_action('wp_headers', [$this, 'addAcdHeaders']);
            $this->addAcdTtlHeaders();
        }
    }

    /**
     * Set ACD TTL conditionally using on the FullPageCache-class.
     */
    private function addAcdTtlHeaders(): void
    {
        add_action('sb_optimizer_fpc_no_cache_headers', function ($fpc) {
            $fpc->header($this->ttlHeaderkey, 'no-cache');
            $fpc->header('CDN-Cache-Control', 'max-age=0,no-cache');
        });
        add_action('sb_optimizer_fpc_cache_headers', function ($fpc) {
            $fpc->header($this->ttlHeaderkey, $this->defaultTtl);
            $fpc->header('CDN-Cache-Control', 'max-age=' . $this->defaultTtl);
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
        $headers['x-acd-cms'] = 'wordpress';
        /*
        if (AcceleratedDomains::htmlMinifyIsActive()) {
            $headers['x-acd-minify'] = true;
        }
        */
        return $headers;
    }
}
