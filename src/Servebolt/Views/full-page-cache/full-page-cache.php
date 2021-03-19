<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\formatPostTypeSlug; ?>
<?php use function Servebolt\Optimizer\Helpers\fpcExcludePostTableRowMarkup; ?>
<?php use function Servebolt\Optimizer\Helpers\nginxFpc; ?>
<div class="wrap sb-content">
	<h1><?php _e('Full Page Cache', 'servebolt-wp'); ?></h1>

    <?php settings_errors(); ?>

	<div>
	  <?php $sbAdminButton = $sbAdminUrl ? sprintf('<a href="%s" target="_blank">%s</a>', $sbAdminUrl, __('Servebolt Control Panel dashboard', 'servebolt-wp')) : __('Servebolt Control Panel dashboard', 'servebolt-wp'); ?>
		<p><?php _e('Servebolt Full Page Cache is easy to set up, but should always be tested before activating it on production environments.', 'servebolt-wp'); ?></p>
		<p><?php printf( esc_html__('To activate Full Page Cache to go %s and set "Caching" to "Static Files + Full-Page Cache"', 'servebolt-wp'), $sbAdminButton ) ?></p>
    <?php if ( $sbAdminUrl ) : ?>
		<p><a href="<?php echo $sbAdminUrl; ?>" target="_blank" class="button"><?php _e('Servebolt Control Panel dashboard', 'servebolt-wp') ?></a></p>
    <?php endif; ?>
	</div>

	<?php if ( is_network_admin() ) : ?>

    <table class="wp-list-table widefat striped">
      <thead>
      <tr>
        <th><?php _e('Blog ID', 'servebolt-wp'); ?></th>
        <th><?php _e('URL', 'servebolt-wp'); ?></th>
        <th><?php _e('Full Page Cache Active', 'servebolt-wp'); ?></th>
        <th><?php _e('Post types', 'servebolt-wp'); ?></th>
        <th><?php _e('Controls', 'servebolt-wp'); ?></th>
      </tr>
      </thead>
      <tfoot>
      <tr>
        <th><?php _e('Blog ID', 'servebolt-wp'); ?></th>
        <th><?php _e('URL', 'servebolt-wp'); ?></th>
        <th><?php _e('Full Page Cache Active', 'servebolt-wp'); ?></th>
        <th><?php _e('Post types', 'servebolt-wp'); ?></th>
        <th><?php _e('Controls', 'servebolt-wp'); ?></th>
      </tr>
      </tfoot>
      <tbody>
	    <?php foreach (get_sites() as $site) : ?>
		  <?php $sb_fpc_settings = nginxFpc()->get_post_types_to_cache(false, false, $site->blog_id); ?>
        <tr>
          <td><?php echo $site->blog_id; ?></td>
          <td><?php echo $site->domain . $site->path; ?></td>
          <td><?php echo nginxFpc()->fpc_is_active($site->blog_id) ? __('Yes', 'servebolt-wp') : __('No', 'servebolt-wp'); ?></td>
          <td>
          <?php if ( ! empty($sb_fpc_settings) ) : ?>
            <?php if ( in_array('all', $sb_fpc_settings) ) : ?>
            All
            <?php else: ?>
            <?php foreach ($sb_fpc_settings as $post_type) : ?>
              <?php echo formatPostTypeSlug($post_type) . '<br>'; ?>
            <?php endforeach; ?>
	          <?php endif; ?>
          <?php else : ?>
          None
          <?php endif; ?>
          </td>
          <td><a href="<?php echo get_admin_url( $site->blog_id, 'admin.php?page=servebolt-nginx-cache' ); ?>" class="button btn"><?php _e('Go to site NGINX settings', 'servebolt-wp'); ?></a></td>
        </tr>
	    <?php endforeach; ?>
      </tbody>
    </table>

  <?php else : ?>

    <?php
	    $nginx_fpc_active     = nginxFpc()->fpc_is_active();
      $post_types_to_cache  = nginxFpc()->get_post_types_to_cache(false, false);
      $available_post_types = nginxFpc()->get_available_post_types_to_cache(true);
    ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'fpc-options-page' ) ?>
			<?php do_settings_sections( 'fpc-options-page' ) ?>
      <div class="nginx_switch">
        <input id="sb-nginx_cache_switch" name="servebolt_fpc_switch" type="checkbox"<?php echo $nginx_fpc_active ? ' checked' : ''; ?>><label for="sb-nginx_cache_switch"><?php _e('Turn Full Page Cache on', 'servebolt-wp'); ?></label>
      </div>
			<table class="form-table" id="sb-nginx-fpc-form"<?php echo ( $nginx_fpc_active ? '' : ' style="display: none;"' ); ?>>
				<tr>
					<th scope="row">Cache post types</th>
					<td>
			      <?php $all_checked = in_array('all', (array) $post_types_to_cache); ?>
            <?php foreach ($available_post_types as $post_type => $post_type_name) : ?>
							<?php $checked = in_array($post_type, (array) $post_types_to_cache) ? ' checked' : ''; ?>
							<span class="<?php if ( $all_checked && $post_type !== 'all' ) echo ' disabled'; ?>"><input id="sb-cache_post_type_<?php echo $post_type; ?>" class="servebolt_fpc_settings_item" name="servebolt_fpc_settings[<?php echo $post_type; ?>]" value="1" type="checkbox"<?php echo $checked; ?>> <label for="sb-cache_post_type_<?php echo $post_type; ?>"><?php echo $post_type_name; ?></label></span><br>
						<?php endforeach; ?>
            <p><?php _e('By default this plugin enables Full Page Caching of posts, pages and products. 
                            Activate post types here if you want a different cache setup. 
                            This will override the default setup.', 'servebolt-wp'); ?></p>
					</td>
				</tr>
        <tr>
          <th scope="row">Posts to exclude from cache</th>
          <td>
            <?php $ids_to_exclude = nginxFpc()->get_ids_to_exclude_from_cache() ?: []; ?>

            <div class="tablenav top">
              <div class="alignleft actions bulkactions">
                <button type="button" class="button action sb-remove-selected-exclude-items" disabled><?php _e('Remove selected', 'servebolt-wp'); ?></button>
              </div>
              <div class="alignleft actions bulkactions">
                <button type="button" style="float:left;" class="button action sb-flush-fpc-exclude-items"<?php if ( count($ids_to_exclude) === 0 ) echo ' disabled'; ?>><?php _e('Flush posts', 'servebolt-wp'); ?></button>
              </div>
              <div class="alignleft actions bulkactions">
                <button class="button button-primary sb-add-exclude-post" type="button">Add post to list</button>
              </div>
              <span class="spinner flush-fpc-exlcude-list-loading-spinner"></span>
              <br class="clear">
            </div>

            <table class="wp-list-table widefat striped" id="nginx-fpc-ids-to-exclude-table">

              <thead>
              <tr>
                <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All', 'servebolt-wp'); ?></label><input id="cb-select-all-1" type="checkbox"></td>
                <th scope="col" id="post_id" class="manage-column column-post-id"><?php _e('Post ID', 'servebolt-wp'); ?></th>
                <th scope="col" id="post_id" class="manage-column column-post-id"><?php _e('Post title', 'servebolt-wp'); ?></th>
                <th scope="col" id="url" class="manage-column column-url"><?php _e('URL', 'servebolt-wp'); ?></th>
              </tr>
              </thead>

              <tfoot>
              <tr>
                <td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2"><?php _e('Select All', 'servebolt-wp'); ?></label><input id="cb-select-all-2" type="checkbox"></td>
                <th scope="col" class="manage-column column-title column-primary"><?php _e('Post ID', 'servebolt-wp'); ?></th>
                <th scope="col" class="manage-column column-title column-primary"><?php _e('Post title', 'servebolt-wp'); ?></th>
                <th scope="col" class="manage-column column-author"><?php _e('URL', 'servebolt-wp'); ?></th>
              </tr>
              </tfoot>

              <tbody id="the-list">
              <tr class="no-items<?php if ( count($ids_to_exclude) > 0 ) echo ' hidden'; ?>"><td colspan="100%"><?php _e('No posts to exclude from cache.', 'servebolt-wp'); ?></td></tr>
	            <?php foreach($ids_to_exclude as $i => $post_id) : ?>
		          <?php fpcExcludePostTableRowMarkup($post_id); ?>
	            <?php endforeach; ?>
              </tbody>

            </table>

            <div class="tablenav bottom">
              <div class="alignleft actions bulkactions">
                <button type="button" id="doaction" class="button action sb-remove-selected-exclude-items" disabled><?php _e('Remove selected', 'servebolt-wp'); ?></button>
              </div>
            </div>

          </td>
        </tr>
			</table>
      <?php submit_button(); ?>

		</form>

	<?php endif; ?>

</div>
