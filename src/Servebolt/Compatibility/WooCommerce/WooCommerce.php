<?php

namespace Servebolt\Optimizer\Compatibility\WooCommerce;

use function Servebolt\Optimizer\Helpers\woocommerceIsActive;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

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
        if (!woocommerceIsActive()) {
            return;
        }
        new ProductCachePurgeOnStockChange;
    }
}
