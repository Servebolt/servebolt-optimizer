<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit; // Exit if uninstall.php is not called by WordPress

require __DIR__ . '/vendor/autoload.php';
use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationRunner;
use function Servebolt\Optimizer\Helpers\deleteAllSettings;
use function Servebolt\Optimizer\Helpers\clearNoCacheCookie;

MigrationRunner::cleanup();
deleteAllSettings(true, true);
clearNoCacheCookie();
