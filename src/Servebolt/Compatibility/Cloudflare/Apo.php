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
            if (apply_filters('sb_optimizer_send_apo_cache_headers', true)) {
                add_action('sb_optimizer_fpc_cache_headers', function ($cacheObject, $queriedObject) {
                    $cacheObject->header('cf-edge-cache: cache,platform=wordpress');
                }, 10, 2);
            }
            if (apply_filters('sb_optimizer_send_apo_no_cache_headers', true)) {
                add_action('sb_optimizer_fpc_no_cache_headers', function ($cacheObject) {
                    $cacheObject->header('cf-edge-cache: no-cache');
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
