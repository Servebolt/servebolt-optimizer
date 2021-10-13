<?php

namespace Servebolt\Optimizer\Compatibility\EasyDigitalDownloads;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class CacheExceptionRules
 * @package Servebolt\Optimizer\Compatibility\EasyDigitalDownloads
 */
class CacheExceptionRules
{
    use PageConditionsTrait;

    /**
     * CacheExceptionRules constructor.
     */
    public function __construct()
    {
        add_filter('sb_optimizer_fpc_ecommerce_pages_no_cache_bool', [$this, 'noCacheCheck'], 10, 1);
        add_filter('sb_optimizer_fpc_ecommerce_pages_cache_bool', [$this, 'cacheCheck'], 10, 1);
    }

    /**
     * Check if we are in an EDD-context and check if we should not cache.
     *
     * @param null|boolean $shouldNotCache
     * @return bool
     */
    public function noCacheCheck($shouldNotCache): bool
    {
        if (is_null($shouldNotCache)) {
            return edd_is_checkout() || edd_is_success_page() && edd_is_purchase_history_page();
        }
        return $shouldNotCache;
    }

    /**
     * Check if we are in an EDD-context and check if we should cache.
     *
     * @param null|boolean $shouldCache
     * @return bool
     */
    public function cacheCheck($shouldCache): bool
    {
        if (is_null($shouldCache)) {
            return edd_is_failed_transaction_page() || $this->isShop() || $this->isProductCategory() || $this->isProductTag() || $this->isProduct();
        }
        return $shouldCache;
    }
}
