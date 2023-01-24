<?php

namespace Servebolt\Optimizer\Admin\ClearSiteDataHeader;

if (!defined('ABSPATH')) exit;

/**
 * Class ClearSiteDataHeader
 * @package Servebolt\Optimizer\Admin\ClearSiteDataHeader
 */
class ClearSiteDataHeader
{
    /**
     * ClearSiteDataHeader constructor.
     */
    public function __construct()
    {
        add_action('wp_login', [$this, 'setHeader'], 10, 0);
    }

    /**
     * Set header to clear local storage and browser cache during login.
     */
    public function setHeader(): void
    {
        if (apply_filters('sb_optimizer_clear_site_data_header_active', true)) {
            error_log('settings header');
            header('Clear-Site-Data: "cache", "storage"');
        }
    }
}
