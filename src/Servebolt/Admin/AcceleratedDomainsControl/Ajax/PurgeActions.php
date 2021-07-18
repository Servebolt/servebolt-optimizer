<?php

namespace Servebolt\Optimizer\Admin\AcceleratedDomainsControl\Ajax;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class PurgeActions
 * @package Servebolt\Optimizer\Admin\AcceleratedDomainsControl\Ajax
 */
class PurgeActions
{
    /**
     * PurgeActions constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_servebolt_acd_purge_all_cache', [$this, 'acdPurgeAllCacheCallback']);
    }

    public function acdPurgeAllCacheCallback(): void
    {
        
        wp_send_json_success();
    }
}
