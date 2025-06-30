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
        add_action('template_redirect', [$this, 'maybeSetHeader']);


    }

    

    public function flagLogin($user_login, $user)
    {
        // Set a transient or cookie to indicate we just logged in
        if($this->get_browser()== 'firefox') {
            setcookie('clear_site_data', '1', time() + 3600, '/', '', is_ssl(), true);
        } else {
            setcookie('clear_browser_cache', '1', time() + 3600, '/', '', is_ssl(), false);
        } 
    }

    public function maybeSetHeader()
    {
        if (
            $_SERVER['REQUEST_METHOD'] == 'GET' &&
            !empty($_COOKIE['clear_site_data']) && 
            is_user_logged_in() &&
            $this->get_browser()== 'firefox') {
            header('Clear-Site-Data: "cache", "storage"');
            // Clear the cookie immediately after use
            setcookie('clear_site_data', '', time() - 3600, '/', '', is_ssl(), true);
        }
    }

    protected function get_browser($userAgent = null) {
        if ($userAgent === null) {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return 'unknown';
        }
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
    }

    $userAgent = strtolower($userAgent);

    if (strpos($userAgent, 'safari') !== false && strpos($userAgent, 'chrome') === false) return 'safari';
    // Detect Chromium-based browsers
    if (
        strpos($userAgent, 'chrome') !== false ||
        strpos($userAgent, 'crios') !== false ||         // Chrome on iOS
        strpos($userAgent, 'edg/') !== false ||          // Edge
        strpos($userAgent, 'opr/') !== false ||          // Opera
        strpos($userAgent, 'brave') !== false ||         // Brave
        strpos($userAgent, 'chromium') !== false
    ) {
        return 'chrome';
    }

    if (strpos($userAgent, 'firefox') !== false) return 'firefox';
    if (strpos($userAgent, 'msie') !== false || strpos($userAgent, 'trident/') !== false) return 'internet explorer';

    return 'unknown';
    }

}
