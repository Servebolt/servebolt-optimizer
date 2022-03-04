<?php

namespace Servebolt\Optimizer\Admin;

if (!defined('ABSPATH')) exit;

use Servebolt\Optimizer\Admin\CachePurgeControl\Ajax\PurgeActions;
use Servebolt\Optimizer\Admin\CachePurgeControl\CachePurgeControl;
use Servebolt\Optimizer\Admin\GeneralSettings\GeneralSettings;
use Servebolt\Optimizer\CachePurge\CachePurge;
use function Servebolt\Optimizer\Helpers\booleanToString;
use function Servebolt\Optimizer\Helpers\getAjaxNonce;
use function Servebolt\Optimizer\Helpers\getVersionForStaticAsset;
use function Servebolt\Optimizer\Helpers\isDevDebug;

/**
 * Class Assets
 *
 * This class includes the CSS and JavaScript of the plugin.
 */
class Assets {

	/**
	 * Assets constructor.
	 */
	public function __construct()
    {
		add_action('init', [$this, 'initAssets']);
	}

    /**
     * Determine whether we should enqueue plugin assets in general.
     *
     * @return bool
     */
	private function shouldInitAssets(): bool
    {
        return apply_filters('sb_optimizer_should_init_assets', is_user_logged_in());
    }

	/**
	 * Init assets.
	 */
	public function initAssets(): void
    {
        if (!$this->shouldInitAssets()) {
            return;
        }

		// Front-end only assets
		add_action('wp_enqueue_scripts', [$this, 'pluginPublicStyling']);
		add_action('wp_enqueue_scripts', [$this, 'pluginPublicScripts']);

        // Admin only assets
        add_action('admin_enqueue_scripts', [$this, 'pluginAdminStyling']);
        add_action('admin_enqueue_scripts', [$this, 'pluginAdminScripts']);

		// Common assets (both public and admin)
		add_action('wp_enqueue_scripts', [$this, 'pluginCommonStyling']);
		add_action('wp_enqueue_scripts', [$this, 'pluginCommonScripts']);
		add_action('admin_enqueue_scripts', [$this, 'pluginCommonStyling']);
        add_action('admin_enqueue_scripts', [$this, 'pluginCommonScripts']);

	}

	/**
	 * Plugin styling (public only).
	 */
	public function pluginPublicStyling(): void
    {
        $this->enqueueStyle(
            'servebolt-optimizer-public-styling',
            'assets/dist/css/public-style.css'
        );
	}

    /**
     * Plugin styling (admin only).
     */
    public function pluginAdminStyling(): void
    {
        $this->enqueueStyle(
            'servebolt-optimizer-styling',
            'assets/dist/css/admin-style.css'
        );
        if ($this->isBlockEditor() && apply_filters('sb_optimizer_add_block_editor_plugin_menu', true)) {
            $this->enqueueStyle(
                'servebolt-optimizer-block-editor-menu-styling',
                'assets/dist/css/block-editor-menu.css'
            );
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
                $this->enqueueStyle(
                    'sb-sweetalert2',
                    'assets/dist/css/sweetalert2.min.css'
                );
            }
            $this->enqueueStyle(
                'servebolt-optimizer-common-styling',
                'assets/dist/css/common-style.css'
            );
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
        if (
            $this->isBlockEditor()
            && apply_filters('sb_optimizer_add_block_editor_plugin_menu', true)
        ) {
            $this->enqueueScript(
                'servebolt-optimizer-block-editor-cache-purge-menu-scripts',
                'assets/dist/js/block-editor-cache-purge-menu.js',
                [],
                true
            );
            $cacheFeatureActive = CachePurge::featureIsAvailable();
            $postId = CachePurgeControl::getSinglePostId();
            wp_localize_script('servebolt-optimizer-block-editor-cache-purge-menu-scripts', 'sb_ajax_object_block_editor_menu', [
                'cacheFeatureActive' => $cacheFeatureActive,
                'canPurgePostCache' => $cacheFeatureActive && $postId && PurgeActions::canPurgePostCache($postId),
                'canPurgeAllCache' => $cacheFeatureActive && PurgeActions::canPurgeAllCache(),
                'canPurgeCacheByUrl' => $cacheFeatureActive && PurgeActions::canPurgeCacheByUrl(),
            ]);
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
                $this->enqueueScript(
                    'sb-sweetalert2',
                    'assets/dist/js/sweetalert2.all.min.js',
                    [],
                    true
                );
            }
            $this->enqueueScript(
                'servebolt-optimizer-scripts',
                'assets/dist/js/general.js',
                ['jquery'],
                true
            );

            $this->enqueueScript(
                'servebolt-optimizer-env-config',
                'assets/dist/js/env-config.js',
                [],
                true
            );
            $this->enqueueScript(
                'servebolt-optimizer-scripts-vanilla',
                'assets/dist/js/general-vanilla.js',
                [],
                true
            );
            $this->enqueueScript(
                'servebolt-optimizer-cache-purge-trigger-scripts',
                'assets/dist/js/cache-purge-trigger.js',
                ['jquery'],
                true
            );
            wp_localize_script('servebolt-optimizer-cache-purge-trigger-scripts', 'servebolt_optimizer_ajax_object', [
                'is_dev'                 => isDevDebug(),
                'ajax_nonce'             => getAjaxNonce(),
                'site_url'               => get_site_url(),
                'use_native_js_fallback' => booleanToString($generalSettings->useNativeJsFallback()),
                'ajaxurl'                => admin_url('admin-ajax.php'),
                'cron_purge_is_active'   => false, // TODO: Add real boolean value
                //'cron_purge_is_active'   => sb_cf_cache()->cron_purge_is_active(),
            ]);
        }
	}

    /**
     * Check whether current screen is the block editor.
     *
     * @return bool
     */
    private function isBlockEditor(): bool
    {
        $currentScreen = get_current_screen();
        return $currentScreen && method_exists($currentScreen, 'is_block_editor') && $currentScreen->is_block_editor();
    }

    /**
     * Enqueue script.
     *
     * @param string $handle
     * @param string $src
     * @param array $deps
     * @param bool $in_footer
     */
    private function enqueueScript($handle, $src, $deps = [], $in_footer = false): void
    {
        wp_enqueue_script($handle, SERVEBOLT_PLUGIN_DIR_URL . $src, $deps, getVersionForStaticAsset(SERVEBOLT_PLUGIN_DIR_PATH . $src), $in_footer);
    }

    /**
     * Enqueue style.
     *
     * @param string $handle
     * @param string $src
     * @param array $deps
     */
    private function enqueueStyle($handle, $src, $deps = []): void
    {
        wp_enqueue_style($handle, SERVEBOLT_PLUGIN_DIR_URL . $src, $deps, getVersionForStaticAsset(SERVEBOLT_PLUGIN_DIR_PATH . $src));
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
