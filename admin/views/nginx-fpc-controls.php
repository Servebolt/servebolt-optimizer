<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap sb-content">
	<h1><?php sb_e('Full Page Cache'); ?></h1>

	<div>
	  <?php $sb_admin_button = $sb_admin_url ? sprintf('<a href="%s" target="_blank">%s</a>', $sb_admin_url, sb__('Servebolt Control Panel dashboard')) : sb__('Servebolt Control Panel dashboard'); ?>
		<p><?php sb_e('Servebolt Full Page Cache is easy to set up, but should always be tested before activating it on production environments.'); ?></p>
		<p><?php printf( sb_esc_html__( 'To activate Full Page Cache to go %s and set "Caching" to "Static Files + Full-Page Cache"'), $sb_admin_button ) ?></p>
    <?php if ( $sb_admin_url ) : ?>
		<p><a href="<?php echo $sb_admin_url; ?>" target="_blank" class="button"><?php sb_e('Servebolt Control Panel dashboard') ?></a></p>
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
        <th><?php sb_e('Full Page Cache Active'); ?></th>
        <th><?php sb_e('Post types'); ?></th>
        <th><?php sb_e('Controls'); ?></th>
      </tr>
      </thead>
      <tfoot>
      <tr>
        <th><?php sb_e('Blog ID'); ?></th>
        <th><?php sb_e('URL'); ?></th>
        <th><?php sb_e('Full Page Cache Active'); ?></th>
        <th><?php sb_e('Post types'); ?></th>
        <th><?php sb_e('Controls'); ?></th>
      </tr>
      </tfoot>
      <tbody>
	    <?php foreach ( get_sites() as $site ) : ?>
		  <?php $sb_fpc_settings = sb_nginx_fpc()->get_post_types_to_cache(false, false, $site->blog_id); ?>
        <tr>
          <td><?php echo $site->blog_id; ?></td>
          <td><?php echo $site->domain . $site->path; ?></td>
          <td><?php echo sb_nginx_fpc()->fpc_is_active($site->blog_id) ? sb__('Yes') : sb__('No'); ?></td>
          <td>
          <?php if ( ! empty($sb_fpc_settings) ) : ?>
            <?php if ( in_array('all', $sb_fpc_settings) ) : ?>
            All
            <?php else: ?>
            <?php foreach ($sb_fpc_settings as $post_type) : ?>
              <?php echo sb_format_post_type($post_type) . '<br>'; ?>
            <?php endforeach; ?>
	          <?php endif; ?>
          <?php else : ?>
          None
          <?php endif; ?>
          </td>
          <td><a href="<?php echo get_admin_url( $site->blog_id, 'admin.php?page=servebolt-nginx-cache' ); ?>" class="button btn"><?php sb_e('Go to site NGINX settings'); ?></a></td>
        </tr>
	    <?php endforeach; ?>
      </tbody>
    </table>

  <?php else : ?>

    <?php
	    $nginx_fpc_active     = sb_nginx_fpc()->fpc_is_active();
      $post_types_to_cache  = sb_nginx_fpc()->get_post_types_to_cache(false, false);
      $available_post_types = sb_nginx_fpc()->get_available_post_types_to_cache(true);
    ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'nginx-fpc-options-page' ) ?>
			<?php do_settings_sections( 'nginx-fpc-options-page' ) ?>
      <div class="nginx_switch">
        <input id="sb-nginx_cache_switch" name="servebolt_fpc_switch" type="checkbox"<?php echo $nginx_fpc_active ? ' checked' : ''; ?>><label for="sb-nginx_cache_switch"><?php sb_e('Turn Full Page Cache on'); ?></label>
      </div>
			<table class="form-table" id="post-types-form"<?php echo ( $nginx_fpc_active ? '' : ' style="display: none;"' ); ?>>
				<tr>
					<th scope="row">Cache post types</th>
					<td>
			      <?php $all_checked = in_array('all', (array) $post_types_to_cache); ?>
            <?php foreach ($available_post_types as $post_type => $post_type_name) : ?>
							<?php $checked = in_array($post_type, (array) $post_types_to_cache) ? ' checked' : ''; ?>
							<span class="<?php if ( $all_checked && $post_type !== 'all' ) echo ' disabled'; ?>"><input id="sb-cache_post_type_<?php echo $post_type; ?>" class="servebolt_fpc_settings_item" name="servebolt_fpc_settings[<?php echo $post_type; ?>]" value="1" type="checkbox"<?php echo $checked; ?>> <label for="sb-cache_post_type_<?php echo $post_type; ?>"><?php echo $post_type_name; ?></label></span><br>
						<?php endforeach; ?>
            <p><?php sb_e('By default this plugin enables Full Page Caching of posts, pages and products. 
                            Activate post types here if you want a different cache setup. 
                            This will override the default setup.'); ?></p>
					</td>
				</tr>
        <tr>
          <th scope="row">Posts to exclude from cache</th>
          <td>

          </td>
        </tr>
			</table>
      <?php submit_button(); ?>

		</form>

	<?php endif; ?>

</div>
