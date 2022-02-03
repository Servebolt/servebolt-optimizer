<?php

namespace Servebolt\Optimizer\FullPageCache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Exception;
use Servebolt\Optimizer\CachePurge\CachePurge;
use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;
use function Servebolt\Optimizer\Helpers\isTesting;
use function Servebolt\Optimizer\Helpers\setDefaultOption;

/**
 * Class FullPageCache
 * @package Servebolt\Optimizer\FullPageCache
 */
class FullPageCache
{
    use Singleton;

    /**
     * Alias for "getInstance".
     */
    public static function init()
    {
        self::getInstance();
    }

    /**
     * FullPageCache constructor.
     */
    public function __construct()
    {
        FullPageCacheSettings::init();
        FullPageCacheHeaders::init();
        if (isHostedAtServebolt() || isTesting()) {
            CacheTtl::init();
        }
        $this->purgePostCacheIfAddedToHtmlCacheExclusion();
        $this->defaultOptionValues();
    }

    /**
     * Set default option values.
     */
    private function defaultOptionValues(): void
    {
        setDefaultOption('fpc_settings', function() {
            return [
                'all' => 1,
            ];
        });
    }

    /**
     * Purge post cache if its added to the HTML Cache exclusion list.
     */
    private function purgePostCacheIfAddedToHtmlCacheExclusion(): void
    {

        // Skip this feature if the cache purge feature is inactive or insufficiently configured, or it automatic cache purge is inactive
        if (!CachePurge::featureIsAvailable()) {
            return;
        }

        // Should skip automatic cache purge for posts added to HTML Cache exclusion?
        if (apply_filters('sb_optimizer_disable_automatic_purge_on_post_added_to_html_cache_exclusion', false)) {
            return;
        }

        add_action('sb_optimizer_post_added_to_html_cache_exclusion', function($postId) {
            try {
                WordPressCachePurge::purgeByPostId((int) $postId);
            } catch (Exception $e) {}
        }, 10, 1);
    }
}
