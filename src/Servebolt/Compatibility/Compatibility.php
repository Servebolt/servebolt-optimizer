<?php

namespace Servebolt\Optimizer\Compatibility;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Compatibility\Cloudflare\Cloudflare as CloudflareCompatibility;
use Servebolt\Optimizer\Compatibility\WooCommerce\WooCommerce as WooCommerceCompatibility;
use Servebolt\Optimizer\Compatibility\WpRocket\WpRocket as WpRocketCompatibility;
use Servebolt\Optimizer\Compatibility\YoastPremium\YoastPremium as YoastPremiumCompatibility;

/**
 * Class Compatibility
 * @package Servebolt\Optimizer\Compatibility
 */
class Compatibility
{
    /**
     * Compatibility constructor.
     */
    public function __construct()
    {
        new WooCommerceCompatibility;
        new WpRocketCompatibility;
        new CloudflareCompatibility;
        new YoastPremiumCompatibility;
    }
}
