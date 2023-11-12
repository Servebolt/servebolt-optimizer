<?php

namespace Servebolt\Optimizer\CachePurge\WordPressCachePurge;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge as CachePurgeDriver;
use Servebolt\Optimizer\CachePurge\PurgeObject\PurgeObject;
use Servebolt\Optimizer\Queue\Queues\WpObjectQueue;
use Servebolt\Optimizer\CacheTags\GetCacheTagsHeadersForTag;
use function Servebolt\Optimizer\Helpers\getCachePurgeOriginEvent;
use function Servebolt\Optimizer\Helpers\isQueueItem;
use function Servebolt\Optimizer\Helpers\isAcd;

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
     * Get all the URLs to purge for a given term.
     *
     * @param int $termId
     * @param string $taxonomySlug
     * @return bool|mixed
     */
    private static function getTagToPurgeByTermId(int $termId): array
    {
        $cacheTag = new GetCacheTagsHeadersForTag($termId, 'term');
        return $cacheTag->getHeaders();
    }

    /**
     * Do cache purge for a term without resolving the whole URL hierarchy.
     *
     * @param int $termId
     * @param string $taxonomySlug
     * @return bool
     */
    public static function purgeTermCacheSimple(int $termId, string $taxonomySlug): bool
    {
        /**
         * Fires when cache is being purged for a term.
         *
         * @param int $termId ID of the term that's being purge cache for.
         * @param bool $simplePurge Whether this is a simple purge or not, a simple purge meaning that we purge the URL only, and not the full URL hierarchy, like archives etc.
         */
        do_action('sb_optimizer_purged_term_cache', $termId, true);
        do_action('sb_optimizer_purged_term_cache_for_' . $termId, true);

        if (self::shouldPurgeByQueue()) {
            $queueInstance = WpObjectQueue::getInstance();
            $queueItemData = [
                'type' => 'term',
                'id'   => $termId,
                'args' => compact('taxonomySlug'),
                'simplePurge' => true,
            ];
            if ($originEvent = getCachePurgeOriginEvent()) {
                $queueItemData['originEvent'] = $originEvent;
            }
            return isQueueItem($queueInstance->add($queueItemData));
        } else {
            $cachePurgeDriver = CachePurgeDriver::getInstance();
            $termUrl = get_term_link($termId);
            return $cachePurgeDriver->purgeByUrl($termUrl);
        }
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
        /**
         * Fires when cache is being purged for a term.
         *
         * @param int $termId ID of the term that's being purge cache for.
         * @param bool $simplePurge Whether this is a simple purge or not, a simple purge meaning that we purge the URL only, and not the full URL hierarchy, like archives etc.
         */
        do_action('sb_optimizer_purged_term_cache', $termId, false);
        do_action('sb_optimizer_purged_term_cache_for_' . $termId, false);

        if (self::shouldPurgeByQueue()) {
            // if queuing, will check the if CacheTag enabled and use it 
            // as needed during processing.
            $queueInstance = WpObjectQueue::getInstance();
            $queueItemData = [
                'type' => 'term',
                'id' => $termId,
                'args' => compact('taxonomySlug'),
            ];
            if ($originEvent = getCachePurgeOriginEvent()) {
                $queueItemData['originEvent'] = $originEvent;
            }
            return isQueueItem($queueInstance->add($queueItemData));
            // should only ever be with ACD but this might change later
            // so checking to make sure CacheTags will be useable.
        } 
        $cachePurgeDriver = CachePurgeDriver::getInstance();
        if ($cachePurgeDriver::driverSupportsCacheTagPurge()) {
            $purgeValues = getTagToPurgeByTermId($termId);
            if(is_array($purgeValues)) {
                error_log('purge array of tags : ' . print_r($purgeValues, true));
            } else if(is_string($purgeValues)) {
                error_log('purge string of tags : ' . $purgeValues);
            } else {
                error_log(' nothing in the reply.  that is not nice');
            } // purging term:
            $result = $cachePurgeDriver->purgeByTags($purgeValues);
        } else {
            //
            if (
                self::$preventDoublePurge
                && self::$preventTermDoublePurge
                && array_key_exists($termId . '-' . $taxonomySlug, self::$recentlyPurgedTerms)
            ) {
                return self::$recentlyPurgedTerms[$termId . '-' . $taxonomySlug];
            }
            $urlsToPurge = self::getUrlsToPurgeByTermId($termId, $taxonomySlug);
            
            $urlsToPurge = self::maybeSliceUrlsToPurge($urlsToPurge, 'term', $cachePurgeDriver);
            $result = $cachePurgeDriver->purgeByUrls($urlsToPurge);
            if (self::$preventDoublePurge && self::$preventTermDoublePurge) {
                self::$recentlyPurgedTerms[$termId . '-' . $taxonomySlug] = $result;
            }
            return $result;
        }
    }
}
