<?php

namespace Servebolt\Optimizer\Admin;

use Servebolt\Optimizer\Admin\CachePurge\CachePurge;
use Servebolt\Optimizer\Traits\Singleton;

class AdminGuiController
{

    use Singleton;

    /**
     * AdminGuiController constructor.
     */
    public function __construct()
    {
        add_action('init', [$this, 'adminInit']);
    }

    /**
     * Admin init.
     */
    public function adminInit()
    {
        if ( ! is_user_logged_in() ) {
            return;
        }
        $this->initAdminMenus();
        $this->initPluginSettingsLink();
        $this->initSettings();

        CachePurge::init();
    }

    /**
     * Initialize settings.
     */
    private function initSettings() {
        add_action( 'admin_init', [$this, 'registerSettings'] );
    }

    /**
     * Register custom options.
     */
    public function registerSettings(): void
    {
        foreach($this->getSettingsItems() as $key) {
            register_setting('sb-cf-options-page', sb_get_option_name($key));
        }
    }

    /**
     * Get all plugin settings in array.
     *
     * @return array
     */
    public function getSettingsItemsWithValues(): array
    {
        $items = $this->getSettingsItems();
        $itemsWithValues = [];
        foreach ( $items as $item ) {
            switch ($item) {
                /*
                case 'cf_switch':
                    $itemsWithValues[$item] = sb_cf_cache()->cf_is_active();
                    break;
                */
                case 'cache_purge_driver':
                    $value = sb_get_option($item);
                    $itemsWithValues['cache_purge_driver'] = $value ?: $this->getDefaultCachePurgeDriver();
                    break;
                case 'cf_auth_type':
                    $value = sb_get_option($item);
                    $itemsWithValues['cf_auth_type'] = $value ?: $this->getDefaultCfAuthType();
                    break;
                default:
                    $itemsWithValues[$item] = sb_get_option($item);
                    break;
            }
        }
        return $itemsWithValues;
    }

    /**
     * @return string
     */
    private function getDefaultCfAuthType(): string
    {
        return 'api_token';
    }

    /**
     * @return string
     */
    private function getDefaultCachePurgeDriver(): string
    {
        return 'cloudflare';
    }

    /**
     * Settings items for CF cache.
     *
     * @return array
     */
    private function getSettingsItems(): array
    {
        return [
            'cache_purge_switch',
            'cache_purge_driver',
            'cf_zone_id',
            'cf_auth_type',
            'cf_email',
            'cf_api_key',
            'cf_api_token',
            'cf_cron_purge',
        ];
    }

    /**
     * Init admin menus.
     */
    private function initAdminMenus(): void
    {

        // Multisite setup
        if (is_multisite()) {

            // Network admin menu setup
            if (is_network_admin() && is_super_admin()) { // Only allow super admins to access network context settings
                add_action('network_admin_menu', [$this, 'networkAdminMenu']);
            }

            // Sub-site admin menu setup
            add_action('admin_menu', [$this, 'subSiteMenu']);

        } else {

            // Single-site admin menu setup
            add_action('admin_menu', [$this, 'singleSiteAdminMenu']);

        }
    }

    /**
     * Network admin menu.
     */
    public function networkAdminMenu()
    {
        if (!apply_filters('sb_optimizer_display_network_super_admin_menu', true)) {
            return;
        }

        $this->generalPageMenuPage(); // Register menu page

        $this->generalMenu();
        $this->performanceOptimizerMenu();
        $this->addSubMenuItems();
    }

    /**
     * Admin menu for sub sites (in multisite context).
     */
    public function subSiteMenu()
    {
        if ( ! apply_filters('sb_optimizer_display_subsite_menu', true) ) return;

        $this->generalPageMenuPage(); // Register menu page

        $this->generalMenu();
        $this->addSubMenuItems();
    }

    /**
     * Admin menu (in non-multisite context).
     */
    public function singleSiteAdminMenu()
    {
        if ( ! apply_filters('sb_optimizer_display_single_site_admin_menu', true) ) return;

        $this->generalPageMenuPage(); // Register menu page

        $this->generalMenu();
        $this->performanceOptimizerMenu();
        $this->addSubMenuItems();
    }

    /**
     * Shared menu items.
     */
    private function addSubMenuItems(): void
    {
        $this->cachePurgeMenu();
        $this->cfImageResizeMenu();
        if ( host_is_servebolt() ) {
            $this->fpcCacheMenu();
            $this->errorLogMenu();
        }
        $this->generalSettingsMenu();
        if ( ! is_network_admin() && sb_is_dev_debug() ) {
            $this->debugMenu();
        }
    }

