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
        $shouldPurgeByQueue = self::shouldPurgeByQueue();

        // If this is just a revision, don't purge anything.
        if (!$postId || wp_is_post_revision($postId)) {
            return false;
        }

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
        if ($originalUrl && get_permalink($postId) !== $originalUrl) {
            $queueItemData['original_url'] = $originalUrl;
        }
        return $queueItemData;
    }

    private static function shouldAttemptCacheTags() : bool
    {
        $blogId = null;

        if(is_multisite()) {
            $blogId = get_current_blog_id();
        }
        // Check if cache tags exist and work on this site.
        if( smartGetOption($blogId, 'added_cache_tags', false) === false) return false;

        return true;
    }

    private static function canUseCacheTags() : bool
    {
        // function to check if running ACD and is on servebolt
        // if( !isHostedAtServebolt() ) return false;
       

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

        $shouldAttemptCacheTags = self::shouldAttemptCacheTags();
        // Loading the CachePurgeDriver early so that it can be assessed for CacheTags capibility.
        $cachePurgeDriver = CachePurgeDriver::getInstance();
        // Perform check.
        $canUseCacheTags = self::canCacheTags($cachePurgeDriver);
        // Default to URL purging.
        $purgeObjectType = 'post';
        // If Hosted on Servebolt, and is capible of cache tags. 
        if($shouldAttemptCacheTags && $canUseCacheTags) {
            $purgeObjectType = 'cachetag';
        }
        
        /**
         * Fires when cache is being purged for a post.
         *
         * @param int $postId ID of the post that's being purge cache for.
         * @param bool $simplePurge Whether this is a simple purge or not, a simple purge meaning that we purge the URL only, and not the full URL hierarchy, like archives etc.
         */
        do_action('sb_optimizer_purged_post_cache', $postId, false);
        do_action('sb_optimizer_purged_post_cache_for_' . $postId, false);

        if ($shouldPurgeByQueue) {
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
        } else {
            if (self::$preventDoublePurge && self::$preventPostDoublePurge && array_key_exists($postId, self::$recentlyPurgedPosts)) {
                return self::$recentlyPurgedPosts[$postId];
            }

            if($purgeObjectType == 'post') {
                $urlsToPurge = self::getUrlsToPurgeByPostId($postId);
                $urlsToPurge = self::maybeSliceUrlsToPurge($urlsToPurge, $purgeObjectType, $cachePurgeDriver);
                $result = $cachePurgeDriver->purgeByUrls($urlsToPurge);
            }
           
            if($purgeObjectType == 'cachetag') {
                // TODO : consider splitting into max 30 CacheTag items, not expected to be used.
                $tagsToPurge = self::getTagsToPurgeByPostId($postId);                
                $result = $cachePurgeDriver->purgeByTags($tagsToPurge);
            }
            //
            if (self::$preventDoublePurge && self::$preventPostDoublePurge) {
                self::$recentlyPurgedPosts[$postId] = $result;
            }
            return $result;
        }
    }
}
