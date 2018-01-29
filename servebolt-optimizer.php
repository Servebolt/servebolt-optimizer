<?php
/*
Plugin Name: Servebolt Optimizer
Version: 1.2.3
Author: Servebolt
Author URI: https://servebolt.com
Description: A plugin that checks and implements Servebolt Performance best practises for WordPress.
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: servebolt-wp
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'SERVEBOLT_PATH', plugin_dir_url( __FILE__ ) );

/**
 * Loads the class that sets the correct cache headers for NGINX cache
 */
if(!class_exists('Servebolt_Nginx_Fpc')){
	require_once 'class/servebolt-nginx-fpc.class.php';
	Servebolt_Nginx_Fpc::setup();
}

/**
 * If the admin is loaded, load this plugins interface
 */
if(is_admin()){
	require_once 'admin/admin-interface.php';
}


