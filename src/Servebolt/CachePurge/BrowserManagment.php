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
        add_action('init', [$this, 'clear_site_data']);
    }

    public function clear_site_data()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['clear_site_data'])) {
            header('Clear-Site-Data: "cache", "storage"');
            header('Content-Type: application/javascript');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            echo 'Clear-Site-Data: "cache", "storage" header sent. Browser cache is now cleared.';

            setcookie('clear_site_data', '', time() - 3600, '/', '', is_ssl(), false);
            exit;
        }
    }

    function wp_footer()
    {
        $shouldReload = false;
        if (defined('SB_OPTIMIZER_CLEAR_CACHE_RELOAD_CHROME')) {
            $shouldReload = SB_OPTIMIZER_CLEAR_CACHE_RELOAD_CHROME;
        }

        ?>
        <script>
            (function() {
                if (document.cookie.includes('clear_site_data=1')) {
                    fetch('/?clear_site_data', {credentials: 'include'})<?php if ($shouldReload): ?>.then(() => location.reload(true));<?php endif; ?>
                }
            })();
        </script>
        <?php
    }
}
