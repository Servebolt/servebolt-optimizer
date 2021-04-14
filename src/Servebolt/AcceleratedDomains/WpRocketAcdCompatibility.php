<?php

namespace Servebolt\Optimizer\AcceleratedDomains;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class WpRocketAcdCompatibility
 *
 * This class
 * @package Servebolt\Optimizer\AcceleratedDomains
 */
class WpRocketAcdCompatibility
{
    /**
     * WpRocketAcdCompatibility constructor.
     */
    public function __construct()
    {
        if (!apply_filters('sb_optimizer_acd_wp_rocket_compatibility', true)) {
            return;
        }
        $this->disableWpRocketCache();
    }

    /**
     * Disable WP Rocket cache.
     */
    private function disableWpRocketCache(): void
    {
        if (AcceleratedDomains::isActive()) {
            add_filter('do_rocket_generate_caching_files', '__return_false');
            register_activation_hook(SERVEBOLT_PLUGIN_FILE, __CLASS__ . '::wpRocketClearAllCache'); // Clear all WP Rocket cache upon plugin activation.
        }
    }

    /**
     * Clear all cache in WP Rocket (given that WP Rocket is present).
     */
    public static function wpRocketClearAllCache(): void
    {
        if (function_exists('rocket_clean_domain')) {
            // Purge all WP Rocket cache
            rocket_clean_domain();
        }
    }
}
