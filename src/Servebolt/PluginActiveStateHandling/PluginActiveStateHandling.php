<?php

namespace Servebolt\Optimizer\PluginActiveStateHandling;

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

        if (is_multisite()) {
            new SingleSitePluginActivationConstraint;
        }
    }

    /**
     * Plugin activation.
     */
    public function activatePlugin(): void
    {
        MigrationRunner::run(); // Run database migrations
        cacheCookieCheck();
    }

    /**
     * Plugin deactivation.
     */
    public function deactivatePlugin(): void
    {
        clearNoCacheCookie();
    }
}
