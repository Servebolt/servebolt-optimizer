<?php

namespace Servebolt\Optimizer\CachePurge\WordPressCachePurge;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge as CachePurgeDriver;
use Servebolt\Optimizer\CachePurge\PurgeObject\PurgeObject;
use Servebolt\Optimizer\Queue\Queues\WpObjectQueue;
use function Servebolt\Optimizer\Helpers\isQueueItem;

/**
 * Trait PostMethods
 * @package Servebolt\Optimizer\CachePurge\WordPressCachePurge
 */
trait PostMethods
{
    use SharedMethods;

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
     * Purge post cache by post Id.
     *
     * @param int $postId
     * @return bool|WP_Error
     */
    public static function purgePostCache(int $postId): bool
    {

        // If this is just a revision, don't purge anything.
        if ( ! $postId || wp_is_post_revision( $postId ) ) {
            return false;
        }

        if (CachePurgeDriver::queueBasedCachePurgeIsActive()) {
            $queueInstance = WpObjectQueue::getInstance();
            return isQueueItem($queueInstance->add([
                'type' => 'post',
                'id' => $postId,
            ]));
        } else {
            $urlsToPurge = self::getUrlsToPurgeByPostId($postId);
            $cachePurgeDriver = CachePurgeDriver::getInstance();
            $urlsToPurge = self::maybeSliceUrlsToPurge($urlsToPurge, 'post', $cachePurgeDriver);
            return $cachePurgeDriver->purgeByUrls($urlsToPurge);
        }
    }
}
