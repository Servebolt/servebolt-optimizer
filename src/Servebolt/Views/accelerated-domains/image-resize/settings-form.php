<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>
<?php use Servebolt\Optimizer\AcceleratedDomains\ImageResize\ImageResize; ?>

<?php settings_errors(); ?>

<form method="post" autocomplete="off" action="options.php">
    <?php settings_fields('sb-accelerated-domains-image-resize-options-page'); ?>
    <?php do_settings_sections('sb-accelerated-domains-image-resize-options-page'); ?>

    <table class="form-table" role="presentation">
        <tr>
            <th style="padding-bottom: 0;" scope="row"><?php _e('Image resize', 'servebolt-wp'); ?></th>
            <td style="padding-bottom: 0;">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e('Image resize-feature active?', 'servebolt-wp'); ?></span></legend>
                    <label for="acd_image_resize_switch">
                        <input name="<?php echo getOptionName('acd_image_resize_switch'); ?>" type="checkbox" class="options-field-switch" id="acd_image_resize_switch" value="1" <?php checked($settings['acd_image_resize_switch']); ?>>
                        <?php _e('Enable', 'servebolt-wp'); ?>
                    </label><br>
                </fieldset>
            </td>
        </tr>
        <tbody id="tbody-options"<?php if (!$settings['acd_image_resize_switch']) echo ' style="display: none;"'; ?>>
            <tr>
                <th scope="row"></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Apply to src-attribute', 'servebolt-wp'); ?></span></legend>
                        <label for="acd_image_resize_src_tag_switch">
                            <input name="<?php echo getOptionName('acd_image_resize_src_tag_switch'); ?>" type="checkbox" id="acd_image_resize_src_tag_switch" value="1" <?php checked($settings['acd_image_resize_src_tag_switch']); ?>>
                            <?php _e('Enable resizing on regular images', 'servebolt-wp'); ?>
                        </label><br>

                        <legend class="screen-reader-text"><span><?php _e('Apply to srcset-attribute', 'servebolt-wp'); ?></span></legend>
                        <label for="acd_image_resize_srcset_tag_switch">
                            <input name="<?php echo getOptionName('acd_image_resize_srcset_tag_switch'); ?>" type="checkbox" id="acd_image_resize_srcset_tag_switch" value="1" <?php checked($settings['acd_image_resize_srcset_tag_switch']); ?>>
                            <?php _e('Enable resizing on responsive images (recommended)', 'servebolt-wp'); ?>
                        </label>

                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Upscale images', 'servebolt-wp'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Upscale images?', 'servebolt-wp'); ?></span></legend>
                        <label for="acd_image_resize_upscale">
                            <input name="<?php echo getOptionName('acd_image_resize_upscale'); ?>" type="checkbox" id="acd_image_resize_upscale" value="1" <?php checked($settings['acd_image_resize_upscale']); ?>>
                            <?php _e('Enable', 'servebolt-wp'); ?><br>
                            <p><?php _e('This will scale up the dimension of images if the image is too small for the requested image size.', 'servebolt-wp'); ?></p>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Add half sizes to responsive images', 'servebolt-wp'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Add half sizes to srcset-attribute?', 'servebolt-wp'); ?></span></legend>
                        <label for="acd_image_resize_half_size_switch">
                            <input name="<?php echo getOptionName('acd_image_resize_half_size_switch'); ?>" type="checkbox" id="acd_image_resize_half_size_switch" value="1" <?php checked($settings['acd_image_resize_half_size_switch']); ?>>
                            <?php _e('Enable', 'servebolt-wp'); ?><br>
                            <p><?php _e('When enabled this will automatically add half sizes of all registered image sizes and help deliver the best possible size to the browser.', 'servebolt-wp'); ?></p>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="acd_image_resize_quality"><?php _e('Image quality', 'servebolt-wp'); ?></label></th>
                <td>
                    <input type="number" name="<?php echo getOptionName('acd_image_resize_quality'); ?>" min="1" max="100" id="acd_image_resize_quality" value="<?php echo esc_attr($settings['acd_image_resize_quality']); ?>" placeholder="Default value: <?php echo ImageResize::$defaultImageQuality; ?>" class="regular-text">
                    <label for="<?php echo getOptionName('acd_image_resize_quality'); ?>">
                        <p><?php _e('You can adjust the quality the resized images should be delivered in. A lower quality means lower file size, and can both be downloaded and rendered in the browser faster.', 'servebolt-wp'); ?></p>
                    </label>
                    <p class="invalid-message"></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Metadata optimization', 'servebolt-wp'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Keep all metadata', 'servebolt-wp'); ?></span></legend>
                        <label>
                            <input type="radio" name="<?php echo getOptionName('acd_image_resize_metadata_optimization_level'); ?>" value="keep" <?php checked($settings['acd_image_resize_metadata_optimization_level'] == 'keep'); ?>> <code><?php _e('Keep all metadata', 'servebolt-wp'); ?></code>
                        </label><br>

                        <legend class="screen-reader-text"><span><?php _e('Keep copyright metadata', 'servebolt-wp'); ?></span></legend>
                        <label>
                            <input type="radio" name="<?php echo getOptionName('acd_image_resize_metadata_optimization_level'); ?>" value="copyright" <?php checked($settings['acd_image_resize_metadata_optimization_level'] == 'copyright'); ?>> <code><?php _e('Keep copyright metadata', 'servebolt-wp'); ?></code>
                        </label><br>

                        <legend class="screen-reader-text"><span><?php _e('No metadata', 'servebolt-wp'); ?></span></legend>
                        <label>
                            <input type="radio" name="<?php echo getOptionName('acd_image_resize_metadata_optimization_level'); ?>" value="none" <?php checked($settings['acd_image_resize_metadata_optimization_level'] == 'none'); ?>> <code><?php _e('No metadata', 'servebolt-wp'); ?></code>
                        </label>
                    </fieldset>
                    <p><?php _e('Metadata on images (EXIF) is usually not needed and removing it will optimize the size of the images', 'servebolt-wp'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Extra responsive image sizes', 'servebolt-wp'); ?></th>
                <td>
                    <p><?php _e('When resizing on responsive images is enabled you can add custom image sizes to optimize the image size downloaded by the browser.', 'servebolt-wp'); ?>
                    <?php view('accelerated-domains.image-resize.image-size-index', compact('extraSizes')); ?>
                </td>
            </tr>
        </tbody>
    </table>

    <p class="submit">
        <?php submit_button(null, 'primary', 'form-submit', false); ?>
    </p>

</form>
