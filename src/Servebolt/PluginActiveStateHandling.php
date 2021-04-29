<?php

namespace Servebolt\Optimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationRunner;
use function Servebolt\Optimizer\Helpers\checkAllCookies;
use function Servebolt\Optimizer\Helpers\clearAllCookies;

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
    }

    /**
     * Plugin activation.
     */
    public function activatePlugin(): void
    {
        MigrationRunner::migrate(); // Run database migrations
        checkAllCookies();
    }

    /**
     * Plugin deactivation.
     */
    public function deactivatePlugin(): void
    {
        clearAllCookies();
    }
}
