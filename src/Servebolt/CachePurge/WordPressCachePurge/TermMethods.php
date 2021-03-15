<?php

namespace Servebolt\Optimizer\CachePurge\WordPressCachePurge;

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
     * @param string $taxonomy
     * @return bool
     */
    public static function purgeTermCache(int $termId, string $taxonomy): bool
    {
        // TODO: Add queue handling here
        $urlsToPurge = self::getUrlsToPurgeByTermId($termId, $taxonomy);
        $cachePurgeDriver = CachePurge::getInstance();
        return $cachePurgeDriver->purgeByUrls($urlsToPurge);
    }
}
