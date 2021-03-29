<?php

namespace Servebolt\Optimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\AcceleratedDomains\AcceleratedDomains;
use Servebolt\Optimizer\GenericOptimizations\GenericOptimizations;
use Servebolt\Optimizer\Database\MigrationRunner;
use Servebolt\Optimizer\Crypto\OptionEncryption;
use Servebolt\Optimizer\CloudflareImageResize\CloudflareImageResize;
use Servebolt\Optimizer\Queue\QueueEventHandler;
use Servebolt\Optimizer\WpCron\WpCronCustomSchedules;
use Servebolt\Optimizer\WpCron\WpCronEvents;
use Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions\ContentChangeTrigger;
use Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions\SlugChangeTrigger;
use Servebolt\Optimizer\Admin\AdminBarGUI\AdminBarGUI;
use Servebolt\Optimizer\Admin\Assets as AdminAssets;
use Servebolt\Optimizer\Admin\AdminGuiController;
use Servebolt\Optimizer\AssetAutoVersion\AssetAutoVersion;
use Servebolt\Optimizer\FullPageCache\FullPageCache;

use function Servebolt\Optimizer\Helpers\isCli;
use function Servebolt\Optimizer\Helpers\isAjax;
use function Servebolt\Optimizer\Helpers\isCron;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;
use function Servebolt\Optimizer\Helpers\isWpRest;
use function Servebolt\Optimizer\Helpers\isTesting;
use function Servebolt\Optimizer\Helpers\isFrontEnd;
use function Servebolt\Optimizer\Helpers\featureIsActive;

/**
 * Class ServeboltOptimizer
 * @package Servebolt\Optimizer
 */
class ServeboltOptimizer
{
    public static function boot()
    {

        // Register events for activation and deactivation of this plugin
        register_activation_hook(__FILE__, '\\Servebolt\\Optimizer\\Helpers\\activatePlugin');
        register_deactivation_hook(__FILE__, '\\Servebolt\\Optimizer\\Helpers\\deactivatePlugin');

        // Add various improvements/optimizations
        new GenericOptimizations;

        if (is_admin()) {
            // Make sure to hold the database and data structure in sync with the version number
            MigrationRunner::run();
        }

        // We don't always need all files - only in WP Admin, in CLI-mode or when running the WP Cron.
        if (
            is_admin()
            || isCli()
            || isAjax()
            || isCron()
            || isTesting()
        ) {

            // Make sure we dont certain options (like API credentials) in clear text.
            new OptionEncryption;

        }

        if (isHostedAtServebolt()) {
            AcceleratedDomains::init();
        }

        // Sets the correct cache headers for the Servebolt full page cache
        FullPageCache::init();

        // Initialize image resizing
        if (featureIsActive('cf_image_resize')) {
            new CloudflareImageResize;
        }

        // Queue system
        new QueueEventHandler; // Register event listener for queues

        // Register cron schedule & event
        new WpCronCustomSchedules; // Register cron schedule
        new WpCronEvents; // Register event trigger for cron schedule

        if (
            is_admin()
            || isWpRest()
            || isTesting()
        ) {
            // Register cache purge event for various hooks
            new ContentChangeTrigger;
            new SlugChangeTrigger;
        }

        // Load this admin bar interface
        AdminBarGUI::init();

        // Load assets
        new AdminAssets;

        // Only load the plugin interface in WP Admin
        if (
            is_admin()
            || isTesting()
        ) {

            // Load this plugins interface
            AdminGuiController::getInstance();

        }

        /*
        if (isset($_GET['a'])) {
            add_action('init', function() {
                (\Servebolt\Optimizer\Queue\Queues\WpObjectQueue::getInstance())->parseQueue();
                die;
            });
        }
        */

        // Only front-end
        if (
            isFrontEnd()
            || isTesting()
        ){

            // Feature to automatically version all enqueued script/style-tags
            if (featureIsActive('sb_asset_auto_version')) {
                new AssetAutoVersion;
            }

        }

        // Initialize CLI-commands
        if (isCli()) {
            require_once SERVEBOLT_PLUGIN_DIR_PATH . 'cli/cli.class.php';
            \Servebolt_CLI::get_instance();
        }
    }
}
