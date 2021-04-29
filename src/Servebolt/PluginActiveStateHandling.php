<?php

namespace Servebolt\Optimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationRunner;
use function Servebolt\Optimizer\Helpers\cacheCookieCheck;
use function Servebolt\Optimizer\Helpers\clearNoCacheCookie;
use function Servebolt\Optimizer\Helpers\isCli;

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
     * Check whether we should prevent plugin from being active.
     *
     * @return bool
     */
    private function shouldPreventSingleSiteActivation(): bool
    {
        return is_multisite() && !is_plugin_active_for_network(SERVEBOLT_PLUGIN_BASENAME);
    }

    /**
     * Prevent the plugin from being activated on a single site when running in a multisite-environment.
     */
    private function preventSingleSitePluginActivationInMultisite(): void
    {
        if (isCli()) {
            add_action('activate_' . SERVEBOLT_PLUGIN_BASENAME, function($network_wide) {
                if (!$network_wide && $this->shouldPreventSingleSiteActivation()) {
                    WP_CLI::error(__('Servebolt Optimizer can only be network activated when running on a multisite.', 'servebolt-wp'));
                    die;
                }
            }, 10, 1);
        }
        add_action('admin_init', function () {
            if ($this->shouldPreventSingleSiteActivation()) {
                deactivate_plugins(SERVEBOLT_PLUGIN_BASENAME);
                if (isset($_GET['activate'])) {
                    unset($_GET['activate']);
                }
                add_filter('sb_optimizer_display_plugin_row_actions', '__return_false');
                add_action('admin_notices', function() {
                    ?>
                    <div class="notice notice-warning is-dismissable">
                        <p><?php echo __('Servebolt Optimizer can only be network activated when running on a multisite.', 'servebolt-wp'); ?></p>
                    </div>
                    <?php
                });
            }
        }, 5);
    }
}
