<?php

namespace Servebolt\Optimizer\FullPageCache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\smartGetOption;

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
        new FullPageCacheSettings;
        FullPageCacheHeaders::init();
        $this->purgePostCacheIfAddedToFpcExclusion();
    }

    /**
     * Purge post cache if its added to the FPC exclusion list.
     */
    private function purgePostCacheIfAddedToFpcExclusion(): void
    {
        add_action('sb_optimizer_post_added_to_fpc_exclusion', function($postId) {
            try {
                WordPressCachePurge::purgeByPostId($postId);
            } catch (Exception $e) {}
        });
    }
}
