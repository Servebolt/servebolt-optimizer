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
        add_action('wp_footer', [$this, 'wp_footer']);
        add_action('admin_footer', [$this, 'wp_footer']);
        add_action('login_footer', [$this, 'wp_footer']);

    }

    public function flagLogin($user_login, $user)
    {
        // Set a transient or cookie to indicate we just logged in
        setcookie('clear_site_data', '1', time() + 100, '/', '', is_ssl(), true);
    }

    public function maybeSetHeader()
    {
        if (!empty($_COOKIE['clear_site_data']) && 
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

    if (strpos($userAgent, 'safari') !== false && strpos($userAgent, 'chrome') === false) return 'safari';
    if (strpos($userAgent, 'firefox') !== false) return 'firefox';
    if (strpos($userAgent, 'msie') !== false || strpos($userAgent, 'trident/') !== false) return 'internet explorer';

    return 'unknown';
    }

    function wp_footer() {
        if (!is_user_logged_in()) return;

        $shouldReload = false;
        if( defined('SB_OPTIMIZER_CLEAR_CACHE_RELOAD_CHROME') ) {
            $reload = SB_OPTIMIZER_CLEAR_CACHE_RELOAD_CHROME;
        }

        ?>
        <script>
        (function() {
            const ua = navigator.userAgent;
            const isChrome = /Chrome/.test(ua) && !/Edg|OPR|SamsungBrowser|CriOS/.test(ua);

            if (isChrome && document.cookie.includes('clear_cache=1')) {
                document.cookie = 'clear_cache=1; Max-Age=0; path=/';
                fetch('/?clear_site_data=1', { credentials: 'include' })
                    <?php if ($shouldReload): ?>
                    .then(() => location.reload(true));
                    <?php endif; ?>
            }
        })();
        </script>
    <?php
    }
}
