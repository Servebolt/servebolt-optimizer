<?php
/*
Plugin Name: Servebolt Optimizer
Version: 3.5.8
Author: Servebolt
Author URI: https://servebolt.com
Description: A plugin that implements Servebolt Security & Performance best practises for WordPress.
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: servebolt-wp
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Defines plugin paths and URLs
define('SERVEBOLT_PLUGIN_FILE', __FILE__);
define('SERVEBOLT_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('SERVEBOLT_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ));
define('SERVEBOLT_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ));
define('SERVEBOLT_PLUGIN_PSR4_PATH', SERVEBOLT_PLUGIN_DIR_PATH . 'src/Servebolt/');
define('SERVEBOLT_PLUGIN_MINIMUM_PHP_VERSION', '7.3');
define('SERVEBOLT_PLUGIN_ACD_VERSION', '7');

// Abort and display WP admin notice if PHP version is less than constant SERVEBOLT_PLUGIN_MINIMUM_PHP_VERSION
if (version_compare(phpversion(), SERVEBOLT_PLUGIN_MINIMUM_PHP_VERSION, '<')) {
    require SERVEBOLT_PLUGIN_DIR_PATH . 'php-outdated.php';
    return;
}

// Load Composer dependencies
require SERVEBOLT_PLUGIN_DIR_PATH . 'vendor/autoload.php';

// Boot plugin
Servebolt\Optimizer\ServeboltOptimizer::boot();
