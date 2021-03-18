<?php

namespace Servebolt\Optimizer\Admin;

use Servebolt\Optimizer\Admin\CachePurgeControl\CachePurgeControl;
use Servebolt\Optimizer\Admin\FullPageCacheControl\FullPageCacheControl;
use Servebolt\Optimizer\Admin\GeneralSettings\GeneralSettings;
use Servebolt\Optimizer\Admin\CloudflareImageResizing\CloudflareImageResizing;
use Servebolt\Optimizer\Admin\PerformanceChecks\PerformanceChecks;
use Servebolt\Optimizer\Admin\LogViewer\LogViewer;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\view;

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
        if (!is_user_logged_in()) {
            return;
        }
        $this->initAdminMenus();
        $this->initPluginSettingsLink();

        CachePurgeControl::init();
        FullPageCacheControl::init();
        GeneralSettings::init();
        CloudflareImageResizing::init();
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
        if (host_is_servebolt()) {
            $this->fpcCacheMenu();
            $this->errorLogMenu();
        }
        $this->generalSettingsMenu();
        if (!is_network_admin() && sb_is_dev_debug()) {
            $this->debugMenu();
        }
    }

    /**
     * Register Servebolt menu page.
     */
    private function generalPageMenuPage(): void
    {
        add_menu_page( sb__('Servebolt'), sb__('Servebolt'), 'manage_options', 'servebolt-wp', [$this, 'generalPageCallback'], SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/images/servebolt-icon.svg' );
    }

    /**
     * Register CF cache menu item.
     */
    private function cachePurgeMenu(): void
    {
        add_submenu_page('servebolt-wp', sb__('Cache purging'), sb__('Cache purging'), 'manage_options', 'servebolt-cache-purge-control', [CachePurgeControl::getInstance(), 'render']);
        add_submenu_page(null, null, null, 'manage_options', 'servebolt-cf-cache-control', [$this, 'cachePurgeLegacyRedirect']);
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
        if ( sb_feature_active('cf_image_resize') ) {
            add_submenu_page('servebolt-wp', sb__('Cloudflare Image Resizing'), sb__('Cloudflare Image Resizing'), 'manage_options', 'servebolt-cf-image-resizing', [CloudflareImageResizing::getInstance(), 'render']);
        }
    }

    /**
     * Register full page cache menu item.
     */
    private function fpcCacheMenu(): void
    {
        add_submenu_page('servebolt-wp', sb__('Page Cache'), sb__('Full Page Cache'), 'manage_options', 'servebolt-fpc', [FullPageCacheControl::getInstance(), 'render']);
    }

    /**
     * Register error log menu item.
     */
    private function errorLogMenu(): void
    {
        add_submenu_page('servebolt-wp', sb__('Error log'), sb__('Error log'), 'manage_options', 'servebolt-logs', [LogViewer::getInstance(), 'render']);
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
        add_submenu_page('servebolt-wp', sb__('Performance optimizer'), sb__('Performance optimizer'), 'manage_options', 'servebolt-performance-tools', [PerformanceChecks::getInstance(), 'render']);
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
        add_submenu_page('servebolt-wp', sb__('Settings'), sb__('Settings'), 'manage_options', 'servebolt-general-settings', [GeneralSettings::getInstance(), 'render']);
    }

    /**
     * Initialize plugin settings link hook.
     */
    private function initPluginSettingsLink(): void
    {
        add_filter('plugin_action_links_' . SERVEBOLT_PLUGIN_BASENAME, [$this, 'addSettingsLinkToPlugin']);
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
