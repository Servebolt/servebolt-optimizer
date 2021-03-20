<?php

namespace Servebolt\Optimizer\CachePurge\WordPressCachePurge;

use Servebolt\Optimizer\CachePurge\CachePurge as CachePurgeDriver;
use Servebolt\Optimizer\CachePurge\PurgeObject\PurgeObject;

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
    private static function getUrlsToPurgeByTermId(int $termId, string $taxonomySlug): array
    {
        $purgeObject = new PurgeObject(
            $termId,
            'term',
            ['taxonomy_slug' => $taxonomySlug]
        );
        return $purgeObject->get_urls();
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
