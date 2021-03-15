<?php

namespace Servebolt\Optimizer\CachePurge\WordPressCachePurge;

/**
 * Class WordPressCachePurge
 * @package Servebolt\Optimizer\CachePurge
 */
class WordPressCachePurge
{

    use PostMethods, TermMethods;

    /**
     * Purge cache by URL.
     *
     * @param string $url
     * @return bool
     */
    public static function purgeByUrl(string $url): bool
    {
        $postId = url_to_postid($url);
        if ($postId) {
            return self::purgePostCache($postId);
        } else {
            // TODO: Add queue handling here
            $cachePurgeDriver = CachePurge::getInstance();
            return $cachePurgeDriver->purgeByUrl($url);
        }
    }

    /**
     * Alias for method "purgeTermCache".
     *
     * @param int $termId
     * @return bool
     */
    public function purgeByTermId(int $termId): bool
    {
        return self::purgeTermCache($termId);
    }

    /**
     * Alias for method "purgePostCache".
     *
     * @param int $postId
     * @return bool
     */
    public static function purgeByPostId(int $postId): bool
    {
        return self::purgePostCache($postId);
    }

    /**
     * Purge all cache on the site.
     *
     * @return bool
     */
    public function purgeAll(): bool
    {
        // TODO: Add queue handling here
        $cachePurgeDriver = CachePurge::getInstance();
        return $cachePurgeDriver->purgeAll();
    }

    /**
     * Purge all cache in network (only for multisites).
     *
     * @return bool
     */
    public function purgeAllNetwork(): bool
    {
        if (!is_multisite()) {
            return false;
        }
        // TODO: Add support for purge all in multisite context
        // TODO: Add queue handling here
    }

}
