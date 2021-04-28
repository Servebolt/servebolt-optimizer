<?php

namespace Servebolt\Optimizer\Compatibility\Cloudflare;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Admin\GeneralSettings\GeneralSettings;

/**
 * Class Apo
 * @package Servebolt\Optimizer\Compatibility\Cloudflare
 */
class Apo
{
    /**
     * Apo constructor.
     */
    public function __construct()
    {

        if ($this->cfApoActive()) {
            add_action('sb_optimizer_fpc_cache_headers', function ($fpc) {
                $fpc->header('cf-edge-cache: cache, platform=wordpress');
            });
            if (apply_filters('sb_optimizer_send_apo_no_cache_headers', true)) {
                add_action('sb_optimizer_fpc_no_cache_headers', function ($fpc) {
                    $fpc->header('cf-edge-cache: no-cache, platform=wordpress');
                });
            }
        }
    }

    /**
     * Whether to use the Cloudflare APO-feature.
     *
     * @return bool
     */
    private function cfApoActive(): bool
    {
        $generalSettings = GeneralSettings::getInstance();
        return $generalSettings->useCloudflareApo();
    }
}
