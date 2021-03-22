<?php

namespace Servebolt\Optimizer\CachePurge\WordPressCachePurge;

use Servebolt\Optimizer\CachePurge\CachePurge as CachePurgeDriver;

/**
 * Class WordPressCachePurge
 *
 * This class acts as a layer between the post/term to be purged and the cache purge driver.
 *
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
    public static function purgeByUrl(string $url)
    {
        if ($postId = url_to_postid($url)) {
            return self::purgePostCache($postId);
        } else {
            // TODO: Add queue handling here
            $cachePurgeDriver = CachePurgeDriver::getInstance();
            return $cachePurgeDriver->purgeByUrl($url);
        }
    }

    /**
     * Alias for method "purgeTermCache".
     *
     * @param int $termId
     * @param string $taxonomySlug
     * @param bool $returnWpError
     * @return bool
     */
    public static function purgeByTermId(int $termId, string $taxonomySlug, bool $returnWpError = false)
    {
        return self::purgeTermCache($termId, $taxonomySlug, $returnWpError);
    }

    /**
     * Alias for method "purgePostCache".
     *
     * @param int $postId
     * @param bool $returnWpError
     * @return bool
     */
    public static function purgeByPostId(int $postId, bool $returnWpError = false)
    {
        return self::purgePostCache($postId, $returnWpError);
    }

    /**
     * Purge all cache on the site.
     *
     * @param bool $returnWpError
     * @return bool
     */
    public static function purgeAll(bool $returnWpError = false)
    {
        // TODO: Add queue handling here
        $cachePurgeDriver = CachePurgeDriver::getInstance();
        return $cachePurgeDriver->purgeAll();
    }

    /**
     * Purge all cache for given site (only for multisites).
     *
     * @param int $blogId
     * @param bool $returnWpError
     * @return bool
     */
    public static function purgeAllByBlogId(int $blogId, bool $returnWpError = false)
    {
        if (!is_multisite()) {
            return false;
        }
        // TODO: Add switch to blog-logic here
        // TODO: Add queue handling here
        $cachePurgeDriver = CachePurgeDriver::getInstance();
        return $cachePurgeDriver->purgeAll();

    }

    /**
     * Purge all cache in network (only for multisites).
     *
     * @param bool $returnWpError
     * @return bool
     */
    public static function purgeAllNetwork(bool $returnWpError = false)
    {
        if (!is_multisite()) {
            return false;
        }
        // TODO: Return Return WP Error object if $returnWpError is true
        // TODO: Add support for purge all in multisite context
        // TODO: Add queue handling here
    }

}
