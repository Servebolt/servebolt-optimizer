<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit; // Exit if uninstall.php is not called by WordPress

require __DIR__ . '/vendor/autoload.php';
use function Servebolt\Optimizer\Helpers\deleteAllSettings;
use function Servebolt\Optimizer\Helpers\clearAllCookies;
use Servebolt\Optimizer\Database\PluginTables;

(new PluginTables(false))->deleteTables();
deleteAllSettings();
clearAllCookies();
