<?php

namespace Servebolt\Optimizer\CachePurge\WordPressCachePurge;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge as CachePurgeDriver;
use Servebolt\Optimizer\CachePurge\PurgeObject\PurgeObject;
use Servebolt\Optimizer\Queue\Queues\WpObjectQueue;
use function Servebolt\Optimizer\Helpers\getCachePurgeOriginEvent;
use function Servebolt\Optimizer\Helpers\isQueueItem;
use function Servebolt\Optimizer\Helpers\pickupValueFromFilter;
use function Servebolt\Optimizer\Helpers\smartGetOption;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;
use function Servebolt\Optimizer\Helpers\convertOriginalUrlToString;

/**
 * Trait PostMethods
 * @package Servebolt\Optimizer\CachePurge\WordPressCachePurge
 */
trait PostMethods
{
    use SharedMethods;

    /**
     * @var bool Whether to prevent the same post from being purge more than once during the execution.
     */
    private static $preventPostDoublePurge = true;

    /**
     * @var array Array of recently purged posts.
     */
    private static $recentlyPurgedPosts = [];

    /**
     * Get all the URLs to purge for a given post.
     *
     * @param int $postId
     * @return array
     */
    private static function getUrlsToPurgeByPostId(int $postId): array
    {
        $purgeObject = new PurgeObject(
            $postId,
            'post'
        );
        return $purgeObject->getUrls();
    }

    /**
     * Get all the URLs to purge for a given post.
     *
     * @param int $postId
     * @return array
     */
    private static function getTagsToPurgeByPostId(int $postId): array
    {
        $purgeObject = new PurgeObject(
            $postId,
            'cachetag'
        );
        return $purgeObject->getCacheTags();
    }
    /**
     * Do cache purge for a post without resolving the whole URL hierarchy.
     *
     * @param int $postId
     * @return bool|null
     */
    public static function purgePostCacheSimple(int $postId): ?bool
    {
        // If this is just a revision, don't purge anything.
        if (!$postId || wp_is_post_revision($postId)) {
            return false;
        }

        // Get purge style.
        $shouldPurgeByQueue = self::shouldPurgeByQueue();
        /**
         * Fires when cache is being purged for a post.
         *
         * @param int $postId ID of the post that's being purge cache for.
         * @param bool $simplePurge Whether this is a simple purge or not, a simple purge meaning that we purge the URL only, and not the full URL hierarchy, like archives etc.
         */
        do_action('sb_optimizer_purged_post_cache', $postId, true);
        do_action('sb_optimizer_purged_post_cache_for_' . $postId, true);

        if ($shouldPurgeByQueue) {
            $queueInstance = WpObjectQueue::getInstance();
            $queueItemData = [
                'type' => 'post',
                'id' => $postId,
                'simplePurge' => true,
            ];
            if ($originEvent = getCachePurgeOriginEvent()) {
                $queueItemData['originEvent'] = $originEvent;
            }
            $queueItemData = self::maybeAddOriginalUrl($queueItemData, $postId);
            return isQueueItem($queueInstance->add($queueItemData));
        } else {
            $cachePurgeDriver = CachePurgeDriver::getInstance();
            $postUrl = get_permalink($postId);
            return $cachePurgeDriver->purgeByUrl($postUrl);
        }
    }

    /**
     * Maybe add original URL to the purge request (useful whenever the slug has recently been changed).
     *
     * @param array $queueItemData
     * @param int $postId
     * @return mixed
     */
    private static function maybeAddOriginalUrl(array $queueItemData, int $postId)
    {
        $originalUrl = pickupValueFromFilter('sb_optimizer_purge_by_post_original_url');
        $originalUrl = convertOriginalUrlToString($originalUrl);
        if ($originalUrl && get_permalink($postId) !== $originalUrl) {
            $queueItemData['original_url'] = $originalUrl;
        }
        return $queueItemData;
    }

    /**
     * Check if running Servebotl CDN or ACD and on Servebolt Hosting.
     * 
     * @return bool
     */
    private static function cacheTagsEnabled(): bool
    {
        $blogId = null;
        if (is_multisite()) {
            $blogId = get_current_blog_id();
        }
        if (in_array(CachePurgeDriver::resolveDriverNameWithoutConfigCheck($blogId), ['acd', 'serveboltcdn'])) {
            return true;
        }

        // Check if Cloudflare is used as driver and if cf_cache_tags is 1 (Enabled)
        if (CachePurgeDriver::resolveDriverNameWithoutConfigCheck($blogId) === 'cloudflare') {
            if (smartGetOption($blogId, 'cf_cache_tag', '1') === '1') {
                return true;
            }
        }
        return false;
    }

