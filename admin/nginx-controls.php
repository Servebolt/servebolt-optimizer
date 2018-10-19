<?php

/**
 * This controls the NGINX cache headers.
 */

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

    <?php if(is_network_admin() !== true): ?>
	<form method="post" action="options.php">
		<?php settings_fields( 'nginx-fpc-options-page' ) ?>
		<?php do_settings_sections( 'nginx-fpc-options-page' ) ?>
		<?php
		$args = array(
			'public' => true
		);
		$post_types = get_post_types($args, 'objects');

		$nginx_switch = get_option('servebolt_fpc_switch');

		$nginx_switch_checked = '';

		if($nginx_switch === 'on'){ $nginx_switch_checked = 'checked';}
		echo '<div class="nginx_switch"><input '.$nginx_switch_checked.' id="nginx_cache_switch" name="servebolt_fpc_switch" type="checkbox" />'.__('Turn Nginx Cache on', 'servebolt-wp').'</input></div>';

		$style = '';
		if($nginx_switch !== 'on'){$style = 'style="display: none;"';}
		?>
		<table class="form-table" <?php echo $style; ?>>
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
					<?php $options = get_option('servebolt_fpc_settings');
                    foreach ($post_types as $type){
						$checked = '';
						if(is_array($options) && array_key_exists($type->name, $options)){ $checked = ' checked="checked" '; }
						echo '<input '.$checked.' id="cache_post_type" name="servebolt_fpc_settings['.$type->name.']" type="checkbox" />'.$type->labels->singular_name.'</input></br>';
					}
					?>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
    <?php else:
    $sites = get_sites();
    ?>
    <table class="wp-list-table widefat striped">
        <thead>
        <tr>
            <th><?php _e('Blog ID', 'servebolt-wp'); ?></th>
            <th><?php _e('URL', 'servebolt-wp'); ?></th>
            <th><?php _e('NGINX Switch', 'servebolt-wp'); ?></th>
            <th><?php _e('Options', 'servebolt-wp'); ?></th>
            <th><?php _e('Controls', 'servebolt-wp'); ?></th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <th><?php _e('Blog ID', 'servebolt-wp'); ?></th>
            <th><?php _e('URL', 'servebolt-wp'); ?></th>
            <th><?php _e('NGINX Switch', 'servebolt-wp'); ?></th>
            <th><?php _e('Options', 'servebolt-wp'); ?></th>
            <th><?php _e('Controls', 'servebolt-wp'); ?></th>
        </tr>
        </tfoot>
        <tbody>
        <?php

        foreach ($sites as $site){
            $sb_fpc_settings = get_blog_option($site->blog_id, 'servebolt_fpc_settings');
            echo '<tr>';
            echo '<td>';
            echo $site->blog_id;
            echo '</td>';
	        echo '<td>';
	        echo $site->domain.$site->path;
	        echo '</td>';
	        echo '<td>';
	        echo (get_blog_option($site->blog_id, 'servebolt_fpc_switch'))? __('On', 'servebolt-wp') : __('Off', 'servebolt-wp');
	        echo '</td>';
	        echo '<td>';
	        if(!empty($sb_fpc_settings)) foreach ($sb_fpc_settings as $page => $switch){
	            echo $page.' = '.$switch.'<br>';
            }
	        echo '</td>';
	        echo '<td>';
	        echo '<a href="'.get_admin_url( $site->blog_id, 'options-general.php?page=servebolt-nginx-cache' ).'" class="button btn">'.__('Go to site NGINX settings', 'servebolt-wp').'</a>';
	        echo '</td>';
            echo '</tr>';
        }
        ?>
        </tbody>
    </table>
    <?php endif; ?>

</div>