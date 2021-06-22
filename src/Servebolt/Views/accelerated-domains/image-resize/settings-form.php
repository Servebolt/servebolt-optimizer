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
                        <input name="<?php echo getOptionName('acd_image_resize_switch'); ?>" type="checkbox" id="acd_image_resize_switch" value="1" <?php checked($settings['acd_image_resize_switch']); ?>>
                        <?php _e('Enabled', 'servebolt-wp'); ?>
                    </label><br>
                </fieldset>
            </td>
        </tr>
        <tbody id="acd-image-resize-options"<?php if (!$settings['acd_image_resize_switch']) echo ' style="display: none;"'; ?>>
            <tr>
                <th scope="row"></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Apply to src-attribute', 'servebolt-wp'); ?></span></legend>
                        <label for="acd_image_resize_src_tag_switch">
                            <input name="<?php echo getOptionName('acd_image_resize_src_tag_switch'); ?>" type="checkbox" id="acd_image_resize_src_tag_switch" value="1" <?php checked($settings['acd_image_resize_src_tag_switch']); ?>>
                            <code><?php _e('Apply to src-attribute', 'servebolt-wp'); ?></code>
                        </label><br>

                        <legend class="screen-reader-text"><span><?php _e('Apply to srcset-attribute', 'servebolt-wp'); ?></span></legend>
                        <label for="acd_image_resize_srcset_tag_switch">
                            <input name="<?php echo getOptionName('acd_image_resize_srcset_tag_switch'); ?>" type="checkbox" id="acd_image_resize_srcset_tag_switch" value="1" <?php checked($settings['acd_image_resize_srcset_tag_switch']); ?>>
                            <code><?php _e('Apply to srcset-attribute', 'servebolt-wp'); ?></code>
                        </label>

                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Upscale images?', 'servebolt-wp'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Upscale images?', 'servebolt-wp'); ?></span></legend>
                        <label for="acd_image_resize_upscale">
                            <input name="<?php echo getOptionName('acd_image_resize_upscale'); ?>" type="checkbox" id="acd_image_resize_upscale" value="1" <?php checked($settings['acd_image_resize_upscale']); ?>>
                            <?php _e('Enabled', 'servebolt-wp'); ?><br>
                            <p>This will scale up the dimension of images that are too small.</p>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Add half sizes to srcset-attribute?', 'servebolt-wp'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Add half sizes to srcset-attribute?', 'servebolt-wp'); ?></span></legend>
                        <label for="acd_image_resize_half_size_switch">
                            <input name="<?php echo getOptionName('acd_image_resize_half_size_switch'); ?>" type="checkbox" id="acd_image_resize_half_size_switch" value="1" <?php checked($settings['acd_image_resize_half_size_switch']); ?>>
                            <?php _e('Enabled', 'servebolt-wp'); ?><br>
                            <p>This will take the existing sizes in the srcset-attribute and add doubles that are half the size.</p>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="acd_image_resize_quality"><?php _e('Image quality', 'servebolt-wp'); ?></label></th>
                <td>
                    <input type="number" name="<?php echo getOptionName('acd_image_resize_quality'); ?>" min="1" max="100" id="acd_image_resize_quality" value="<?php echo esc_attr($settings['acd_image_resize_quality']); ?>" placeholder="Default value: <?php echo ImageResize::$defaultImageQuality; ?>" class="regular-text">
                    <p class="invalid-message"></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Metadata optimization', 'servebolt-wp'); ?></th>
                <td>
                    <fieldset>

                        <?php /*
                        <legend class="screen-reader-text"><span><?php _e('Keep all metadata', 'servebolt-wp'); ?></span></legend>
                        <label>
                            <input type="radio" name="<?php echo getOptionName('acd_image_resize_metadata_optimization_level'); ?>" value="keep_all" <?php checked($settings['acd_image_resize_metadata_optimization_level'] == 'keep_all'); ?>> <code><?php _e('Keep all metadata', 'servebolt-wp'); ?></code>
                        </label><br>*/ ?>

                        <legend class="screen-reader-text"><span><?php _e('Keep copyright metadata', 'servebolt-wp'); ?></span></legend>
                        <label>
                            <input type="radio" name="<?php echo getOptionName('acd_image_resize_metadata_optimization_level'); ?>" value="keep_copyright" <?php checked($settings['acd_image_resize_metadata_optimization_level'] == 'keep_copyright'); ?>> <code><?php _e('Keep copyright metadata', 'servebolt-wp'); ?></code>
                        </label><br>

                        <legend class="screen-reader-text"><span><?php _e('No metadata', 'servebolt-wp'); ?></span></legend>
                        <label>
                            <input type="radio" name="<?php echo getOptionName('acd_image_resize_metadata_optimization_level'); ?>" value="no_metadata" <?php checked($settings['acd_image_resize_metadata_optimization_level'] == 'no_metadata'); ?>> <code><?php _e('No metadata', 'servebolt-wp'); ?></code>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row">Extra image sizes</th>
                <td>
                    <?php view('accelerated-domains.image-resize.image-size-index', compact('extraSizes')); ?>
                </td>
            </tr>
        </tbody>
    </table>

    <p class="submit">
        <?php submit_button(null, 'primary', 'form-submit', false); ?>
    </p>

</form>