    /**
     * If not running on Servebolt, can't use cache tags.
     * 
     * @return bool
     */
    private static function canUseCacheTags(): bool
    {
        if (!isHostedAtServebolt()) return false;
        return true;
    }
    /**
     * Purge post cache by post Id.
     *
     * @param int $postId
     * @return bool|WP_Error
     */
    public static function purgePostCache(int $postId): bool
    {
        // If this is just a revision, don't purge anything.
        if (!$postId || wp_is_post_revision($postId)) {
            return false;
        }
        $shouldPurgeByQueue = self::shouldPurgeByQueue();
        // Check if on Servebolt and Servebolt CDN/ACD.
        $canUseCacheTags = self::cacheTagsEnabled();
        $purgeObjectType = ($canUseCacheTags) ? 'cachetag' : 'post';

        /**
         * Fires when cache is being purged for a post.
         *
         * @param int $postId ID of the post that's being purge cache for.
         * @param bool $simplePurge Whether this is a simple purge or not, 
         *             a simple purge meaning that we purge the URL only, 
         *             and not the full URL hierarchy, like archives etc.
         */
        do_action('sb_optimizer_purged_post_cache', $postId, false);
        do_action('sb_optimizer_purged_post_cache_for_' . $postId, false);

        if ($shouldPurgeByQueue) {
            return self::setupPurgeByQueue($postId, $purgeObjectType);
        } else {
            $cachePurgeDriver = CachePurgeDriver::getInstance();
            if (self::$preventDoublePurge && self::$preventPostDoublePurge && array_key_exists($postId, self::$recentlyPurgedPosts)) {
                return self::$recentlyPurgedPosts[$postId];
            }
            // If the purge provider only supports URL purging, use that.
            // This is for people who are not on Servebolt or not using Servebolt CDN/ACD.
            if ($purgeObjectType == 'post') {
                $result = self::setupPurgeByPost($postId, $cachePurgeDriver);
                self::setResultOfPostPurge($postId, $result);
            }
            // If the provider supports cachetags, use that. (Servebolt CDN/ACD)
            if ($purgeObjectType == 'cachetag') {
                $result = self::setupPurgeByCachetags($postId, $cachePurgeDriver);
            }
            return $result;
        }
    }

    /**
     * Set the result of a post purge.
     *
     * @param int $postId
     * @param bool $result
     * 
     * @return void
     */
    private static function setResultOfPostPurge(int $postId, bool $result): void
    {
        if (self::$preventDoublePurge && self::$preventPostDoublePurge) {
            self::$recentlyPurgedPosts[$postId] = $result;
        }
    }

    /**
     * Setup purge by queue.
     * 
     * The queue stores a stub of the purge request, and is worked on by the queue worker.
     * TODO: move to getting the queue worker to expand the stub on reciept or already have these
     * expanded when created, and check for duplicates then.
     *
     * @param int $postId
     * @param string $purgeObjectType
     * @return bool
     */
    protected static function setupPurgeByQueue($postId, $purgeObjectType)
    {
        $queueInstance = WpObjectQueue::getInstance();
        $queueItemData = [
            'type' => $purgeObjectType, // Replace with cachetag when available, default to post.
            'id' => $postId,
        ];
        if ($originEvent = getCachePurgeOriginEvent()) {
            $queueItemData['originEvent'] = $originEvent;
        }
        $queueItemData = self::maybeAddOriginalUrl($queueItemData, $postId);
        return isQueueItem($queueInstance->add($queueItemData));
    }

