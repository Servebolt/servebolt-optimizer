<?php

namespace Servebolt\Optimizer\Compatibility\WpRocket;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\AcceleratedDomains\AcceleratedDomains;

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
        if (AcceleratedDomains::isActive()) {
            add_filter('do_rocket_generate_caching_files', '__return_false');
            register_activation_hook(SERVEBOLT_PLUGIN_FILE, __CLASS__ . '::wpRocketClearAllCache'); // Clear all WP Rocket cache upon plugin activation (if ACD is active).
        }
        //add_action('sb_optimizer_acd_enable', __CLASS__ . '::wpRocketClearAllCache'); // Clear all WP Rocket cache upon ACD activation.
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
