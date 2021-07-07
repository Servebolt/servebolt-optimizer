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
     * @var bool Whether to prevent the same term from being purge more than once during the execution.
     */
    private static $preventTermDoublePurge = true;

    /**
     * @var array Array of recently purged terms.
     */
    private static $recentlyPurgedTerms = [];

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
        do_action('sb_optimizer_purged_term_cache', $termId);
        do_action('sb_optimizer_purged_term_cache_for_' . $termId);

        if (self::shouldPurgeByQueue()) {
            $queueInstance = WpObjectQueue::getInstance();
            return isQueueItem($queueInstance->add([
                'type' => 'term',
                'id'   => $termId,
                'args' => compact('taxonomySlug'),
            ]));
        } else {
            if (self::$preventDoublePurge && self::$preventTermDoublePurge && array_key_exists($termId . '-' . $taxonomySlug, self::$recentlyPurgedTerms)) {
                return self::$recentlyPurgedTerms[$termId . '-' . $taxonomySlug];
            }
            $urlsToPurge = self::getUrlsToPurgeByTermId($termId, $taxonomySlug);
            $cachePurgeDriver = CachePurgeDriver::getInstance();
            $urlsToPurge = self::maybeSliceUrlsToPurge($urlsToPurge, 'term', $cachePurgeDriver);
            $result = $cachePurgeDriver->purgeByUrls($urlsToPurge);
            if (self::$preventDoublePurge && self::$preventTermDoublePurge) {
                self::$recentlyPurgedTerms[$termId . '-' . $taxonomySlug] = $result;
            }
            return $result;
        }
    }
}
