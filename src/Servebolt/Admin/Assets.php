<?php

namespace Servebolt\Optimizer\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

use Servebolt\Optimizer\Admin\GeneralSettings\GeneralSettings;
use function Servebolt\Optimizer\Helpers\booleanToString;
use function Servebolt\Optimizer\Helpers\getAjaxNonce;

/**
 * Class Servebolt_Optimizer_Assets
 *
 * This class includes the CSS and JavaScript of the plugin.
 */
class Assets {

	/**
	 * Servebolt_Optimizer_Assets constructor.
	 */
	public function __construct() {
		add_action('init', [$this, 'initAssets']);
	}

	/**
	 * Init assets.
	 */
	public function initAssets()
    {

		if ( ! is_user_logged_in() ) {
		    return;
        }

		// Front-end only assets
		add_action('wp_enqueue_scripts', [$this, 'pluginPublicStyling'], 100);
		add_action('wp_enqueue_scripts', [$this, 'pluginPublicScripts'], 100);

        // Admin only assets
        add_action('admin_enqueue_scripts', [$this, 'pluginAdminStyling'], 100);
        add_action('admin_enqueue_scripts', [$this, 'pluginAdminScripts'], 100);

		// Common assets (both public and admin)
		add_action('wp_enqueue_scripts', [$this, 'pluginCommonStyling'], 100);
		add_action('wp_enqueue_scripts', [$this, 'pluginCommonScripts'], 100);
		add_action('admin_enqueue_scripts', [$this, 'pluginCommonStyling'], 100);
        add_action('admin_enqueue_scripts', [$this, 'pluginCommonScripts'], 100);

	}

	/**
	 * Plugin styling (public only).
	 */
	public function pluginPublicStyling(): void
    {
		wp_enqueue_style('servebolt-optimizer-public-styling', SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/css/public-style.css', [], filemtime(SERVEBOLT_PLUGIN_DIR_PATH . 'assets/dist/css/public-style.css'));
	}

    /**
     * Plugin styling (admin only).
     */
    public function pluginAdminStyling(): void
    {
        wp_enqueue_style('servebolt-optimizer-styling', SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/css/admin-style.css', [], filemtime(SERVEBOLT_PLUGIN_DIR_PATH . 'assets/dist/css/admin-style.css'));
        if ( $this->isGutenberg() && apply_filters('sb_optimizer_add_gutenberg_plugin_menu', true) ) {
            wp_enqueue_style('servebolt-optimizer-gutenberg-menu-styling', SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/css/gutenberg-menu.css', [], filemtime(SERVEBOLT_PLUGIN_DIR_PATH . 'assets/dist/css/gutenberg-menu.css'));
        }
    }

    /**
     * Plugin styling (styling for both WP Admin and front end).
     */
	public function pluginCommonStyling()
    {
        if ($this->shouldLoadCommonAssets('styling')) {
            $generalSettings = GeneralSettings::getInstance();
            if (!$generalSettings->useNativeJsFallback()) {
                wp_enqueue_style('sb-sweetalert2', SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/css/sweetalert2.min.css', [], filemtime(SERVEBOLT_PLUGIN_DIR_PATH . 'assets/dist/css/sweetalert2.min.css'));
            }
            wp_enqueue_style('servebolt-optimizer-common-styling', SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/css/common-style.css', [], filemtime(SERVEBOLT_PLUGIN_DIR_PATH . 'assets/dist/css/common-style.css'));
        }
	}

    /**
     * Plugin scripts (public only).
     */
	public function pluginPublicScripts(): void {}

    /**
     * Plugin scripts (admin only).
     */
	public function pluginAdminScripts(): void
    {
        if ($this->isGutenberg() && apply_filters('sb_optimizer_add_gutenberg_plugin_menu', true)) {
            wp_enqueue_script('servebolt-optimizer-gutenberg-menu-scripts', SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/js/gutenberg-menu.js', [], filemtime(SERVEBOLT_PLUGIN_DIR_PATH . 'assets/dist/js/gutenberg-menu.js'), true);
        }
    }

    /**
     * Plugin common scripts (scripts for both WP Admin and front end).
     */
	public function pluginCommonScripts()
    {
	    if ($this->shouldLoadCommonAssets('scripts')) {
	        $generalSettings = GeneralSettings::getInstance();
            if (!$generalSettings->useNativeJsFallback()) {
                wp_enqueue_script('sb-sweetalert2', SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/js/sweetalert2.all.min.js', [], filemtime(SERVEBOLT_PLUGIN_DIR_PATH . 'assets/dist/js/sweetalert2.all.min.js'), true);
            }
            wp_enqueue_script('servebolt-optimizer-scripts', SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/js/general.js', ['jquery'], filemtime(SERVEBOLT_PLUGIN_DIR_PATH . 'assets/dist/js/general.js'), true);
            wp_enqueue_script('servebolt-optimizer-cloudflare-cache-purge-trigger-scripts', SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/js/cloudflare-cache-purge-trigger.js', ['jquery'], filemtime(SERVEBOLT_PLUGIN_DIR_PATH . 'assets/dist/js/cloudflare-cache-purge-trigger.js'), true);
            wp_localize_script('servebolt-optimizer-cloudflare-cache-purge-trigger-scripts', 'sb_ajax_object', [
                'ajax_nonce'                         => getAjaxNonce(),
                'use_native_js_fallback'             => booleanToString($generalSettings->useNativeJsFallback()),
                //'cron_purge_is_active'               => sb_cf_cache()->cron_purge_is_active(),
                'cron_purge_is_active'               => false, // TODO: Add real boolean value
                'ajaxurl'                            => admin_url('admin-ajax.php'),
            ]);
        }
	}

    /**
     * Check whether current screen is Gutenberg-editor.
     *
     * @return bool
     */
    private function isGutenberg(): bool
    {
        $currentScreen = get_current_screen();
        return $currentScreen && method_exists($currentScreen, 'is_block_editor') && $currentScreen->is_block_editor();
    }

    /**
     * Check whether we need common scripts present.
     *
     * @param string $type
     * @return bool
     */
    private function shouldLoadCommonAssets(string $type): bool
    {
        $shouldLoadCommonAssets = false;

        // Load if in WP admin
        if (is_admin()) {
            $shouldLoadCommonAssets = true;
        }

        // Load if admin bar is showing
        if (!is_admin() && is_admin_bar_showing()) {
            $shouldLoadCommonAssets = true;
        }

        return apply_filters('sb_optimizer_should_load_common_assets', $shouldLoadCommonAssets, $type);
    }
}
