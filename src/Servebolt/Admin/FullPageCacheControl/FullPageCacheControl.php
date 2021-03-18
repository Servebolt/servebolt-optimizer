<?php

namespace Servebolt\Optimizer\Admin\FullPageCacheControl;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Servebolt\Optimizer\Admin\FullPageCacheControl\Ajax\FpcPostExclusion;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\view;
use function Servebolt\Optimizer\Helpers\sbGetAdminUrl;

/**
 * Class Nginx_FPC_Controls
 *
 * This class displays the Nginx Full Page Cache control GUI  - only works for sites hosted at Servebolt.
 */
class FullPageCacheControl
{
    use Singleton;

    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * Nginx_FPC_Controls constructor.
     */
    private function __construct() {
        $this->initSettings();
        $this->initAssets();
        $this->initAjax();
    }

    /**
     * Register AJAX handling.
     */
    private function initAjax(): void
    {
        new FpcPostExclusion;
    }

    /**
     * Init assets.
     */
    private function initAssets() {
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    /**
     * Plugin scripts.
     */
    public function enqueueScripts(): void
    {
        $screen = get_current_screen();
        if ( $screen->id != 'servebolt_page_servebolt-fpc' ) {
            return;
        }
        wp_enqueue_script('servebolt-optimizer-fpc-scripts', SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/js/fpc.js', ['servebolt-optimizer-scripts'], filemtime(SERVEBOLT_PLUGIN_DIR_PATH . 'assets/dist/js/fpc.js'), true );
    }

    /**
     * Initialize settings.
     */
    private function initSettings() {
        add_action( 'admin_init', [$this, 'registerSettings'] );
    }

    /**
     * Register custom option.
     */
    public function registerSettings(): void
    {
        foreach(['fpc_settings', 'fpc_switch'] as $key) {
            register_setting('fpc-options-page', sb_get_option_name($key));
        }
    }

    public function render(): void
    {
        $sbAdminUrl = sbGetAdminUrl();
        view('full-page-cache.full-page-cache', compact('sbAdminUrl'));
    }
}
