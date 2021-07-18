<?php

namespace Servebolt\Optimizer\Admin\AcceleratedDomainsControl\Ajax;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Admin\SharedAjaxMethods;
use Servebolt\Optimizer\CachePurge\Drivers\Servebolt;
use function Servebolt\Optimizer\Helpers\ajaxUserAllowed;

/**
 * Class PurgeActions
 * @package Servebolt\Optimizer\Admin\AcceleratedDomainsControl\Ajax
 */
class PurgeActions extends SharedAjaxMethods
{
    /**
     * PurgeActions constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_servebolt_acd_purge_all_cache', [$this, 'acdPurgeAllCacheCallback']);
    }

    /**
     * Purge all cache in ACD.
     */
    public function acdPurgeAllCacheCallback(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed(false, '\\Servebolt\\Optimizer\\Admin\\CachePurgeControl\\Ajax\\PurgeActions::canPurgeAllCache');
        $sbDriver = Servebolt::getInstance();
        if (!$sbDriver->configurationOk()) {
            wp_send_json_error([
                'message' => __('The cache purge feature is not active or is not configured correctly, so we could not purge cache.', 'servebolt-wp'),
            ]);
        } elseif ($sbDriver->purgeAll()) {
            wp_send_json_success();
        } else {
            wp_send_json_error([
                'message' => __('Could not purge all cache.', 'servebolt-wp'),
            ]);
        }
    }
}
