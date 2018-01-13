<?php
if( ! defined( 'ABSPATH' ) ) exit;

require_once 'logs-viewer/tail.php'; // Get the file we need for log viewer

// create custom plugin settings menu
add_action('admin_menu', 'servebolt_menu');

function servebolt_menu() {
	add_options_page('Servebolt', __('General','servebolt'), 'manage_options', 'servebolt-settings', 'general_page');
	add_menu_page('Servebolt', __('Servebolt','servebolt'), 'manage_options', 'servebolt-wp', 'general_page', plugin_dir_url(__FILE__).'assets/img/servebolt-wp.png');
	add_submenu_page('servebolt-wp', __('Performance optimizer','servebolt'), __('Performance optimizer','servebolt'), 'manage_options', 'servebolt-performance-tools', 'servebolt_performance');
	add_action( 'admin_init', 'sb_register_settings' );
	if(host_is_servebolt() == true) {
	    ## Add these if the site is hosted on Servebolt
		add_submenu_page('servebolt-wp', __('NGINX Cache','servebolt'), __('NGINX Cache','servebolt'), 'manage_options', 'servebolt-nginx-cache', 'Servebolt_NGINX_cache');
		add_submenu_page('servebolt-wp', __('Error logs','servebolt'), __('Error logs','servebolt'), 'manage_options', 'servebolt-logs', 'servebolt_get_error_log');
		add_action('admin_bar_menu', 'sb_admin_bar', 100);
	}
}

add_action('admin_head', 'sb_plugin_styling');
function sb_plugin_styling() {
	wp_register_style( 'servebolt_optimizer_styling', plugin_dir_url(__FILE__) . '/assets/style.css', false, false );
	wp_enqueue_style( 'servebolt_optimizer_styling' );
}


add_action('admin_head', 'servebolt_scripts');
function servebolt_scripts() {
	wp_enqueue_script( 'servebolt-admin-js', plugin_dir_url(__FILE__) . '/assets/js/admin.js', array('jquery'), false, false );
}


function servebolt_performance(){
	require_once 'performance-checks.php';
	require_once 'optimize-db/checks.php';
}

function sb_admin_bar($wp_admin_bar){
    $adminUrl = the_sb_admin_url();
	$args = array(
		'id' => 'servebolt-admin',
		'title' => __('Servebolt Control Panel', 'servebolt-wp'),
		'href' => $adminUrl,
		'meta' => array(
			'class' => 'sb-admin-button'
		)
	);
	$wp_admin_bar->add_node($args);
}

function sb_register_settings() {
	register_setting( 'nginx-fpc-options-page', 'fpc_settings' );
}

function general_page() {
    require_once 'general.php';
}


function Servebolt_NGINX_cache() {
    $sbAdminButton = '<a href="'. the_sb_admin_url() .'">'.__('Servebolt site settings', 'servebolt-wp').'</a>';
	?>
	<div class="wrap sb-content">
		<h1><?php _e('NGINX Cache', 'servebolt-wp') ?></h1>
        <div>
            <p><?php _e('Servebolt NGINX Cache is easy to set up, but should always be tested before activating it on production environments.', 'servebolt-wp') ?></p>
            <p><?php printf( esc_html__( 'To activate NGINX cache to go %s and set "Enable caching of static files" to "All"', 'servebolt-wp' ), $sbAdminButton ) ?></p>
            <a href="<?php echo the_sb_admin_url() ?>" class="button"><?php _e('Servebolt site settings', 'servebolt-wp') ?></a>
        </div>
		<?php if (isset($_GET['settings-updated'])) : ?>
			<div class="notice notice-success is-dismissible"><p><?php _e('Cache settings saved!', 'servebolt-wp') ?></p></div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'nginx-fpc-options-page' ) ?>
			<?php do_settings_sections( 'nginx-fpc-options-page' ) ?>
            <?php
            $args = array(
                    'public' => true
            );
            $post_types = get_post_types($args, 'objects');
            ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Cache post types
                        <div>
                            <p><?php _e(
                                    'By default this plugin enables caching of posts, pages and products. 
                            Activate post types here if you want a different cache setup. 
                            This will override the default setup.',
                                    'servebolt-wp'); ?></p>
                        </div>
                    </th>
                    <td>
                    <?php foreach ($post_types as $type){
	                    $options = get_option('fpc_settings');
                        $checked = '';
                        if(array_key_exists($type->name, $options)){ $checked = ' checked="checked" '; }
                        echo $options['fpc_settings'];
	                    echo '<input '.$checked.' id="cache_post_type" name="fpc_settings['.$type->name.']" type="checkbox" />'.$type->labels->singular_name.'</input></br>';
                    }
                    ?>
                    </td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
<?php
}

/**
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
	$server_name = $_SERVER['SERVER_NAME'];
	if (strpos($server_name, "raskesider") !== FALSE || strpos($server_name, "servebolt") !== FALSE) {
		return true;
	}
	return false;
}

