<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit; // Exit if uninstall.php is not called by WordPress

// Delete all settings
require_once __DIR__ . '/functions.php';
sb_delete_all_settings();
sb_delete_tables();
