<?php

namespace Servebolt\Optimizer\Admin\ClearSiteDataHeader;

if (!defined('ABSPATH')) exit;

/**
 * Class ClearSiteDataHeader
 * @package Servebolt\Optimizer\Admin\ClearSiteDataHeader
 */
class ClearSiteDataHeader
{
    public function __construct()
    {
        add_action('wp_login', [$this, 'flagLogin'], 10, 2);
    }

    public function flagLogin($user_login, $user)
    {
        // Set a cookie to indicate we just logged in and need to clear site data
        setcookie('clear_site_data', '1', time() + 3600, '/', '', is_ssl(), true);
    }
}
