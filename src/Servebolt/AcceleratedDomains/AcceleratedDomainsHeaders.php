<?php

namespace Servebolt\Optimizer\AcceleratedDomains;

use Servebolt\Optimizer\FullPageCache\CacheTtl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class AcceleratedDomainsHeaders
 * @package Servebolt\Optimizer\AcceleratedDomains
 */
class AcceleratedDomainsHeaders
{

    /**
     * @var int Default TTL for ACD/CDN cache.
     */
    private $defaultTtl = 86400;

    /**
     * The header name used to specify TTL for ACD cache.
     *
     * @var string
     */
    private $ttlHeaderkeyForAcd = 'x-acd-ttl';

    /**
     * The header name used to specify TTL for CDN cache.
     *
     * @var string
     */
    private $ttlHeaderkeyForCdnControl = 'CDN-Cache-Control';

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
     * Get the cache TTL.
     *
     * @param string|null $objectType
     * @param string|null $objectIdentifier
     * @return int
     */
    private function getTtl(?string $objectType = null, ?string $objectIdentifier = null): int
    {
        // Conditional TTL
        if (
            CacheTtl::isActive()
            && $objectType
            && $objectIdentifier
        ) {
            switch ($objectType) {
                case 'post-type':
                    $ttl = CacheTtl::getTtlByPostType($objectIdentifier);
                    break;
                case 'taxonomy':
                    $ttl = CacheTtl::getTtlByTaxonomy($objectIdentifier);
                    break;
            }
            if (isset($ttl) && is_numeric($ttl)) {
                return $ttl;
            }
        }
        return $this->defaultTtl;
    }

    /**
     * Set ACD TTL conditionally using on the FullPageCache-class.
     */
    private function addAcdTtlHeaders(): void
    {
        add_action('sb_optimizer_fpc_cache_headers', function ($cacheObject, $queriedObject) {
            if ($queriedObject) {
                $ttl = $this->getTtl($queriedObject['objectType'], $queriedObject['objectId']);
            } else {
                $ttl = $this->getTtl();
            }
            $cacheObject->header($this->ttlHeaderkeyForAcd, $ttl);
            $cacheObject->header($this->ttlHeaderkeyForCdnControl, 'max-age=' . $ttl);
        }, 10, 2);
        add_action('sb_optimizer_fpc_no_cache_headers', function ($cacheObject) {
            $cacheObject->header($this->ttlHeaderkeyForAcd, 'no-cache');
            $cacheObject->header($this->ttlHeaderkeyForCdnControl, 'max-age=0,no-cache');
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
