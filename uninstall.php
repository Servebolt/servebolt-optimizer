<?php

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
	die;
}

// Delete custom settings
$option_name = 'servebolt_fpc_settings';
delete_option($option_name);
delete_site_option($option_name);

