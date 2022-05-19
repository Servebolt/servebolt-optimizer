<?php

namespace Servebolt\Optimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\AcceleratedDomains\AcceleratedDomains;
use Servebolt\Optimizer\AcceleratedDomains\Prefetching\WpPrefetching;
use Servebolt\Optimizer\Admin\AdminBarGui\AdminBarGui;
use Servebolt\Optimizer\Admin\AdminController;
use Servebolt\Optimizer\Admin\Assets as AdminAssets;
use Servebolt\Optimizer\Admin\ClearSiteDataHeader\ClearSiteDataHeader;
use Servebolt\Optimizer\AssetAutoVersion\AssetAutoVersion;
use Servebolt\Optimizer\CachePurge\WpCachePurge;
use Servebolt\Optimizer\Cli\Cli;
use Servebolt\Optimizer\CloudflareImageResize\CloudflareImageResize;
use Servebolt\Optimizer\Compatibility\Compatibility as PluginCompatibility;
use Servebolt\Optimizer\FullPageCache\FullPageCache;
use Servebolt\Optimizer\GenericOptimizations\GenericOptimizations;
use Servebolt\Optimizer\MenuOptimizer\WpMenuOptimizer;
use Servebolt\Optimizer\PluginActiveStateHandling\PluginActiveStateHandling;
use Servebolt\Optimizer\Queue\QueueParseEventHandler;
use Servebolt\Optimizer\TextDomainLoader\WpTextDomainLoader;
use Servebolt\Optimizer\Utils\Crypto\OptionEncryption;
use Servebolt\Optimizer\Utils\PostUpgradeActions;
use Servebolt\Optimizer\WpCron\WpCronCustomSchedules;
use Servebolt\Optimizer\WpCron\WpCronEvents;
use function Servebolt\Optimizer\Helpers\featureIsActive;
use function Servebolt\Optimizer\Helpers\featureIsAvailable;
use function Servebolt\Optimizer\Helpers\isCli;
use function Servebolt\Optimizer\Helpers\isFrontEnd;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;
use function Servebolt\Optimizer\Helpers\isLogin;
use function Servebolt\Optimizer\Helpers\isTesting;
use function Servebolt\Optimizer\Helpers\envFileFailureHandling;

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

        // Run actions after plugin upgrade
        PostUpgradeActions::init();

        // Add various improvements/optimizations
        new GenericOptimizations;

        // Generate database tables etc.
        WpDatabaseMigrations::init();

        // Plugin compatibility
        add_action('plugins_loaded', function () {
            new PluginCompatibility;
        });

        // Make sure we don't store certain options (like API credentials) in clear text.
        new OptionEncryption;

        WpMenuOptimizer::init();

        if (isHostedAtServebolt()) {
            // ACD Init
            AcceleratedDomains::init();

            // Add admin notice if we cannot read the environment file
            envFileFailureHandling();
        }

        // Sets the correct cache headers for the HTML Cache
        FullPageCache::init();

        // Initialize image resizing
        if (featureIsActive('cf_image_resize')) {
            new CloudflareImageResize;
        }

        // Prefetching feature init
        if (featureIsAvailable('prefetching')) {
            WpPrefetching::init();
        }

        // Prefetching feature init
        if (featureIsAvailable('custom_text_domain_loader')) {
            WpTextDomainLoader::init();
        }

        // Queue system
        new QueueParseEventHandler; // Register event listener for queues

        // Register cron schedule & event
        new WpCronCustomSchedules; // Register cron schedule
        new WpCronEvents; // Register event trigger for cron schedule

        // Init cache purging
        new WpCachePurge;

        // Load this admin bar interface
        AdminBarGui::init();

        // Load assets
        new AdminAssets;

        if (isLogin()) {
            new ClearSiteDataHeader;
        }

        // Only load the plugin interface in WP Admin
        if (
            is_admin()
            || isTesting()
        ) {

            // Load this plugins admin interface
            AdminController::getInstance();

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
