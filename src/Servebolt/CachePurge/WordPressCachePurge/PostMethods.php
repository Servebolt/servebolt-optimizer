<?php

namespace Servebolt\Optimizer\CachePurge\WordPressCachePurge;

use WP_Error;

trait PostMethods
{

    /**
     * Get all the URLs to purge for a given post.
     *
     * @param int $postId
     * @return array
     */
    private static function getUrlsToPurgeByPostId(int $postId): array
    {
        return sb_cf_cache_purge_object(
            $postId,
            'post'
        )->get_urls();
    }

    /**
     * Purge post cache by post Id.
     *
     * @param int $postId
     * @param bool $returnWpError
     * @return bool|WP_Error
     */
    public static function purgePostCache(int $postId, bool $returnWpError = false): bool
    {

        // If this is just a revision, don't purge anything.
        if ( ! $postId || wp_is_post_revision( $postId ) ) {
            return false;
        }

        // TODO: Add queue handling here
        $urlsToPurge = self::getUrlsToPurgeByPostId($postId);
        $cachePurgeDriver = CachePurge::getInstance();
        return $cachePurgeDriver->purgeByUrls($urlsToPurge);
    }
}
