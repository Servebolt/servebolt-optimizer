<?php
/*
Plugin Name: Servebolt Optimizer
Version: 1.1
Author: Servebolt
Author URI: https://servebolt.com
Description: A plugin that checks and implements Servebolt Performance best practises for WordPress.
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: servebolt-wp
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if(!class_exists(Nginx_Fpc)){
	require_once 'class/class.nginx-fpc.php';
	Servebolt_Nginx_Fpc::setup();
}

if(is_admin()){
	require_once 'admin/admin-interface.php';
	add_action('admin_head', 'servebolt_scripts');
}


