<?php

namespace Servebolt\Optimizer\CachePurge\WordPressCachePurge;

use WP_Error;

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
     * @param bool $returnWpError
     * @return bool
     */
    public static function purgeTermCache(int $termId, string $taxonomySlug, bool $returnWpError = false): bool
    {
        // TODO: Add queue handling here
        $urlsToPurge = self::getUrlsToPurgeByTermId($termId, $taxonomySlug);
        $cachePurgeDriver = CachePurge::getInstance();
        return $cachePurgeDriver->purgeByUrls($urlsToPurge);
    }
}
