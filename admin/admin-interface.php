<?php
if ( ! defined( 'ABSPATH' ) ) exit;

require_once SERVEBOLT_PATH . 'admin/log-viewer.php';
require_once SERVEBOLT_PATH . 'admin/general-settings.php';
require_once SERVEBOLT_PATH . 'admin/performance-checks.php';
require_once SERVEBOLT_PATH . 'admin/nginx-fpc-controls.php';
require_once SERVEBOLT_PATH . 'admin/cf-cache-admin-controls.php';
require_once SERVEBOLT_PATH . 'admin/cf-image-resizing.php';
require_once SERVEBOLT_PATH . 'admin/optimize-db/optimize-db.php';

/**
 * Class Servebolt_Admin_Interface
 *
 * This class initiates the admin interface for the plugin.
 */
class Servebolt_Admin_Interface {

	/**
	 * Servebolt_Admin_Interface constructor.
	 */
	public function __construct() {
		add_action('init', [$this, 'admin_init']);
	}

	/**
	 * Admin init.
	 */
	public function admin_init() {
		if ( ! is_user_logged_in() ) return;
		$this->init_admin_menus();
		$this->init_plugin_settings_link();
	}

	/**
	 * Init admin menus.
	 */
	private function init_admin_menus() {

        // Multisite setup
        if ( is_multisite() ) {

            // Network admin menu setup
            if ( is_network_admin() && is_super_admin() ) { // Only allow super admins to access network context settings
                add_action('network_admin_menu', [$this, 'network_admin_menu']);
            }

            // Sub-site admin menu setup
            add_action('admin_menu', [$this, 'sub_site_menu']);

		} else {

            // Single-site admin menu setup
			add_action('admin_menu', [$this, 'single_site_admin_menu']);

		}
	}

    /**
     * Network admin menu.
     */
	public function network_admin_menu() {
        if ( ! apply_filters('sb_optimizer_display_network_super_admin_menu', true) ) return;

        $this->sb_general_page_menu_page(); // Register menu page

        $this->general_menu();
        $this->performance_optimizer_menu();
        $this->add_sub_menu_items();
    }

	/**
	 * Admin menu for sub sites (in multisite context).
	 */
	public function sub_site_menu() {
		if ( ! apply_filters('sb_optimizer_display_subsite_menu', true) ) return;

		$this->sb_general_page_menu_page(); // Register menu page

        $this->general_menu();
        $this->add_sub_menu_items();
	}

    /**
     * Admin menu (in non-multisite context).
     */
    public function single_site_admin_menu() {
        if ( ! apply_filters('sb_optimizer_display_single_site_admin_menu', true) ) return;

        $this->sb_general_page_menu_page(); // Register menu page

        $this->general_menu();
        $this->performance_optimizer_menu();
        $this->add_sub_menu_items();
    }

    /**
     * Shared menu items.
     */
    private function add_sub_menu_items() {
        $this->cf_cache_menu();
        $this->cf_image_resize_menu();
        if ( host_is_servebolt() ) {
            $this->fpc_cache_menu();
            $this->error_log_menu();
        }
        $this->general_settings_menu();
        if ( ! is_network_admin() && sb_is_dev_debug() ) {
            $this->debug_menu();
        }
    }

    /**
     * Register Servebolt menu page.
     */
    private function sb_general_page_menu_page() {
        add_menu_page( sb__('Servebolt'), sb__('Servebolt'), 'manage_options', 'servebolt-wp', [$this, 'general_page_callback'], SERVEBOLT_PATH_URL . 'assets/dist/images/servebolt-icon.svg' );
    }

    /**
     * Register CF cache menu item.
     */
    private function cf_cache_menu() {
        add_submenu_page('servebolt-wp', sb__('Cloudflare Cache'), sb__('Cloudflare Cache'), 'manage_options', 'servebolt-cf-cache-control', [$this, 'cf_cache_callback']);
    }

    /**
     * Register CF image resize menu item.
     */
    private function cf_image_resize_menu() {
        if ( sb_feature_active('cf_image_resize') ) {
            add_submenu_page('servebolt-wp', sb__('Cloudflare Image Resizing'), sb__('Cloudflare Image Resizing'), 'manage_options', 'servebolt-cf-image-resizing', [$this, 'cf_image_resizing_callback']);
        }
    }

    /**
     * Register full page cache menu item.
     */
    private function fpc_cache_menu() {
        add_submenu_page('servebolt-wp', sb__('Page Cache'), sb__('Full Page Cache'), 'manage_options', 'servebolt-nginx-cache', [$this, 'nginx_cache_callback']);
    }

    /**
     * Register error log menu item.
     */
    private function error_log_menu() {
        add_submenu_page('servebolt-wp', sb__('Error log'), sb__('Error log'), 'manage_options', 'servebolt-logs', [$this, 'error_log_callback']);
    }

    /**
     * Register debug menu item.
     */
    private function debug_menu() {
        add_submenu_page('servebolt-wp', sb__('Debug'), sb__('Debug'), 'manage_options', 'servebolt-debug', [$this, 'debug_callback']);
    }

    /**
     * Register performance optimizer menu item.
     */
    private function performance_optimizer_menu() {
        add_submenu_page('servebolt-wp', sb__('Performance optimizer'), sb__('Performance optimizer'), 'manage_options', 'servebolt-performance-tools', [$this, 'performance_callback']);
    }

    /**
     * Register general/dashboard menu item.
     */
    private function general_menu() {
        add_submenu_page('servebolt-wp', sb__('General'), sb__('General'), 'manage_options', 'servebolt-wp');
    }

    /**
     * Register general settings menu item.
     */
    private function general_settings_menu() {
        add_submenu_page('servebolt-wp', sb__('Settings'), sb__('Settings'), 'manage_options', 'servebolt-general-settings', [$this, 'general_settings_callback']);
    }

	/**
	 * Initialize plugin settings link hook.
	 */
	private function init_plugin_settings_link() {
		add_filter('plugin_action_links_' . SERVEBOLT_BASENAME, [$this, 'add_settings_link_to_plugin']);
	}

	/**
	 * Add settings-link in plugin list.
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public function add_settings_link_to_plugin($links) {
		$links[] = sprintf('<a href="%s">%s</a>', admin_url( 'options-general.php?page=servebolt-wp' ), sb__('Settings'));
		return $links;
	}

	/**
	 * Display Servebolt dashboard.
	 */
	public function general_page_callback() {
		sb_view('admin/views/dashboard/dashboard');
	}

	/**
	 * Display DB optimization page.
	 */
	public function performance_callback(){
		sb_performance_checks()->view();
	}

	/**
	 * Display the Cloudflare Cache control page.
	 */
	public function cf_cache_callback() {
		sb_cf_cache_admin_controls()->view();
	}

	/**
	 * Display the Cloudflare Image Resizing control pagel.
	 */
	public function cf_image_resizing_callback() {
		sb_cf_image_resizing()->view();
	}

	/**
	 * Display the Full Page Cache control page.
	 */
	public function nginx_cache_callback() {
		sb_nginx_fpc_controls()->view();
	}

    /**
     * Display the general settings control page.
     */
    public function general_settings_callback() {
        sb_general_settings()->view();
    }


	/**
	 * Display error log page.
	 */
	public function error_log_callback() {
		( Servebolt_Logviewer::get_instance() )->view();
	}

	/**
	 * Display debug information.
	 */
	public function debug_callback() {
		sb_view('admin/views/debug');
	}

}
new Servebolt_Admin_Interface;

