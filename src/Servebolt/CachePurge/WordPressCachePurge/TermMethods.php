<?php

namespace Servebolt\Optimizer\CachePurge\WordPressCachePurge;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge as CachePurgeDriver;
use Servebolt\Optimizer\CachePurge\PurgeObject\PurgeObject;
use Servebolt\Optimizer\Queue\Queues\WpObjectQueue;
use function Servebolt\Optimizer\Helpers\isQueueItem;

/**
 * Trait TermMethods
 * @package Servebolt\Optimizer\CachePurge\WordPressCachePurge
 */
trait TermMethods
{
    use SharedMethods;

    /**
     * Get all the URLs to purge for a given term.
     *
     * @param int $termId
     * @param string $taxonomySlug
     * @return bool|mixed
     */
    private static function getUrlsToPurgeByTermId(int $termId, string $taxonomySlug): array
    {
        $purgeObject = new PurgeObject(
            $termId,
            'term',
            compact('taxonomySlug'),
        );
        return $purgeObject->getUrls();
    }

    /**
     * Purge cache for term.
     *
     * @param int $termId
     * @param string $taxonomySlug
     * @return bool
     */
    public static function purgeTermCache(int $termId, string $taxonomySlug): bool
    {
        if (CachePurgeDriver::queueBasedCachePurgeIsActive()) {
            $queueInstance = WpObjectQueue::getInstance();
            return isQueueItem($queueInstance->add([
                $termId,
                'term',
                compact('taxonomySlug'),
            ]));
        } else {
            $urlsToPurge = self::getUrlsToPurgeByTermId($termId, $taxonomySlug);
            $cachePurgeDriver = CachePurgeDriver::getInstance();
            $urlsToPurge = self::maybeSliceUrlsToPurge($urlsToPurge, 'term', $cachePurgeDriver);
            return $cachePurgeDriver->purgeByUrls($urlsToPurge);
        }
    }
}
