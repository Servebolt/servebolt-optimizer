<?php

namespace Servebolt\Optimizer\Compatibility\WooCommerce;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class InstantPageURLs
 * @package Servebolt\Optimizer\Compatibility\WooCommerce
 */

class InstantPageURLs {

    /**
     * @var 
     * 
     * ip (instant page) = 1 (true)
     */
    protected $query_string = "?ip=1";

    /**
     * Add filters to urls that we never want InstantPages to prefetch.
     * 
     * Query strings always prevent prefechting unless they are specifically whitelisted.
     * 
     * @since 3.5.11
     */
    public function __construct()
    {
        if (!apply_filters('sb_optimizer_woocommerce_instantpage_urls', true)) {
            return;            
        }

        add_filter('woocommerce_get_cart_url', [$this, 'addQueryString']);
        add_filter('woocommerce_get_checkout_url', [$this, 'addQueryString']);
    }

    /**
     * Add a query string to the cart and checkout pages if theres
     * not one already.
     * 
     * @since 3.5.11
     * @param string $url of woocommerce page
     * @return string url
     */
    public function addQueryString($url)
    {
        // if there already is a url with a query string on it, ignore
        if(!empty(parse_url($url, PHP_URL_QUERY))) return $url;
        // else append a simple query string.
        return $url . $this->query_string;
    }

}
