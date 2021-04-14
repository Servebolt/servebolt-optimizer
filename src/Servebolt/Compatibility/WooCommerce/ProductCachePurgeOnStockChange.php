<?php

namespace Servebolt\Optimizer\Compatibility\WooCommerce;

use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class WooCommerce
 * @package Servebolt\Optimizer\Compatibility\WooCommerce
 */
class ProductCachePurgeOnStockChange
{
    /**
     * ProductCachePurgeOnStockChange constructor.
     */
    public function __construct()
    {
        add_filter('woocommerce_product_set_stock', [$this, 'productStockChange']);
        add_filter('woocommerce_variation_set_stock', [$this, 'productStockChange']);
    }

    /**
     * Purge cache for product whenever stock changes.
     *
     * @param $product
     */
    public function productStockChange($product): void
    {
        WordPressCachePurge::purgeByPostId($product->get_id());
    }
}
