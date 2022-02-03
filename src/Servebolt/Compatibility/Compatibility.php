<?php

namespace Servebolt\Optimizer\Compatibility;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Compatibility\Cloudflare\Cloudflare as CloudflareCompatibility;
use Servebolt\Optimizer\Compatibility\WooCommerce\WooCommerce as WooCommerceCompatibility;
use Servebolt\Optimizer\Compatibility\WpRocket\WpRocket as WpRocketCompatibility;
use Servebolt\Optimizer\Compatibility\YoastPremium\YoastPremium as YoastPremiumCompatibility;
use Servebolt\Optimizer\Compatibility\Jetpack\Jetpack as JetpackCompatibility;
use Servebolt\Optimizer\Compatibility\EasyDigitalDownloads\EasyDigitalDownloads as EasyDigitalDownloadsCompatibility;
use Servebolt\Optimizer\Compatibility\ActionScheduler\ActionScheduler as ActionSchedulerCompatibility;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;

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
        new JetpackCompatibility;
        //new EasyDigitalDownloadsCompatibility;

        if (isHostedAtServebolt()) {
            new ActionSchedulerCompatibility;
        }
    }
}
