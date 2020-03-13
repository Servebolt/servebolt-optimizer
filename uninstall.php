<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit; // Exit if uninstall.php is not called by WordPress

// Clear all settings
require_once __DIR__ . '/functions.php';
sb_clear_all_settings();
