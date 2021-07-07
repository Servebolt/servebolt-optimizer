<?php

namespace Servebolt\Optimizer\FullPageCache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge;
use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\Traits\Singleton;

/**
 * Class FullPageCache
 * @package Servebolt\Optimizer\FullPageCache
 */
class FullPageCache
{
    use Singleton;

    /**
     * FullPageCache constructor.
     */
    public function __construct()
    {
        FullPageCacheSettings::init();
        FullPageCacheHeaders::init();
        $this->purgePostCacheIfAddedToFpcExclusion();
    }

    /**
     * Purge post cache if its added to the FPC exclusion list.
     */
    private function purgePostCacheIfAddedToFpcExclusion(): void
    {

        // Skip this feature if the cache purge feature is inactive or insufficiently configured, or it automatic cache purge is inactive
        if (!CachePurge::featureIsAvailable()) {
            return;
        }

        // Should skip automatic cache purge for posts added to FPC exclusion?
        if (apply_filters('sb_optimizer_disable_automatic_purge_on_post_added_to_fpc_exclusion', false)) {
            return;
        }

        add_action('sb_optimizer_post_added_to_fpc_exclusion', function($postId) {
            try {
                WordPressCachePurge::purgeByPostId($postId);
            } catch (Exception $e) {}
        }, 10, 1);
    }
}
