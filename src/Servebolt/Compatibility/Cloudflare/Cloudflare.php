<?php

namespace Servebolt\Optimizer\Compatibility\Cloudflare;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class Cloudflare
 * @package Servebolt\Optimizer\Compatibility\Cloudflare
 */
class Cloudflare
{
    /**
     * Cloudflare constructor.
     */
    public function __construct()
    {
        if (!apply_filters('sb_optimizer_cloudflare_feature_compatibility', true)) {
            return;
        }

        // Add Cloudflare APO-support
        new Apo;
    }
}
