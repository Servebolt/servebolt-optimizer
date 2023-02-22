<?php

namespace Servebolt\Optimizer\Compatibility\WooCommerce;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\woocommerceIsActive;

/**
 * Class WooCommerce
 * @package Servebolt\Optimizer\Compatibility\WooCommerce
 */
class WooCommerce
{
    /**
     * WooCommerce constructor.
     */
    public function __construct()
    {
        if (!apply_filters('sb_optimizer_woocommerce_compatibility', true)) {
            return;
        }
        if (!woocommerceIsActive()) {
            return;
        }
        new ProductVariationCachePurge;
        new ProductCachePurgeOnStockChange;
        new CacheExceptionRules;
        new InstantPageURLs;
    }
}
