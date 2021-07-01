<?php

namespace Servebolt\Optimizer\Admin;

use Servebolt\Optimizer\Admin\AcceleratedDomainsControl\AcceleratedDomainsControl;
use Servebolt\Optimizer\Admin\AcceleratedDomainsImageControl\AcceleratedDomainsImageResizeControl;
use Servebolt\Optimizer\Admin\CachePurgeControl\CachePurgeControl;
use Servebolt\Optimizer\Admin\PrefetchingControl\PrefetchingControl;
use Servebolt\Optimizer\Admin\FullPageCacheControl\FullPageCacheControl;
use Servebolt\Optimizer\Admin\GeneralSettings\GeneralSettings;
use Servebolt\Optimizer\Admin\CloudflareImageResize\CloudflareImageResize;
use Servebolt\Optimizer\Admin\PerformanceChecks\PerformanceChecks;
use Servebolt\Optimizer\Admin\LogViewer\LogViewer;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\view;
use function Servebolt\Optimizer\Helpers\featureIsAvailable;
use function Servebolt\Optimizer\Helpers\isDevDebug;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;

class AdminGuiController
{

    use Singleton;

    /**
     * AdminGuiController constructor.
     */
    public function __construct()
    {
        add_action('init', [$this, 'init']);
        add_action('admin_init', [$this, 'adminInit']);
    }

    /**
     * Admin init.
     */
    public function adminInit()
    {
        $this->initPluginSettingsLink();
    }

    /**
     * Init.
     */
    public function init()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $this->initAdminMenus();
        PrefetchingControl::init();
        AcceleratedDomainsControl::init();
        AcceleratedDomainsImageResizeControl::init();
        CachePurgeControl::init();
        FullPageCacheControl::init();
        GeneralSettings::init();
        CloudflareImageResize::init();
        PerformanceChecks::init();
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
    public function networkAdminMenu(): void
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
    public function subSiteMenu(): void
    {
        if (!apply_filters('sb_optimizer_display_subsite_menu', true)) {
            return;
        }

        $this->generalPageMenuPage(); // Register menu page

        $this->generalMenu();
        $this->addSubMenuItems();
    }

    /**
     * Admin menu (in non-multisite context).
     */
    public function singleSiteAdminMenu(): void
    {
        if (!apply_filters('sb_optimizer_display_single_site_admin_menu', true)) {
            return;
        }

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
        $this->prefetchingMenu();
        if (isHostedAtServebolt()) {
            $this->acceleratedDomainsMenu();
            $this->cacheSettingsMenu();
            $this->errorLogMenu();
        }
        $this->generalSettingsMenu();
        if (!is_network_admin() && isDevDebug()) {
            $this->debugMenu();
        }
    }

