<?php

namespace Servebolt\Optimizer\Compatibility\YoastPremium;

use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Exception;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class RedirectCachePurge
 * @package Servebolt\Optimizer\Compatibility\Yoast
 */
class RedirectCachePurge
{
    /**
     * RedirectCachePurge constructor.
     */
    public function __construct()
    {
        add_action('Yoast\WP\SEO\redirects_modified', [$this, 'purgeCacheOnRedirectModification'], 10, 2);
    }

    /**
     * Purge cache for URLs that are redirected by Yoast SEO Premium.
     *
     * @param string $origin The from-redirect URL.
     * @param string $target The to-redirect URL.
     */
    public function purgeCacheOnRedirectModification($origin, $target): void
    {
        if ($origin) {
            try {
                WordPressCachePurge::purgeByUrl($origin, false);
            } catch (Exception $e) {}
        }
        if ($target) {
            try {
                WordPressCachePurge::purgeByUrl($target, false);
            } catch (Exception $e) {}

        }
    }
}
