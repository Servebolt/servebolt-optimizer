<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit; // Exit if uninstall.php is not called by WordPress

require __DIR__ . '/vendor/autoload.php';
use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationRunner;
use function Servebolt\Optimizer\Helpers\deleteAllSettings;
use function Servebolt\Optimizer\Helpers\deleteAllSiteSettings;
use function Servebolt\Optimizer\Helpers\clearNoCacheCookie;

// Defines plugin paths and URLs
if (!defined('SERVEBOLT_PLUGIN_FILE')) {
    define('SERVEBOLT_PLUGIN_FILE', __DIR__ . '/servebolt-optimizer.php');
}

MigrationRunner::cleanup();
deleteAllSettings(true, true);
if (is_multisite()) {
    deleteAllSiteSettings(true);
}
clearNoCacheCookie();
