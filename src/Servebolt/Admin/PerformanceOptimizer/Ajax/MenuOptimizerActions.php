<?php

namespace Servebolt\Optimizer\Admin\PerformanceOptimizer\Ajax;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\MenuOptimizer\MenuOptimizer;
use Servebolt\Optimizer\Admin\SharedAjaxMethods;
use function Servebolt\Optimizer\Helpers\ajaxUserAllowed;

/**
 * Class MenuOptimizerActions
 * @package Servebolt\Optimizer\Admin\PerformanceOptimizer\Ajax
 */
class MenuOptimizerActions extends SharedAjaxMethods
{

    /**
     * MenuOptimizerActions constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_servebolt_menu_optimizer_purge_all', [$this, 'purgeAll']);
    }

    /**
     * Purge all menu optimization cache.
     */
    public function purgeAll()
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();
        MenuOptimizer::purgeCache();
        wp_send_json_success();
    }
}
