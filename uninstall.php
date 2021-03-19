<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit; // Exit if uninstall.php is not called by WordPress

require __DIR__ . '/vendor/autoload.php';
use Servebolt\Optimizer\Database\PluginTables;
use function Servebolt\Optimizer\Helpers\deleteAllSettings;
use function Servebolt\Optimizer\Helpers\clearAllCookies;

(new PluginTables(false))->deleteTables();
deleteAllSettings();
clearAllCookies();
