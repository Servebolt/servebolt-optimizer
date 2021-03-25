<?php
/*
Plugin Name: Servebolt Optimizer
Version: 2.2.0-beta.6
Author: Servebolt
Author URI: https://servebolt.com
Description: A plugin that implements Servebolt Security & Performance best practises for WordPress.
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: servebolt-wp
*/

use Servebolt\Optimizer\Queue\QueueSystem\Queue;
use Servebolt\Optimizer\Queue\QueueSystem\SqlBuilder;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Defines plugin paths and URLs
define('SERVEBOLT_PLUGIN_FILE', __FILE__);
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

// Register events for activation and deactivation of this plugin
register_activation_hook(__FILE__, 'Servebolt\\Optimizer\\Helpers\\activatePlugin');
register_deactivation_hook(__FILE__, 'Servebolt\\Optimizer\\Helpers\\deactivatePlugin');

// Add various improvements/optimizations
new Servebolt\Optimizer\GenericOptimizations\GenericOptimizations;

if (is_admin()) {
    // Make sure to hold the database and data structure in sync with the version number
    Servebolt\Optimizer\Database\MigrationRunner::run();
}

// We don't always need all files - only in WP Admin, in CLI-mode or when running the WP Cron.
if (
    is_admin()
    || Servebolt\Optimizer\Helpers\isCli()
    || Servebolt\Optimizer\Helpers\isCron()
) {

    // Make sure we dont API credentials in clear text.
    new Servebolt\Optimizer\Crypto\OptionEncryption;

}

// Loads the class that sets the correct cache headers for the Servebolt full page cache
if (!class_exists('Servebolt_Nginx_FPC')) {
	require_once SERVEBOLT_PLUGIN_DIR_PATH . 'classes/nginx-fpc/sb-nginx-fpc.php';
    Servebolt\Optimizer\Helpers\nginxFpc()->setup();
}

// Initialize image resizing
if (Servebolt\Optimizer\Helpers\featureIsActive('cf_image_resize')) {
    new Servebolt\Optimizer\CloudflareImageResize\CloudflareImageResize;
}

// Queue system
new Servebolt\Optimizer\Queue\QueueEventHandler; // Register event listener for queues

// Register cron schedule & event
new Servebolt\Optimizer\WpCron\WpCronCustomSchedules; // Register cron schedule
new Servebolt\Optimizer\WpCron\WpCronEvents; // Register event trigger for cron schedule

if (Servebolt\Optimizer\Helpers\isWpRest() || is_admin()) {
    // Register cache purge event for various hooks
    new Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions\ContentChangeTrigger;
    new Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions\SlugChangeTrigger;
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

/*
if (!is_admin() && !Servebolt\Optimizer\Helpers\isWpRest()) {
    add_action('init', function() {
        (Servebolt\Optimizer\Queue\Queues\WpObjectQueue::getInstance())->parseQueue();
        die;
    });
}
*/

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
