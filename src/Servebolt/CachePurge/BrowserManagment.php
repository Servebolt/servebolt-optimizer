<?php

namespace Servebolt\Optimizer\CachePurge;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;


/**
 * Class CachePurge
 *
 * This class will resolve the cache purge driver, and forward the cache purge request to it.
 *
 * @package Servebolt\Optimizer\CachePurge
 */
class BrowserManagment
{
    use Singleton;

    /**
     * Alias for "getInstance".
     */
    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * WpDatabaseMigrations constructor.
     */
    public function __construct()
    {
        add_action('wp_footer', [$this, 'wp_footer']);
        add_action('admin_footer', [$this, 'wp_footer']);
        add_action('login_footer', [$this, 'wp_footer']);
        add_action('init', [$this, 'clear_chrome_cache']);
    }

    public function clear_chrome_cache() {
        if (
            $_SERVER['REQUEST_METHOD'] == 'GET' &&
            isset($_GET['clear_site_data']) &&
            is_user_logged_in() &&
            $this->get_browser()== 'chrome'
        ) {
            header('Clear-Site-Data: "cache", "storage"');
            header('Content-Type: application/javascript');
            echo '/* cache cleared */';
            exit;
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
        $shouldReload = false;
        if( defined('SB_OPTIMIZER_CLEAR_CACHE_RELOAD_CHROME') ) {
            $reload = SB_OPTIMIZER_CLEAR_CACHE_RELOAD_CHROME;
        }

        ?>
        <script>
        (function() {
            if ( /Chrome|Chromium|Edg|OPR|SamsungBrowser|CriOS/.test(navigator.userAgent) && 
            document.cookie.includes('clear_browser_cache=1')) 
            {
                document.cookie = 'clear_browser_cache=1; Max-Age=0; path=/';
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