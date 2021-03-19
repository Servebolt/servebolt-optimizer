<?php
/*
Plugin Name: Servebolt Optimizer
Version: 2.2.0-beta.4
Author: Servebolt
Author URI: https://servebolt.com
Description: A plugin that implements Servebolt Security & Performance best practises for WordPress.
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: servebolt-wp
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Defines plugin paths and URLs
define('SB_TXT_DOMAIN', plugin_basename(__FILE__));
define('SERVEBOLT_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('SERVEBOLT_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ));
define('SERVEBOLT_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ));
define('SERVEBOLT_PLUGIN_PSR4_PATH', SERVEBOLT_PLUGIN_DIR_PATH . 'src/Servebolt/');

// Abort and display WP admin notice if PHP_MAJOR_VERSION is less than 7
if (defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION < 7) {
    require SERVEBOLT_PLUGIN_DIR_PATH . 'php-outdated.php';
    return;
}

// Load Composer dependencies
require SERVEBOLT_PLUGIN_DIR_PATH . 'vendor/autoload.php';

// Include general functions
require_once SERVEBOLT_PLUGIN_DIR_PATH . 'functions.php'; // TODO: Phase this file out

// Register events for activation and deactivation of this plugin
register_activation_hook(__FILE__, 'Servebolt\\Optimizer\\Helpers\\activatePlugin');
register_deactivation_hook(__FILE__, 'Servebolt\\Optimizer\\Helpers\\deactivatePlugin');

// Add various improvements/optimizations
new Servebolt\Optimizer\GenericOptimizations\GenericOptimizations;

// We don't always need all files - only in WP Admin, in CLI-mode or when running the WP Cron.
if (
    is_admin()
    || Servebolt\Optimizer\Helpers\isCli()
    || Servebolt\Optimizer\Helpers\isCron()
) {

    // Make sure we dont API credentials in clear text.
    new Servebolt\Optimizer\Crypto\OptionEncryption;

	// Include the Servebolt Cloudflare class
	//require_once SERVEBOLT_PLUGIN_DIR_PATH . 'classes/cloudflare-cache/sb-cf-cache.php';

}

// Loads the class that sets the correct cache headers for the Servebolt full page cache
if (!class_exists('Servebolt_Nginx_FPC')) {
	require_once SERVEBOLT_PLUGIN_DIR_PATH . 'classes/nginx-fpc/sb-nginx-fpc.php';
	sb_nginx_fpc()->setup();
}

// Initialize image resizing
if (Servebolt\Optimizer\Helpers\featureIsActive('cf_image_resize')) {
	require_once SERVEBOLT_PLUGIN_DIR_PATH . 'classes/cloudflare-image-resize/cloudflare-image-resizing.php';
	( new Cloudflare_Image_Resize )->init();
}

// Register cron schedule and cache purge event
//require_once SERVEBOLT_PLUGIN_DIR_PATH . 'classes/cloudflare-cache/sb-cf-cache-cron-handle.php';

// Register cache purge event for various hooks
if (is_admin() || Servebolt\Optimizer\Helpers\isWpRest()) {
    new Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions;
}

// Load this admin bar interface
Servebolt\Optimizer\Admin\AdminBarGUI\AdminBarGUI::init();

// Load assets
new Servebolt\Optimizer\Admin\Assets;

// Only load the plugin interface in WP Admin
if (is_admin()) {

	// Load this plugins interface
    Servebolt\Optimizer\Admin\AdminGuiController::getInstance();

}

// Only front-end
if (!is_admin() && !Servebolt\Optimizer\Helpers\isCli()) {

    // Feature to automatically version all enqueued script/style-tags
    if (Servebolt\Optimizer\Helpers\featureIsActive('sb_asset_auto_version')) {
        new Servebolt\Optimizer\AssetAutoVersion\AssetAutoVersion;
    }

}

// Initialize CLI-commands
if (Servebolt\Optimizer\Helpers\isCli()) {
    require_once SERVEBOLT_PLUGIN_DIR_PATH . 'cli/cli.class.php';
	Servebolt_CLI::get_instance();
}
