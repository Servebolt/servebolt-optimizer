<?php

namespace Servebolt\Optimizer\CachePurge\WordPressCachePurge;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge as CachePurgeDriver;
use Servebolt\Optimizer\CachePurge\PurgeObject\PurgeObject;
use Servebolt\Optimizer\Queue\Queues\WpObjectQueue;

/**
 * Trait TermMethods
 * @package Servebolt\Optimizer\CachePurge\WordPressCachePurge
 */
trait TermMethods
{

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
            ['taxonomy_slug' => $taxonomySlug]
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
            return $queueInstance->add([
                $termId,
                'term',
                ['taxonomy_slug' => $taxonomySlug]
            ]);
        } else {
            $urlsToPurge = self::getUrlsToPurgeByTermId($termId, $taxonomySlug);
            $cachePurgeDriver = CachePurgeDriver::getInstance();
            return $cachePurgeDriver->purgeByUrls($urlsToPurge);
        }
    }
}
