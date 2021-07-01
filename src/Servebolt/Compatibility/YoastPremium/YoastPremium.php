<?php

namespace Servebolt\Optimizer\Compatibility\YoastPremium;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\yoastSeoPremiumIsActive;

/**
 * Class YoastPremium
 * @package Servebolt\Optimizer\Compatibility\Yoast
 */
class YoastPremium
{
    /**
     * YoastPremium constructor.
     */
    public function __construct()
    {
        if (!apply_filters('sb_optimizer_yoast_seo_premium_compatibility', true)) {
            return;
        }
        if (!yoastSeoPremiumIsActive()) {
            return;
        }
        new RedirectCachePurge;
    }
}
