<?php

namespace Servebolt\Optimizer\FullPageCache;

use function Servebolt\Optimizer\Helpers\getBlogOption;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\smartUpdateOption;

/**
 * Class CachePostExclusion
 * @package Servebolt\Optimizer\FullPageCache
 */
class CachePostExclusion
{
    /**
     * Cache the post ids to exclude from cache.
     *
     * @var null
     */
    private static $idsToExcludeCache = null;

    /**
     * Ids of posts to exclude from cache.
     *
     * @param null|int $blogId
     *
     * @return array|mixed|void|null
     */
    public static function getIdsToExcludeFromCache(?int $blogId = null)
    {
        if (is_numeric($blogId)) {
            $idsToExclude = getBlogOption($blogId, 'fpc_exclude');
            if (!is_array($idsToExclude)) {
                $idsToExclude = [];
            }
            return $idsToExclude;
        }
        if (is_null(self::$idsToExcludeCache)) {
            $idsToExclude = getOption( 'fpc_exclude');
            if (!is_array($idsToExclude)) {
                $idsToExclude = [];
            }
            self::$idsToExcludeCache = $idsToExclude;
        }
        return self::$idsToExcludeCache;
    }

    /**
     * Exclude post from FPC.
     *
     * @param int $postId
     * @param null|int $blogId
     *
     * @return bool
     */
    public static function excludePostFromCache($postId, ?int $blogId = null)
    {
        return self::excludePostsFromCache([$postId], $blogId);
    }

    /**
     * Exclude posts from FPC.
     *
     * @param $posts
     * @param null|int $blogId
     *
     * @return bool
     */
    public static function excludePostsFromCache($posts, ?int $blogId = null)
    {
        $alreadyExcluded = self::getIdsToExcludeFromCache($blogId) ?: [];
        foreach($posts as $postId) {
            if (!in_array($postId, $alreadyExcluded)) {
                $alreadyExcluded[] = $postId;
            }
        }
        return self::setIdsToExcludeFromCache($alreadyExcluded, $blogId);
    }

    /**
     * Set posts to exclude from cache.
     *
     * @param $idsToExclude
     * @param null|int $blogId
     *
     * @return bool
     */
    public static function setIdsToExcludeFromCache($idsToExclude, ?int $blogId = null)
    {
        self::$idsToExcludeCache = $idsToExclude;
        return smartUpdateOption($blogId, 'fpc_exclude', $idsToExclude);
    }

    /**
     * Check if we should exclude post from cache.
     *
     * @param $postId
     *
     * @return bool
     */
    public static function shouldExcludePostFromCache($postId)
    {
        $idsToExclude = self::getIdsToExcludeFromCache();
        return is_array($idsToExclude) && in_array($postId, $idsToExclude);
    }

    /**
     * Clear all posts from the cache exclusion.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function clearExcludePostFromCache(?int $blogId = null)
    {
        return self::setIdsToExcludeFromCache([]);
    }
}