    /**
     * Register Servebolt menu page.
     */
    private function generalPageMenuPage(): void
    {
        add_menu_page( sb__('Servebolt'), sb__('Servebolt'), 'manage_options', 'servebolt-wp', [$this, 'generalPageCallback'], SERVEBOLT_PATH_URL . 'assets/dist/images/servebolt-icon.svg' );
    }

    /**
     * Register CF cache menu item.
     */
    private function cachePurgeMenu(): void
    {
        add_submenu_page('servebolt-wp', sb__('Cache purging'), sb__('Cache purging'), 'manage_options', 'servebolt-cache-purge-control', [CachePurge::getInstance(), 'render']);
        add_submenu_page(null, null, null, 'manage_options', 'servebolt-cf-cache-control', [$this, 'cachePurgeLegacyRedirect']);
    }

    /**
     * Redirect from old cache purge control page to the new one.
     */
    public function cachePurgeLegacyRedirect(): void
    {
        wp_redirect(
            admin_url('admin.php?page=servebolt-cache-purge-control')
        );
    }

    /**
     * Register CF image resize menu item.
     */
    private function cfImageResizeMenu(): void
    {
        if ( sb_feature_active('cf_image_resize') ) {
            add_submenu_page('servebolt-wp', sb__('Cloudflare Image Resizing'), sb__('Cloudflare Image Resizing'), 'manage_options', 'servebolt-cf-image-resizing', [$this, 'cfImageResizingCallback']);
        }
    }

    /**
     * Register full page cache menu item.
     */
    private function fpcCacheMenu(): void
    {
        add_submenu_page('servebolt-wp', sb__('Page Cache'), sb__('Full Page Cache'), 'manage_options', 'servebolt-nginx-cache', [$this, 'nginxCacheCallback']);
    }

    /**
     * Register error log menu item.
     */
    private function errorLogMenu(): void
    {
        add_submenu_page('servebolt-wp', sb__('Error log'), sb__('Error log'), 'manage_options', 'servebolt-logs', [$this, 'errorLogCallback']);
    }

    /**
     * Register debug menu item.
     */
    private function debugMenu(): void
    {
        add_submenu_page('servebolt-wp', sb__('Debug'), sb__('Debug'), 'manage_options', 'servebolt-debug', [$this, 'debugCallback']);
    }

    /**
     * Register performance optimizer menu item.
     */
    private function performanceOptimizerMenu(): void
    {
        add_submenu_page('servebolt-wp', sb__('Performance optimizer'), sb__('Performance optimizer'), 'manage_options', 'servebolt-performance-tools', [$this, 'performanceCallback']);
    }

    /**
     * Register general/dashboard menu item.
     */
    private function generalMenu(): void
    {
        add_submenu_page('servebolt-wp', sb__('General'), sb__('General'), 'manage_options', 'servebolt-wp');
    }

    /**
     * Register general settings menu item.
     */
    private function generalSettingsMenu(): void
    {
        add_submenu_page('servebolt-wp', sb__('Settings'), sb__('Settings'), 'manage_options', 'servebolt-general-settings', [$this, 'generalSettingsCallback']);
    }

    /**
     * Initialize plugin settings link hook.
     */
    private function initPluginSettingsLink(): void
    {
        add_filter('plugin_action_links_' . SERVEBOLT_BASENAME, [$this, 'addSettingsLinkToPlugin']);
    }

    /**
     * Add settings-link in plugin list.
     *
     * @param $links
     *
     * @return array
     */
    public function addSettingsLinkToPlugin($links): array
    {
        $links[] = sprintf('<a href="%s">%s</a>', admin_url( 'options-general.php?page=servebolt-wp' ), sb__('Settings'));
        return $links;
    }

    /**
     * Display Servebolt dashboard.
     */
    public function generalPageCallback(): void
    {
        sb_view('admin/views/dashboard/dashboard');
    }

    /**
     * Display DB optimization page.
     */
    public function performanceCallback(): void
    {
        sb_performance_checks()->view();
    }

    /**
     * Display the Cloudflare Image Resizing control page.
     */
    public function cfImageResizingCallback(): void
    {
        sb_cf_image_resizing()->view();
    }

    /**
     * Display the Full Page Cache control page.
     */
    public function nginxCacheCallback(): void
    {
        sb_nginx_fpc_controls()->view();
    }

    /**
     * Display the general settings control page.
     */
    public function generalSettingsCallback(): void
    {
        sb_general_settings()->view();
    }


    /**
     * Display error log page.
     */
    public function errorLogCallback(): void
    {
        ( Servebolt_Logviewer::get_instance() )->view();
    }

    /**
     * Display debug information.
     */
    public function debugCallback(): void
    {
        sb_view('admin/views/debug');
    }
}
