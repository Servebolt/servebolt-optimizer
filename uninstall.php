<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit; // Exit if uninstall.php is not called by WordPress

require SERVEBOLT_PATH . 'vendor/autoload.php';
use function Servebolt\Optimizer\Helpers\sbDeleteAllSettings;
use function Servebolt\Optimizer\Helpers\sbClearAllCookies;
use Servebolt\Optimizer\Database\PluginTables;

(new PluginTables(false))->deleteTables();
sbDeleteAllSettings();
sbClearAllCookies();
