<?php

namespace Servebolt\Optimizer\Compatibility\WpRocket;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

//use Servebolt\Optimizer\AcceleratedDomains\AcceleratedDomains;
use Servebolt\Optimizer\FullPageCache\FullPageCacheSettings;

/**
 * Class DisableWpRocketCache
 * @package Servebolt\Optimizer\Compatibility\WpRocket
 */
class DisableWpRocketCache
{
    /**
     * DisableWpRocketCache constructor.
     */
    public function __construct()
    {
        if ($this->shouldDisableCache()) {
            // Disable WP Rocket cache
            add_filter('do_rocket_generate_caching_files', '__return_false');
        }

        // Clear WP Rocket cache every time we enable FPC / ACD
        add_action('sb_optimizer_fpc_enable', [$this, 'wpRocketClearAllCache']);
    }

    /**
     * Check whether we should disable WP Rocket cache.
     *
     * @return bool
     */
    private function shouldDisableCache(): bool
    {
        //return AcceleratedDomains::isActive();
        return FullPageCacheSettings::fpcIsActive();
    }

    /**
     * Clear all cache in WP Rocket (given that WP Rocket is present).
     *
     * @return bool
     */
    public function wpRocketClearAllCache(): bool
    {
        if (function_exists('rocket_clean_domain')) {
            // Purge all WP Rocket cache
            return rocket_clean_domain();
        }
        return false;
    }
}
