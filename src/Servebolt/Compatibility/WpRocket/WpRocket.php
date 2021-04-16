<?php

namespace Servebolt\Optimizer\Compatibility\WpRocket;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\wpRocketIsActive;

/**
 * Class WpRocket
 * @package Servebolt\Optimizer\Compatibility\WpRocket
 */
class WpRocket
{
    /**
     * WpRocket constructor.
     */
    public function __construct()
    {
        if (!apply_filters('sb_optimizer_wp_rocket_compatibility', true)) {
            return;
        }
        if (!wpRocketIsActive()) {
            return;
        }
        new DisableWpRocketCache;
    }
}
