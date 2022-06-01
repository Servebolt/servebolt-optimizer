<?php

namespace Servebolt\Optimizer\Compatibility\WooCommerce;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Throwable;
use Servebolt\Optimizer\CachePurge\CachePurge;
use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions\ContentChangeTrigger;

/**
 * Class ProductCachePurgeOnStockChange
 * @package Servebolt\Optimizer\Compatibility\WooCommerce
 */
class ProductCachePurgeOnStockChange
{
    /**
     * ProductCachePurgeOnStockChange constructor.
     */
    public function __construct()
    {

        // Note: Might be sufficient with just listening to woocommerce_product_update
        if ($this->shouldPurgeCacheOnStockAmountChange()) {
            // These actions are only triggered if global setting "manage_stock" is set to true
            add_action('woocommerce_product_set_stock', [$this, 'productStockChange']);
            add_action('woocommerce_variation_set_stock', [$this, 'productVariationStockChange']);
        }

        if ($this->shouldPurgeCacheOnStockStatusChange()) {
            add_action('woocommerce_product_set_stock_status', [$this, 'productVariationStockStatusChange'], 10, 3);
            add_action('woocommerce_variation_set_stock_status', [$this, 'productStockStatusChange'], 10, 3);
        }
    }

    /**
     * Purge cache for product whenever stock amount changes.
     *
     * @param WC_Product|WC_Product_Simple|WC_Product_Grouped|WC_Product_External|WC_Product_Variable $product
     */
    public function productStockChange($product): void
    {
        if ($productId = $this->resolveProductPostId($product)) {
            if (ContentChangeTrigger::shouldPurgePostCache($productId)) { // Check if we should purge cache for the current product in regards to rules in ContentChangeTrigger::class
                try {

                    /**
                     * Determine whether we should do an immediate purge whenever the product stock changes.
                     *
                     * @param bool $forceImmediatePurge Whether we should force an immediate purge or not.
                     * @param int $productId The product to be purged cache for.
                     * @param string $context The context of the purge.
                     */
                    if (apply_filters(
                        'sb_optimizer_woocommerce_force_immediate_purge',
                        true,
                        $productId,
                        'woocommerce_product_stock_change'
                    )) {
                        WordPressCachePurge::purgeImmediately();
                    }

                    /**
                     * Determine whether we should do a simple purge whenever the product stock changes.
                     *
                     * @param bool $doSimplePurge Whether we should do a simple purge or not.
                     * @param int $productId The product to be purged cache for.
                     * @param string $context The context of the purge.
                     */
                    if (apply_filters(
                        'sb_optimizer_woocommerce_do_simple_purge',
                        true,
                        $productId,
                        'woocommerce_product_stock_change'
                    )) {
                        WordPressCachePurge::purgePostCacheSimple($productId);
                    } else {
                        WordPressCachePurge::purgeByPostId($productId);
                    }
                } catch (Throwable $e) {}
            }
        }
    }

    /**
     * Purge cache for product whenever variation stock amount changes.
     *
     * @param WC_Product_Variation $productVariation
     */
    public function productVariationStockChange($productVariation): void
    {
        if ($product = $this->resolveProductFromProductVariation($productVariation)) {
            $this->productStockChange($product);
        }
    }

    /**
     * Purge cache for product whenever stock status changes.
     *
     * @param int $productId
     * @param string $stockStatus
     * @param WC_Product|WC_Product_Simple|WC_Product_Grouped|WC_Product_External|WC_Product_Variable $product
     */
    public function productStockStatusChange($productId, $stockStatus, $product): void
    {
        $this->productStockChange($product);
    }

    /**
     * @param int $productVariationId
     * @param string $stockStatus
     * @param WC_Product_Variation $productVariation
     */
    public function productVariationStockStatusChange($productVariationId, $stockStatus, $productVariation): void
    {
        $this->productVariationStockChange($productVariation);
    }

    /**
     * Check whether we should purge cache on stock amount change.
     *
     * @return bool
     */
    private function shouldPurgeCacheOnStockAmountChange(): bool
    {
        if (apply_filters('sb_optimizer_woocommerce_product_cache_purge_on_stock_amount_change', true) === false) {
            return false; // We're not supposed to purge cache on WooCommerce stock change
        }
        return $this->shouldPurgeCacheOnStockCommonCondition();
    }

    /**
     * Check whether we should purge cache on stock status change.
     *
     * @return bool
     */
    private function shouldPurgeCacheOnStockStatusChange(): bool
    {
        if (apply_filters('sb_optimizer_woocommerce_product_cache_purge_on_stock_status_change', true) === false) {
            return false; // We're not supposed to purge cache on WooCommerce stock status change
        }
        return $this->shouldPurgeCacheOnStockCommonCondition();
    }

    /**
     * Do check if we should purge cache on stock change (regardless of stock change or stock status change).
     *
     * @return bool
     */
    private function shouldPurgeCacheOnStockCommonCondition(): bool
    {
        if (!CachePurge::featureIsAvailable()) {
            return false; // Cache feature is not available or insufficiently configured
        }

        if (apply_filters('sb_optimizer_woocommerce_product_cache_purge_on_stock_change', true) === false) {
            return false; // We're not supposed to purge cache on WooCommerce stock status change
        }

        /*
        if ($this->isWooCommerceCheckout()) {
            return true; // We're doing a WooCommerce checkout, let's update the stock
        }
        */

        //return false;
        return true;
    }

    /**
     * Check if we're currently doing a WooCommerce checkout.
     *
     * @return bool
     */
    /*
    private function isWooCommerceCheckout(): bool
    {
        if (is_admin()) {
            return false;
        }
        if (function_exists('is_checkout')) {
            return is_checkout();
        }
        return false;
    }
    */

    /**
     * Get post Id from WC product.
     *
     * @param WC_Product|WC_Product_Simple|WC_Product_Grouped|WC_Product_External|WC_Product_Variable $product
     * @return null|int
     */
    private function resolveProductPostId($product): ?int
    {
        if (
            is_object($product)
            && method_exists($product, 'get_id')
        ) {
            if ($productId = $product->get_id()) {
                return (int) $productId;
            }
        }
        return null;
    }

    /**
     * Get product from product variation.
     *
     * @param WC_Product_Variation $productVariation
     * @return null|WC_Product|WC_Product_Simple|WC_Product_Grouped|WC_Product_External|WC_Product_Variable
     */
    private function resolveProductFromProductVariation($productVariation): ?object
    {
        if (
            is_object($productVariation)
            && method_exists($productVariation, 'get_parent_id')
        ) {
            if (
                function_exists('wc_get_product')
                && $productId = $productVariation->get_parent_id()
            ) {
                if ($product = wc_get_product($productId)) {
                    return $product;
                }
            }
        }
        return null;
    }
}
