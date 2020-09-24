<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap sb-content" id="sb-configuration">

	<h1><?php sb_e('Cloudflare Image Resizing'); ?> <sup style="font-size: 12px;">BETA</sup></h1>

    <?php settings_errors(); ?>

  <p>This feature will use Cloudflare Image Resizing to resize the images uploaded in WordPress. Note that this is a <u>beta feature</u> in this plugin.</p>

  <p>Read more about Cloudflares image resize feature <a href="https://developers.cloudflare.com/images/about/" target="_blank">here.</a></p>

	<?php if ( is_network_admin() ) : ?>

      <table class="wp-list-table widefat striped">
        <thead>
        <tr>
          <th><?php sb_e('Blog ID'); ?></th>
          <th><?php sb_e('URL'); ?></th>
          <th><?php sb_e('Image Resizing Active'); ?></th>
          <th><?php sb_e('Controls'); ?></th>
        </tr>
        </thead>
        <tfoot>
        <tr>
          <th><?php sb_e('Blog ID'); ?></th>
          <th><?php sb_e('URL'); ?></th>
          <th><?php sb_e('Image Resizing Active'); ?></th>
          <th><?php sb_e('Controls'); ?></th>
        </tr>
        </tfoot>
        <tbody>
		<?php foreach ( get_sites() as $site ) : ?>
          <tr>
            <td><?php echo $site->blog_id; ?></td>
            <td><?php echo $site->domain . $site->path; ?></td>
            <td><?php echo ( sb_cf_image_resize_control() )->resizing_is_active($site->blog_id) ? sb__('Yes') : sb__('No'); ?></td>
            <td><a href="<?php echo get_admin_url( $site->blog_id, 'admin.php?page=servebolt-cf-image-resizing' ); ?>" class="button btn"><?php sb_e('Go to site settings'); ?></a></td>
          </tr>
		<?php endforeach; ?>
        </tbody>
      </table>

	<?php else : ?>

    <form method="post" autocomplete="off" action="options.php" id="sb-image-resizing-configuration-form">
		<?php settings_fields( 'sb-cf-image-resizing-options-page' ) ?>
		<?php do_settings_sections( 'sb-cf-image-resizing-options-page' ) ?>

      <table class="form-table" id="sb-image-resizing-configuration-table" role="presentation">
        <tr>
          <th scope="row"><?php sb_e('Cloudflare image resize-feature'); ?></th>
          <td>
            <fieldset>
              <legend class="screen-reader-text"><span><?php sb_e('Cloudflare cache-feature active?'); ?></span></legend>
              <label for="cf_image_resizing">
                <input name="<?php echo sb_get_option_name('cf_image_resizing'); ?>" type="checkbox" id="cf_image_resizing" value="1" <?php checked((sb_cf_image_resize_control())->resizing_is_active()); ?>>
				  <?php sb_e('Active?'); ?>
              </label>
            </fieldset>
          </td>
        </tr>
      </table>

      <p class="submit">
		  <?php submit_button(null, 'primary', 'form-submit', false); ?>
        <span class="spinner form-submit-spinner"></span>
      </p>

    </form>

  <?php endif; ?>

</div>
