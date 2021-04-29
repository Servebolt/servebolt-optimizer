<?php

namespace Servebolt\Optimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationRunner;
use function Servebolt\Optimizer\Helpers\cacheCookieCheck;
use function Servebolt\Optimizer\Helpers\clearNoCacheCookie;

/**
 * Class PluginActiveStateHandling
 * @package Servebolt\Optimizer
 */
class PluginActiveStateHandling
{
    public function __construct()
    {
        // Register events for activation and deactivation of this plugin
        register_activation_hook(SERVEBOLT_PLUGIN_FILE, [$this, 'activatePlugin']);
        register_deactivation_hook(SERVEBOLT_PLUGIN_FILE, [$this, 'deactivatePlugin']);

        $this->preventSingleSitePluginActivationInMultisite();
    }

    /**
     * Plugin activation.
     */
    public function activatePlugin(): void
    {
        MigrationRunner::migrate(); // Run database migrations
        cacheCookieCheck();
    }

    /**
     * Plugin deactivation.
     */
    public function deactivatePlugin(): void
    {
        clearNoCacheCookie();
    }

    /**
     * Prevent the plugin from being activated on a single site when running in a multisite-environment.
     */
    private function preventSingleSitePluginActivationInMultisite(): void
    {
        add_action('admin_init', function () {
            if (is_multisite() && !is_plugin_active_for_network(SERVEBOLT_PLUGIN_BASENAME)) {
                deactivate_plugins(SERVEBOLT_PLUGIN_BASENAME);
                if (isset($_GET['activate'])) {
                    unset($_GET['activate']);
                }
                add_filter('sb_optimizer_display_plugin_row_actions', '__return_false');
                add_action('admin_notices', function() {
                    ?>
                    <div class="notice notice-warning is-dismissable">
                        <p><?php printf(__('Servebolt Optimizer can only be network activated when running on a multisite.', 'servebolt-wp'), phpversion()); ?></p>
                    </div>
                    <?php
                });
            }
        }, 5);
    }
}
