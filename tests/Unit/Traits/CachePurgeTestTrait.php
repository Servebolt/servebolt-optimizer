<?php

namespace Unit\Traits;

use function Servebolt\Optimizer\Helpers\updateOption;

/**
 * Trait CachePurgeTestTrait
 * @package Unit
 */
trait CachePurgeTestTrait
{
    /**
     * Set up bogus ACD config.
     */
    private function setUpBogusAcdConfig(): void
    {
        add_filter('sb_optimizer_selected_cache_purge_driver', function() {
            return 'acd';
        });
        add_filter('sb_optimizer_acd_is_configured', '__return_true');
        updateOption('cache_purge_switch', true);
        updateOption('cache_purge_auto', true);
    }

    /**
     * Use the queue based cache purge.
     */
    private function useQueueBasedCachePurge(): void
    {
        add_filter('sb_optimizer_get_option_servebolt_queue_based_cache_purge', '__return_true');
    }
}
