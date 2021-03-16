<?php

namespace Servebolt\Optimizer\CachePurge\WordPressCachePurge;

use WP_Error;
use Exception;
use Servebolt\Optimizer\CachePurge\CachePurge;
use Servebolt\Optimizer\Exceptions\ApiError;

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
     * @param bool $returnWpError
     * @return bool|WP_Error
     */
    public static function purgeByUrl(string $url, bool $returnWpError = false)
    {
        if ($postId = url_to_postid($url)) {
            return self::purgePostCache($postId);
        } else {
            try {
                // TODO: Add queue handling here
                $cachePurgeDriver = CachePurge::getInstance();
                return $cachePurgeDriver->purgeByUrl($url);
            } catch (ApiError $e) {
                // TODO: Handle API error message
                if ($returnWpError) {
                    // TODO: Return WP Error object
                }
                return false;
            } catch (Exception $e) {
                // TODO: Handle general error
                if ($returnWpError) {
                    // TODO: Return WP Error object
                }
                return false;
            }
        }
    }

    /**
     * Alias for method "purgeTermCache".
     *
     * @param int $termId
     * @param string $taxonomySlug
     * @param bool $returnWpError
     * @return bool|WP_Error
     */
    public function purgeByTermId(int $termId, string $taxonomySlug, bool $returnWpError = false)
    {
        return self::purgeTermCache($termId, $taxonomySlug, $returnWpError);
    }

    /**
     * Alias for method "purgePostCache".
     *
     * @param int $postId
     * @param bool $returnWpError
     * @return bool|WP_Error
     */
    public static function purgeByPostId(int $postId, bool $returnWpError = false)
    {
        return self::purgePostCache($postId, $returnWpError);
    }

    /**
     * Purge all cache on the site.
     *
     * @param bool $returnWpError
     * @return bool|WP_Error
     */
    public static function purgeAll(bool $returnWpError = false)
    {
        // TODO: Add queue handling here
        try {
            $cachePurgeDriver = CachePurge::getInstance();
            return $cachePurgeDriver->purgeAll();
        } catch (ApiError $e) {
            // TODO: Handle API error message
            if ($returnWpError) {
                // TODO: Return WP Error object
            }
            return false;
        } catch (Exception $e) {
            // TODO: Handle general error
            if ($returnWpError) {
                // TODO: Return WP Error object
            }
            return false;
        }
    }

    /**
     * Purge all cache for given site (only for multisites).
     *
     * @param int $blogId
     * @param bool $returnWpError
     * @return false|WP_Error
     */
    public static function purgeAllByBlogId(int $blogId, bool $returnWpError = false)
    {
        if (!is_multisite()) {
            return false;
        }
        // TODO: Add switch to blog-logic here
        // TODO: Add queue handling here
        try {
            $cachePurgeDriver = CachePurge::getInstance();
            return $cachePurgeDriver->purgeAll();
        } catch (ApiError $e) {
            // TODO: Handle API error message
            if ($returnWpError) {
                // TODO: Return WP Error object
            }
            return false;
        } catch (Exception $e) {
            // TODO: Handle general error
            if ($returnWpError) {
                // TODO: Return WP Error object
            }
            return false;
        }
    }

    /**
     * Purge all cache in network (only for multisites).
     *
     * @param bool $returnWpError
     * @return bool|WP_Error
     */
    public function purgeAllNetwork(bool $returnWpError = false)
    {
        if (!is_multisite()) {
            return false;
        }
        // TODO: Return Return WP Error object if $returnWpError is true
        // TODO: Add support for purge all in multisite context
        // TODO: Add queue handling here
    }

}
