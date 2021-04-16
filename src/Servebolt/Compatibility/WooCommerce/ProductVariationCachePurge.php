<?php

namespace Servebolt\Optimizer\Compatibility\WooCommerce;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class ProductVariationCachePurge
 * @package Servebolt\Optimizer\Compatibility\WooCommerce
 */
class ProductVariationCachePurge
{

    /**
     * ProductVariationCachePurge constructor.
     */
    public function __construct()
    {
        if (apply_filters('sb_optimizer_woocommerce_cache_purge_for_variations', true) === false) {
            return false; // We're not suppose to purge cache for variations
        }
        add_action('sb_optimizer_term_cache_purge_3rd_party_urls_post_type_product', [$this, 'addProductVariationUrls'], 10, 2);
    }

    /**
     * Add variations.
     *
     * @param $productId
     * @param $purgeObject
     */
    public function addProductVariationUrls($productId, $purgeObject): void
    {
        if (!function_exists('wc_get_product')) {
            return;
        }
        if ($product = wc_get_product($productId)) {
            if (!$product->is_type('variable')) {
                return;
            }
            $variations = $product->get_available_variations();
            if (!is_array($variations)) {
                return;
            }
            foreach ($variations as $variation) {
                if ($variationUrl = $variation->get_permalink()) {
                    $purgeObject->addUrl($variationUrl);
                }
            }
        }
    }
}


