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
     * AcceleratedDomainsHeaders constructor.
     */
    public function __construct()
    {
        add_action('wp_headers', [$this, 'addAcdHeaders']);
    }

    /**
     * @param $headers
     * @return array
     */
    public function addAcdHeaders($headers): array
    {
        if (AcceleratedDomains::isActive()) {
            $headers['x-acd-ttl'] = apply_filters('sb_optimizer_acd_ttl', 43200);
            $headers['x-acd-cms'] = 'wordpress';
            if (AcceleratedDomains::htmlMinifyIsActive()) {
                $headers['x-acd-minify'] = true;
            }
        }
        return $headers;
    }
}
