<?php

namespace Servebolt\Optimizer\Admin\GeneralSettings\Ajax;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Admin\SharedAjaxMethods;
use function Servebolt\Optimizer\Helpers\deleteAllSettings;
use function Servebolt\Optimizer\Helpers\ajaxUserAllowed;
use function Servebolt\Optimizer\Helpers\isDevDebug;

/**
 * Class GeneralSettingsActions
 * @package Servebolt\Optimizer\Admin\GeneralSettings\Ajax
 */
class GeneralSettingsActions extends SharedAjaxMethods
{

    /**
     * GeneralSettingsActions constructor.
     */
    public function __construct()
    {
        if (isDevDebug()) {
            add_action('wp_ajax_servebolt_clear_all_settings', [$this, 'clearAllSettingsCallback']);
        }
    }

    /**
     * Clear all plugin settings.
     */
    public function clearAllSettingsCallback(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();
        deleteAllSettings();
        wp_send_json_success();
    }
}