    /**
     * Register Servebolt menu page.
     */
    private function generalPageMenuPage(): void
    {
        add_menu_page( __('Servebolt', 'servebolt-wp'), __('Servebolt', 'servebolt-wp'), 'manage_options', 'servebolt-wp', [$this, 'generalPageCallback'], SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/images/servebolt-icon.svg' );
    }

    /**
     * Register CF cache menu item.
     */
    private function cachePurgeMenu(): void
    {
        if (isHostedAtServebolt()) {
            add_submenu_page(null, null, null, 'manage_options', 'servebolt-cache-purge-control', [CachePurgeControl::getInstance(), 'render']);
        } else {
            add_submenu_page('servebolt-wp', __('Cache purging', 'servebolt-wp'), __('Cache purging', 'servebolt-wp'), 'manage_options', 'servebolt-cache-purge-control', [CachePurgeControl::getInstance(), 'render']);
        }
        add_submenu_page(null, null, null, 'manage_options', 'servebolt-cf-cache-control', [$this, 'cachePurgeLegacyRedirect']);
    }

    /**
     * Redirect from old cache purge control page to the new one.
     */
    public function fpcLegacyRedirect(): void
    {
        ?>
        <script>
            window.location = '<?php echo admin_url('admin.php?page=servebolt-fpc') ?>';
        </script>
        <?php
    }

    /**
     * Redirect from old cache purge control page to the new one.
     */
    public function cachePurgeLegacyRedirect(): void
    {
        ?>
        <script>
            window.location = '<?php echo admin_url('admin.php?page=servebolt-cache-purge-control') ?>';
        </script>
        <?php
    }

    /**
     * Register CF image resize menu item.
     */
    private function cfImageResizeMenu(): void
    {
        if (featureIsAvailable('cf_image_resize')) {
            if (isDevDebug()) {
                add_submenu_page('servebolt-wp', __('Cloudflare Image Resizing', 'servebolt-wp'), __('Cloudflare Image Resizing', 'servebolt-wp'), 'manage_options', 'servebolt-cf-image-resizing', [CloudflareImageResize::getInstance(), 'render']);
            } else {
                add_submenu_page(null, null, null, 'manage_options', 'servebolt-cf-image-resizing', [CloudflareImageResize::getInstance(), 'render']);
            }
        }
    }

    /**
     * Register cache settings menu item.
     */
    private function cacheSettingsMenu(): void
    {
        add_submenu_page('servebolt-wp', __('Cache settings', 'servebolt-wp'), __('Cache', 'servebolt-wp'), 'manage_options', 'servebolt-fpc', [FullPageCacheControl::getInstance(), 'render']);
        add_submenu_page(null, null, null, 'manage_options', 'servebolt-nginx-cache', [$this, 'fpcLegacyRedirect']);
    }

    /**
     * Register cache settings menu item.
     */
    private function acceleratedDomainsMenu(): void
    {
        add_submenu_page('servebolt-wp', __('Accelerated Domains', 'servebolt-wp'), __('Accelerated Domains', 'servebolt-wp'), 'manage_options', 'servebolt-acd', [AcceleratedDomainsControl::getInstance(), 'render']);
        add_submenu_page(null, null, null, 'manage_options', 'servebolt-acd-image-resize', [AcceleratedDomainsImageResizeControl::getInstance(), 'render']);
    }

    /**
     * Register prefetch feature settings menu item.
     */
    private function prefetchingMenu(): void
    {
        add_submenu_page('servebolt-wp', __('Prefetching', 'servebolt-wp'), __('Prefetching', 'servebolt-wp'), 'manage_options', 'servebolt-prefetching', [PrefetchingControl::getInstance(), 'render']);
    }

    /**
     * Register error log menu item.
     */
    private function errorLogMenu(): void
    {
        add_submenu_page('servebolt-wp', __('Error log', 'servebolt-wp'), __('Error log', 'servebolt-wp'), 'manage_options', 'servebolt-logs', [LogViewer::getInstance(), 'render']);
    }

    /**
     * Register debug menu item.
     */
    private function debugMenu(): void
    {
        add_submenu_page('servebolt-wp', __('Debug', 'servebolt-wp'), __('Debug', 'servebolt-wp'), 'manage_options', 'servebolt-debug', [$this, 'debugCallback']);
    }

    /**
     * Register performance optimizer menu item.
     */
    private function performanceOptimizerMenu(): void
    {
        add_submenu_page('servebolt-wp', __('Performance Optimizer', 'servebolt-wp'), __('Performance optimizer', 'servebolt-wp'), 'manage_options', 'servebolt-performance-tools', [PerformanceChecks::getInstance(), 'render']);
    }

    /**
     * Register general/dashboard menu item.
     */
    private function generalMenu(): void
    {
        add_submenu_page('servebolt-wp', __('General', 'servebolt-wp'), __('General', 'servebolt-wp'), 'manage_options', 'servebolt-wp');
    }

    /**
     * Register general settings menu item.
     */
    private function generalSettingsMenu(): void
    {
        add_submenu_page('servebolt-wp', __('Settings', 'servebolt-wp'), __('General settings', 'servebolt-wp'), 'manage_options', 'servebolt-general-settings', [GeneralSettings::getInstance(), 'render']);
    }

    /**
     * Initialize plugin settings link hook.
     */
    private function initPluginSettingsLink(): void
    {
        if (apply_filters('sb_optimizer_display_plugin_row_actions', true)) {
            add_filter('plugin_action_links_' . SERVEBOLT_PLUGIN_BASENAME, [$this, 'addSettingsLinkToPlugin']);
        }
        add_filter('network_admin_plugin_action_links_' . SERVEBOLT_PLUGIN_BASENAME, [$this, 'addNetworkAdminSettingsLinkToPlugin']);
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
        $links[] = sprintf('<a href="%s">%s</a>', admin_url( 'options-general.php?page=servebolt-wp' ), __('Settings', 'servebolt-wp'));
        return $links;
    }

    /**
     * Add settings-link in network admin plugin list.
     *
     * @param $links
     *
     * @return array
     */
    public function addNetworkAdminSettingsLinkToPlugin($links): array
    {
        $links[] = sprintf('<a href="%s">%s</a>', network_admin_url('admin.php?page=servebolt-wp'), __('Settings', 'servebolt-wp'));
        return $links;
    }

    /**
     * Display Servebolt dashboard.
     */
    public function generalPageCallback(): void
    {
        view('dashboard.dashboard');
    }

    /**
     * Display debug information.
     */
    public function debugCallback(): void
    {
        view('debug.debug');
    }
}