    /**
     * Based on the URL's requested to be purged, remove any that are invalid.
     * 
     * @param array $urlsToPurge
     * @param CachePurgeDriver $cachePurgeDriver
     * 
     * @return array
     */
    protected static function removeInvalidPurgeTargets($urlsToPurge, $cachePurgeDriver)
    {
        $validUrlsToPurge = [];
        foreach ($urlsToPurge as $url) {
            if ($cachePurgeDriver->validateUrl($url)) {
                $validUrlsToPurge[] = $url;
            }
        }
        return $validUrlsToPurge;
    }
    /**
     * Purge post cache via URL's using post Id.
     *
     * @param int $postId
     * @param CachePurgeDriver $cachePurgeDriver
     *
     * @return bool always returns true or failure via ServeboltApiError exception.
     */
    protected static function setupPurgeByPost($postId, $cachePurgeDriver)
    {
        $urlsToPurge = self::getUrlsToPurgeByPostId($postId);
        // Prototype for removing invalid purge targets.
        $urlsToPurge = self::removeInvalidPurgeTargets($urlsToPurge, $cachePurgeDriver);
        if (count($urlsToPurge) === 0) {
            return true;
        }
        $urlsToPurge = self::maybeSliceUrlsToPurge($urlsToPurge, 'post', $cachePurgeDriver);
        return $cachePurgeDriver->purgeByUrls($urlsToPurge);
    }


    /**
     * Purge post cache via CacheTag's using post Id.
     *
     * Checks if using ACD or Servebolt CDN, loads the correct driver and
     * purge requests for that style of CDN.
     *
     * @param int $postId
     * @param CachePurgeDriver $cachePurgeDriver
     *
     * @return bool always returns true or failure via ServeboltApiError exception.
     */
    protected static function setupPurgeByCachetags($postId, $cachePurgeDriver)
    {
        $result = false;
        // If accelerated domains clear the Permalink and tags.
        if ($cachePurgeDriver->resolveDriverNameWithoutConfigCheck() == 'acd') {
            $url = get_permalink($postId);
            $result = $cachePurgeDriver->purgeByUrl($url);
            self::setResultOfPostPurge($postId, $result);
            // If purging the url did not work, don't go further, purging via cache tags will no
            // doubt also fail.
            if (!$result) {
                return $result;
            }
            // Now purge cache tags.
            $tagsToPurge = self::getTagsToPurgeByPostId($postId);
            $chunkedTagsToPurge = array_chunk($tagsToPurge, 30);
            foreach ($chunkedTagsToPurge as $tags) {
                $result = $cachePurgeDriver->purgeByTags($tags);
                if (!$result) {
                    error_log("Servebolt Optimizer: Accelerated Domains CacheTags Purge failed");
                }
            }
        } elseif ($cachePurgeDriver->resolveDriverNameWithoutConfigCheck() == 'cloudflare') {
            $blogId = null;
            if (is_multisite()) {
                $blogId = get_current_blog_id();
            }

            if (smartGetOption($blogId, 'cf_cache_tags', '1') === '1') {
                $tagsToPurge = self::getTagsToPurgeByPostId($postId);
                // for safety, chunk the tags to purge.
                $chunkedTagsToPurge = array_chunk($tagsToPurge, 30);
                foreach ($chunkedTagsToPurge as $tags) {
                    $result = $cachePurgeDriver->purgeByTags($tags);
                    if (!$result) {
                        error_log("Servebolt Optimizer: Cloudflare CacheTags Purge failed");
                    }
                }
            } else {
                $urlsToPurge = self::getUrlsToPurgeByPostId($postId);
                $result = $cachePurgeDriver->purgeByUrls($urlsToPurge);
                // If purging the url does not work, don't go further.
                self::setResultOfPostPurge($postId, $result);
                if (!$result) {
                    return $result;
                }
            }
        } else {
            // if Serveblt CDN only use tags when there is more than 16 urls to purge.
            $urlsToPurge = self::getUrlsToPurgeByPostId($postId);
            if (count($urlsToPurge) < 17) {
                $result = $cachePurgeDriver->purgeByUrls($urlsToPurge);
                // If purging the url does not work, don't go further.
                self::setResultOfPostPurge($postId, $result);
                if (!$result) {
                    return $result;
                }
            } else {
                // Next purge cache tags, it should just do 1 tag for all HTML.
                $tagsToPurge = self::getTagsToPurgeByPostId($postId);
                // for safety, chunk the tags to purge.
                $chunkedTagsToPurge = array_chunk($tagsToPurge, 30);
                foreach ($chunkedTagsToPurge as $tags) {
                    $result = $cachePurgeDriver->purgeByTags($tags);
                    if (!$result) {
                        error_log("Servebolt Optimizer: ServeboltCDN CacheTags Purge failed");
                    }
                }
            }
        }
        return $result;
    }
}
