<?php

namespace Servebolt\Optimizer\CachePurge\WordPressCachePurge;

use Servebolt\Optimizer\CachePurge\CachePurge as CachePurgeDriver;

/**
 * Trait TermMethods
 * @package Servebolt\Optimizer\CachePurge\WordPressCachePurge
 */
trait TermMethods
{

    /**
     * Get all the URLs to purge for a given term.
     *
     * @param int $termId
     * @param string $taxonomySlug
     * @return bool|mixed
     */
    private function getUrlsToPurgeByTermId(int $termId, string $taxonomySlug): array
    {
        return sb_cf_cache_purge_object(
            $termId,
            'term',
            ['taxonomy_slug' => $taxonomySlug]
        )->get_urls();
    }

    /**
     * Purge cache for term.
     *
     * @param int $termId
     * @param string $taxonomySlug
     * @return bool
     */
    public static function purgeTermCache(int $termId, string $taxonomySlug): bool
    {
        // TODO: Add queue handling here
        $urlsToPurge = self::getUrlsToPurgeByTermId($termId, $taxonomySlug);
        $cachePurgeDriver = CachePurgeDriver::getInstance();
        return $cachePurgeDriver->purgeByUrls($urlsToPurge);
    }
}
