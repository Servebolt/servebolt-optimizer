<?php

namespace Servebolt\Optimizer\PluginActiveStateHandling;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use function Servebolt\Optimizer\Helpers\isCli;

/**
 * Class SingleSitePluginActivationConstraint
 */
class SingleSitePluginActivationConstraint
{
    /**
     * SingleSitePluginActivationConstraint constructor.
     */
    public function __construct()
    {
        $this->adminGuiHandling();
        $this->wpCliHandling();
    }

    /**
     * Check whether the plugin is network activated or not.
     *
     * @return bool
     */
    private function pluginIsNetworkActivated(): bool
    {
        if(!function_exists('is_plugin_active_for_network')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        return is_plugin_active_for_network(SERVEBOLT_PLUGIN_BASENAME);
    }

    /**
     * Error message for attempting to activate on single site when running in a multisite-environment.
     *
     * @return string
     */
    private function activationErrorMessage(): string
    {
        return __('Servebolt Optimizer can only be network activated when running on a multisite.', 'servebolt-wp');
    }

    /**
     * Prevent the plugin from being activated via WP CLI on a single site when running in a multisite-environment.
     */
    private function wpCliHandling(): void
    {
        if (isCli()) {
            add_action('activate_' . SERVEBOLT_PLUGIN_BASENAME, function($network_wide) {
                if (!$network_wide) {
                    WP_CLI::error($this->activationErrorMessage());
                    die;
                }
            }, 10, 1);
        }
    }

    /**
     * Prevent the plugin from being activated from WP Admin on a single site when running in a multisite-environment.
     */
    private function adminGuiHandling(): void
    {
        add_action('admin_init', function () {
            if (!$this->pluginIsNetworkActivated()) {
                deactivate_plugins(SERVEBOLT_PLUGIN_BASENAME);
                add_filter('sb_optimizer_display_plugin_row_actions', '__return_false');
                if (isset($_GET['activate'])) {
                    unset($_GET['activate']);
                }
                add_action('admin_notices', function() {
                    ?>
                    <div class="notice notice-warning is-dismissable">
                        <p><?php echo $this->activationErrorMessage(); ?></p>
                    </div>
                    <?php
                });
            }
        }, 5);
    }
}
