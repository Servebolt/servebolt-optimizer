<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>
<?php use Servebolt\Optimizer\Admin\CloudflareImageResize\CloudflareImageResize; ?>
<div class="wrap sb-content" id="sb-configuration">

    <h1><?php _e('Cloudflare Image Resizing', 'servebolt-wp'); ?> <sup style="font-size: 12px;">BETA</sup></h1>

    <?php settings_errors(); ?>
    <p><?php echo sprintf(__('This feature will use %sCloudflare Image Resizing%s to resize the images uploaded in WordPress. Note that this is a <strong>beta feature</strong> and might not work as expected.', 'servebolt-wp'), '<a href="https://servebo.lt/lzlmr" target="_blank">', '</a>'); ?></p>
    <p><?php echo sprintf(__('Cloudflare Image Resize requires your domain to have an active %sCloudflare Business subscription%s, have the Image Resizing feature active, and that your traffic is proxied through Cloudflare.', 'servebolt-wp'), '<a href="https://servebo.lt/agg0t" target="_blank">', '</a>'); ?></p>

    <p></p>

    <?php if ( is_network_admin() ) : ?>

        <table class="wp-list-table widefat striped">
            <thead>
            <tr>
                <th><?php _e('Blog ID', 'servebolt-wp'); ?></th>
                <th><?php _e('URL', 'servebolt-wp'); ?></th>
                <th><?php _e('Image Resizing Active', 'servebolt-wp'); ?></th>
                <th><?php _e('Controls', 'servebolt-wp'); ?></th>
            </tr>
            </thead>
            <tfoot>
            <tr>
                <th><?php _e('Blog ID', 'servebolt-wp'); ?></th>
                <th><?php _e('URL', 'servebolt-wp'); ?></th>
                <th><?php _e('Image Resizing Active', 'servebolt-wp'); ?></th>
                <th><?php _e('Controls', 'servebolt-wp'); ?></th>
            </tr>
            </tfoot>
            <tbody>
            <?php foreach ( get_sites() as $site ) : ?>
                <tr>
                    <td><?php echo $site->blog_id; ?></td>
                    <td><?php echo $site->domain . $site->path; ?></td>
                    <td><?php echo CloudflareImageResize::resizingIsActive($site->blog_id) ? __('Yes', 'servebolt-wp') : __('No', 'servebolt-wp'); ?></td>
                    <td><a href="<?php echo get_admin_url( $site->blog_id, 'admin.php?page=servebolt-cf-image-resizing' ); ?>" class="button btn"><?php _e('Go to site settings', 'servebolt-wp'); ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    <?php else : ?>

        <form method="post" autocomplete="off" action="options.php" id="sb-image-resizing-configuration-form">
            <?php settings_fields('sb-cf-image-resizing-options-page'); ?>
            <?php do_settings_sections('sb-cf-image-resizing-options-page'); ?>

            <table class="form-table" id="sb-image-resizing-configuration-table" role="presentation">
                <tr>
                    <th scope="row"><?php _e('Cloudflare Image Resizing support', 'servebolt-wp'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e('Cloudflare cache-feature active?', 'servebolt-wp'); ?></span></legend>
                            <label for="cf_image_resizing">
                                <input name="<?php echo getOptionName('cf_image_resizing'); ?>" type="checkbox" id="cf_image_resizing" value="1" <?php checked(CloudflareImageResize::resizingIsActive()); ?>>
                                <?php _e('Enable', 'servebolt-wp'); ?>
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
