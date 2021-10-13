<?php

namespace Servebolt\Optimizer\Compatibility\WooCommerce;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class CacheExceptionRules
 * @package Servebolt\Optimizer\Compatibility\WooCommerce
 */
class CacheExceptionRules
{
    /**
     * CacheExceptionRules constructor.
     */
    public function __construct()
    {
        add_filter('sb_optimizer_fpc_ecommerce_pages_no_cache_bool', [$this, 'noCacheCheck'], 10, 1);
        add_filter('sb_optimizer_fpc_ecommerce_pages_cache_bool', [$this, 'cacheCheck'], 10, 1);
    }

    /**
     * Check if we are in a WooCommerce-context and check if we should not cache.
     *
     * @param null|boolean $shouldNotCache
     * @return bool
     */
    public function noCacheCheck($shouldNotCache): bool
    {
        if (is_null($shouldNotCache)) {
            return is_cart() || is_checkout() || is_account_page();
        }
        return $shouldNotCache;
    }

    /**
     * Check if we are in a WooCommerce-context and check if we should cache.
     *
     * @param null|boolean $shouldCache
     * @return bool
     */
    public function cacheCheck($shouldCache): bool
    {
        if (is_null($shouldCache)) {
            return is_shop() || is_product_category() || is_product_tag() || is_product();
        }
        return $shouldCache;
    }
}
