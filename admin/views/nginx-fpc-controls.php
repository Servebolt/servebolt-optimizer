<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap sb-content">
	<h1><?php sb_e('Full Page Cache'); ?></h1>

	<div>
	  <?php $sb_admin_button = $sb_admin_url ? '<a href="'. get_sb_admin_url() .'">' . sb__('Servebolt site settings') . '</a>' : sb__('Servebolt site settings'); ?>
		<p><?php sb_e('Servebolt Full Page Cache is easy to set up, but should always be tested before activating it on production environments.'); ?></p>
		<p><?php printf( sb_esc_html__( 'To activate Full Page Cache to go %s and set "Caching" to "Static Files + Full-Page Cache"'), $sb_admin_button ) ?></p>
    <?php if ( $sb_admin_url ) : ?>
		<a href="<?php echo $sb_admin_url; ?>" class="button"><?php sb_e('Servebolt site settings') ?></a>
    <?php endif; ?>
	</div>

	<?php if ( array_key_exists('settings-updated', $_GET) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php sb_e('Cache settings saved!'); ?></p></div>
	<?php endif; ?>

	<?php if ( is_network_admin() ) : ?>

    <table class="wp-list-table widefat striped">
      <thead>
      <tr>
        <th><?php sb_e('Blog ID'); ?></th>
        <th><?php sb_e('URL'); ?></th>
        <th><?php sb_e('Full Page Cache Switch'); ?></th>
        <th><?php sb_e('Options'); ?></th>
        <th><?php sb_e('Controls'); ?></th>
      </tr>
      </thead>
      <tfoot>
      <tr>
        <th><?php sb_e('Blog ID'); ?></th>
        <th><?php sb_e('URL'); ?></th>
        <th><?php sb_e('Full Page Cache Switch'); ?></th>
        <th><?php sb_e('Options'); ?></th>
        <th><?php sb_e('Controls'); ?></th>
      </tr>
      </tfoot>
      <tbody>
	    <?php foreach ( $sites as $site ) : ?>
		  <?php $sb_fpc_settings = sb_nginx_fpc()->get_cacheable_post_types(false, $site->blog_id); ?>
        <tr>
          <td><?php echo $site->blog_id; ?></td>
          <td><?php echo $site->domain.$site->path; ?></td>
          <td><?php sb_nginx_fpc()->fpc_is_active($site->blog_id) ? sb__('On') : sb__('Off'); ?></td>
          <td>
			  <?php if ( ! empty($sb_fpc_settings) ) : ?>
				  <?php foreach ($sb_fpc_settings as $page => $switch) : ?>
					  <?php echo $page.' = '.$switch.'<br>'; ?>
				  <?php endforeach; ?>
			  <?php endif; ?>
          </td>
          <td><a href="<?php admin_url( $site->blog_id, 'options-general.php?page=servebolt-nginx-cache' ); ?>" class="button btn"><?php sb_e('Go to site NGINX settings'); ?></a></td>
        </tr>
	    <?php endforeach; ?>
      </tbody>
    </table>

  <?php else : ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'nginx-fpc-options-page' ) ?>
			<?php do_settings_sections( 'nginx-fpc-options-page' ) ?>
      <div class="nginx_switch">
        <input id="nginx_cache_switch" name="servebolt_fpc_switch" type="checkbox"<?php echo $nginx_fpc_active ? ' checked' : ''; ?>><label for="nginx_cache_switch"><?php sb_e('Turn Full Page Cache on'); ?></label>
      </div>
			<table class="form-table" id="post-types-form"<?php echo ( $nginx_fpc_active ? '' : ' style="display: none;"' ); ?>>
				<tr valign="top">
					<th scope="row">Cache post types
						<div>
							<p><?php sb_e('By default this plugin enables Full Page Caching of posts, pages and products. 
                            Activate post types here if you want a different cache setup. 
                            This will override the default setup.'); ?></p>
						</div>
					</th>
					<td>
            <?php foreach ($post_types as $type) : ?>
							<?php $checked = ( is_array($options) && array_key_exists($type->name, $options) ) ? ' checked' : ''; ?>
							<input id="cache_post_type_<?php echo $type->name; ?>" name="servebolt_fpc_settings[<?php echo $type->name; ?>]" type="checkbox"<?php echo $checked; ?>> <label for="cache_post_type_<?php echo $type->name; ?>"><?php echo $type->labels->singular_name; ?></label><br>
						<?php endforeach; ?>
					</td>
				</tr>
			</table>
      <?php submit_button(); ?>

		</form>

	<?php endif; ?>

</div>
