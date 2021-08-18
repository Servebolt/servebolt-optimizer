<?php

namespace Servebolt\Optimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\WpCachePurge;
use Servebolt\Optimizer\Prefetching\WpPrefetching;
use Servebolt\Optimizer\Compatibility\Compatibility as PluginCompatibility;
use Servebolt\Optimizer\AcceleratedDomains\AcceleratedDomains;
use Servebolt\Optimizer\FullPageCache\FullPageCache;
use Servebolt\Optimizer\MenuCache\WpMenuCache;
use Servebolt\Optimizer\GenericOptimizations\GenericOptimizations;
use Servebolt\Optimizer\TextDomainLoader\WpTextDomainLoader;
use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationRunner;
use Servebolt\Optimizer\Utils\Crypto\OptionEncryption;
use Servebolt\Optimizer\CloudflareImageResize\CloudflareImageResize;
use Servebolt\Optimizer\Queue\QueueEventHandler;
use Servebolt\Optimizer\WpCron\WpCronCustomSchedules;
use Servebolt\Optimizer\WpCron\WpCronEvents;
use Servebolt\Optimizer\Admin\AdminBarGui\AdminBarGui;
use Servebolt\Optimizer\Admin\Assets as AdminAssets;
use Servebolt\Optimizer\Admin\AdminGuiController;
use Servebolt\Optimizer\AssetAutoVersion\AssetAutoVersion;
use Servebolt\Optimizer\Cli\Cli;
use Servebolt\Optimizer\PluginActiveStateHandling\PluginActiveStateHandling;

use function Servebolt\Optimizer\Helpers\featureIsAvailable;
use function Servebolt\Optimizer\Helpers\isCli;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;
use function Servebolt\Optimizer\Helpers\isTesting;
use function Servebolt\Optimizer\Helpers\isFrontEnd;
use function Servebolt\Optimizer\Helpers\featureIsActive;

/**
 * Class ServeboltOptimizer
 * @package Servebolt\Optimizer
 */
class ServeboltOptimizer
{

    /**
     * Boot the plugin.
     */
    public static function boot()
    {

        // Handle activation/deactivation
        new PluginActiveStateHandling;

        // Add various improvements/optimizations
        new GenericOptimizations;

        // Generate database tables etc.
        new WpDatabaseMigrations;

        // Plugin compatibility
        add_action('plugins_loaded', function () {
            new PluginCompatibility;
        });

        // Make sure we don't store certain options (like API credentials) in clear text.
        new OptionEncryption;

        if (WpMenuCache::isActive()) {
            WpMenuCache::init();
        }

        if (isHostedAtServebolt()) {
            // ACD Init
            AcceleratedDomains::init();
        }

        // Sets the correct cache headers for the HTML Cache
        FullPageCache::getInstance();

        // Initialize image resizing
        if (featureIsActive('cf_image_resize')) {
            new CloudflareImageResize;
        }

        // Prefetching feature init
        if (featureIsAvailable('prefetching')) {
            new WpPrefetching;
        }

        // Prefetching feature init
        if (featureIsAvailable('custom_text_domain_loader')) {
            new WpTextDomainLoader;
        }

        // Queue system
        new QueueEventHandler; // Register event listener for queues

        // Register cron schedule & event
        new WpCronCustomSchedules; // Register cron schedule
        new WpCronEvents; // Register event trigger for cron schedule

        // Init cache purging
        new WpCachePurge;

        // Load this admin bar interface
        AdminBarGui::init();

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

        // Only front-end
        if (
            isFrontEnd()
            || isTesting()
        ) {

            // Feature to automatically version all enqueued script/style-tags
            if (featureIsActive('asset_auto_version')) {
                AssetAutoVersion::init();
            }

        }

        // Initialize CLI-commands
        if (isCli()) {
            Cli::init();
        }
    }
}
