<?php

namespace Servebolt\Optimizer\Compatibility\Jetpack;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\jetpackIsActive;

/**
 * Class Jetpack
 * @package Servebolt\Optimizer\Compatibility\Jetpack
 */
class Jetpack
{
    /**
     * WooCommerce constructor.
     */
    public function __construct()
    {
        if (!apply_filters('sb_optimizer_jetpack_compatibility', true)) {
            return;
        }
        if (!jetpackIsActive()) {
            return;
        }
        new DisableSiteAcceleratorOnAcd;
    }
}
