<?php

namespace Servebolt\Optimizer\CachePurge\WordPressCachePurge;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge as CachePurgeDriver;
use Servebolt\Optimizer\Queue\Queues\WpObjectQueue;
use function Servebolt\Optimizer\Helpers\isQueueItem;
use function Servebolt\Optimizer\Helpers\iterateSites;

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
     * @var bool Whether to prevent the same post/term from being purge more than once during the execution.
     */
    private static $preventDoublePurge = true;

    /**
     * Attempt to resolve post Id from URL.
     *
     * @param string $url
     * @return int|null
     */
    public static function attemptToResolvePostIdFromUrl(string $url): ?int
    {
        if ($postId = url_to_postid($url)) {
            return $postId;
        }
        return null;
    }

    /**
     * Purge cache by URL.
     *
     * @param string $url
     * @param bool $shouldAttemptToResolvePostIdFromUrl
     * @return bool
     */
    public static function purgeByUrl(string $url, bool $shouldAttemptToResolvePostIdFromUrl = true)
    {
        $shouldPurgeByQueue = self::shouldPurgeByQueue();
        if (
            $shouldAttemptToResolvePostIdFromUrl
            && $postId = self::attemptToResolvePostIdFromUrl($url)
        ) { // Resolve URL to post ID, then purge by post ID
            /*
            if ($url !== get_permalink($postId)) {
                // Purge only URL, not post?
            } else {
                // Purge post, since specified URL is identical with post URL
            }
            */
            add_filter('sb_optimizer_purge_by_post_original_url', function() use ($url) {
                return $url;
            });
            return self::purgePostCache((int) $postId);
        } else {
            if ($shouldPurgeByQueue) {
                $queueInstance = WpObjectQueue::getInstance();
                return isQueueItem($queueInstance->add([
                    'type' => 'url',
                    'url' => $url,
                ]));
            } else {
                $cachePurgeDriver = CachePurgeDriver::getInstance();
                return $cachePurgeDriver->purgeByUrl($url);
            }
        }
    }

    /**
     * Purge cache by URLs.
     *
     * @param array $urls
     * @return bool
     */
    public static function purgeByUrls(array $urls)
    {
        if (self::shouldPurgeByQueue()) {
            $queueInstance = WpObjectQueue::getInstance();
            foreach($urls as $url) {
                isQueueItem($queueInstance->add([
                    'type' => 'url',
                    'url' => $url,
                ]));
            }
            return true;
        } else {
            $cachePurgeDriver = CachePurgeDriver::getInstance();
            return $cachePurgeDriver->purgeByUrls($urls);
        }
    }

    /**
     * Alias for method "purgeByTermId".
     *
     * @param int $termId
     * @param string $taxonomySlug
     * @param bool $returnWpError
     * @return bool
     */
    public static function purgeByTerm(int $termId, string $taxonomySlug, bool $returnWpError = false)
    {
        return self::purgeByTermId($termId, $taxonomySlug, $returnWpError);
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
     * Alias for method "purgeByPostId".
     *
     * @param int $postId
     * @param bool $returnWpError
     * @return bool
     */
    public static function purgeByPost(int $postId, bool $returnWpError = false)
    {
        return self::purgeByPostId($postId, $returnWpError);
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
     * Purge all cache on the current site.
     *
     * @param bool $returnWpError
     * @return bool
     */
    public static function purgeAll(bool $returnWpError = false)
    {
        return self::purgeAllByBlogId();
    }

    /**
     * Purge all cache for given site.
     *
     * @param int|null $blogId
     * @param bool $returnWpError
     * @return bool
     */
    public static function purgeAllByBlogId(?int $blogId = null, bool $returnWpError = false)
    {
        if (self::shouldPurgeByQueue($blogId)) {
            $queueInstance = WpObjectQueue::getInstance($blogId);
            return isQueueItem($queueInstance->add(['type' => 'purge-all']));
        } else {
            $cachePurgeDriver = CachePurgeDriver::getInstance($blogId);
            return $cachePurgeDriver->purgeAll();
        }
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
        iterateSites(function($site) {
            self::purgeAllByBlogId((int) $site->blog_id);
        });
    }
}
