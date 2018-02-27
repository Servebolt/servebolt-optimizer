<?php
if( ! defined( 'ABSPATH' ) ) exit;

require_once SERVEBOLT_PATH.'admin/logs-viewer/tail.php'; // Get the file we need for log viewer
require_once SERVEBOLT_PATH.'admin/optimize-db/optimize-db.php';

// create custom plugin settings menu
add_action('admin_menu', 'servebolt_admin_menu');

/**
 * Load the menus
 */
function servebolt_admin_menu() {
	add_options_page('Servebolt', __('General','servebolt'), 'manage_options', 'servebolt-settings', 'servebolt_general_page');
	add_menu_page('Servebolt', __('Servebolt','servebolt'), 'manage_options', 'servebolt-wp', 'servebolt_general_page', SERVEBOLT_PATH_URL.'admin/assets/img/servebolt-wp.png');
	add_submenu_page('servebolt-wp', __('Performance optimizer','servebolt'), __('Performance optimizer','servebolt'), 'manage_options', 'servebolt-performance-tools', 'servebolt_performance');
	if(host_is_servebolt() == true) {
	    ## Add these if the site is hosted on Servebolt
		add_submenu_page('servebolt-wp', __('NGINX Cache','servebolt'), __('NGINX Cache','servebolt'), 'manage_options', 'servebolt-nginx-cache', 'Servebolt_NGINX_cache');
		add_submenu_page('servebolt-wp', __('Error logs','servebolt'), __('Error logs','servebolt'), 'manage_options', 'servebolt-logs', 'servebolt_get_error_log');
		add_submenu_page('servebolt-wp', __('Security issues','servebolt'), __('Security issues','servebolt'), 'manage_options', 'servebolt-wpvuldb', 'Servebolt_wpvuldb');
		add_action('admin_bar_menu', 'servebolt_admin_bar', 100);
	}
}

function servebolt_admin_bar($wp_admin_bar){
	$adminUrl = the_sb_admin_url();
	$args = array(
		'id' => 'servebolt-admin',
		'title' => __('Servebolt Control Panel', 'servebolt-wp'),
		'href' => $adminUrl,
		'meta' => array(
            'target' => '_blank',
			'class' => 'sb-admin-button'
		)
	);
	$wp_admin_bar->add_node($args);
}

/**
 * Loading the styling and js needed for this plugin
 */
add_action('admin_enqueue_scripts', 'servebolt_plugin_styling');
function servebolt_plugin_styling() {
	wp_register_style( 'servebolt_optimizer_styling', SERVEBOLT_PATH_URL . 'admin/assets/style.css', false, filemtime(SERVEBOLT_PATH . 'admin/assets/style.css') );
	wp_enqueue_style( 'servebolt_optimizer_styling' );
}

/**
 * Load the files needed for the DB optimization screen
 */
function servebolt_performance(){
	require_once 'performance-checks.php';
	require_once 'optimize-db/checks.php';
}

/**
 * Register the custom option for what post type to cache
 */
add_action( 'admin_init', 'servebolt_register_settings' );
function servebolt_register_settings() {
	register_setting( 'nginx-fpc-options-page', 'servebolt_fpc_settings' );
	register_setting( 'nginx-fpc-options-page', 'servebolt_fpc_switch' );
}

/**
 * Set up the Servebolt dashboard
 */
function servebolt_general_page() {
    require_once 'servebolt-dashboard.php';
}

/**
 * Set up the NGINX cache control page
 */
function Servebolt_NGINX_cache() {
 require_once 'nginx-controls.php';
}

/**
 * Set up the WPVULNDB overview
 */
function Servebolt_wpvuldb() {
	require_once 'security/interface.php';
}

/**
 * Make a link to the Servebolt admin panel
 * @return bool|string link
 */
function the_sb_admin_url() {
	return ( preg_match( "@kunder/[a-z_0-9]+/[a-z_]+(\d+)/@", get_home_path(), $matches ) ) ? 'https://admin.servebolt.com/siteredirect/?site='. $matches[1] : false;
}

/**
 * Check if the site is hosted on Servebolt.com
 * @return bool
 */
function host_is_servebolt() {
	if (array_key_exists('SERVER_ADMIN', $_SERVER)) {
		$server_admin = $_SERVER['SERVER_ADMIN'];
		if (strpos($server_admin, "raskesider.no") !== FALSE || strpos($server_admin, "servebolt.com") !== FALSE ){
			return true;
		}
    }
	if (array_key_exists('SERVER_NAME', $_SERVER)) {
		$server_name = $_SERVER['SERVER_NAME'];
		if (strpos($server_name, "raskesider.no") !== FALSE || strpos($server_name, "servebolt.com") !== FALSE) {
			return true;
		}
	}
	return true;
}

add_action('admin_head', 'servebolt_ajax_optimize');
function servebolt_ajax_optimize() {
	?>
	<script type="text/javascript" >
        jQuery(document).ready(function($) {

            $('.optimize-now').click(function(){
                var data = {
                    action: 'servebolt_optimize_db',
                    whatever: 1234
                };

                // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                $.post(ajaxurl, data, function(response) {
                    alert(response);
                    location.reload();
                });
            });

        });
	</script>
	<?php
}

