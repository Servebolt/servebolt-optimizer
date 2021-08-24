<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>

<?php settings_errors(); ?>

<form method="post" autocomplete="off" action="options.php">
    <?php settings_fields('sb-prefetch-feature-options-page'); ?>
    <?php do_settings_sections('sb-prefetch-feature-options-page'); ?>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php _e('Prefetching', 'servebolt-wp'); ?></th>
            <td>
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e('Prefetching-feature active?', 'servebolt-wp'); ?></span></legend>
                    <label for="prefetch_switch">
                        <input name="<?php echo getOptionName('prefetch_switch'); ?>" class="options-field-switch" type="checkbox" id="prefetch_switch" value="1" <?php checked($settings['prefetch_switch']); ?>>
                        <?php _e('Enable', 'servebolt-wp'); ?>
                    </label>
                </fieldset>
            </td>
        </tr>
        <tbody id="options-fields"<?php if (!$settings['prefetch_switch']) echo ' style="display: none;"'; ?>>
            <tr>
                <th scope="row"><?php _e('Manifest files', 'servebolt-wp'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Generate style manifest-file?', 'servebolt-wp'); ?></span></legend>
                        <label>
                            <input type="checkbox" name="<?php echo getOptionName('prefetch_file_style_switch'); ?>" value="1" <?php checked($settings['prefetch_file_style_switch']); ?>> <code><?php _e('Styles', 'servebolt-wp'); ?></code>
                        </label><br>

                        <legend class="screen-reader-text"><span><?php _e('Generate script manifest-file?', 'servebolt-wp'); ?></span></legend>
                        <label>
                            <input type="checkbox" name="<?php echo getOptionName('prefetch_file_script_switch'); ?>" value="1" <?php checked($settings['prefetch_file_script_switch']); ?>> <code><?php _e('Scripts', 'servebolt-wp'); ?></code>
                        </label><br>

                        <legend class="screen-reader-text"><span><?php _e('Generate menu manifest-file?', 'servebolt-wp'); ?></span></legend>
                        <label>
                            <input type="checkbox" name="<?php echo getOptionName('prefetch_file_menu_switch'); ?>" value="1" <?php checked($settings['prefetch_file_menu_switch']); ?>> <code><?php _e('Menu', 'servebolt-wp'); ?></code>
                        </label>
                    </fieldset>
                    <p><?php _e('Check the file types that you would like to generate.', 'servebolt-wp'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Use full URLs in manifest files', 'servebolt-wp'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Use full URLs in manifest files?', 'servebolt-wp'); ?></span></legend>
                        <label for="prefetch_full_url_switch">
                            <input name="<?php echo getOptionName('prefetch_full_url_switch'); ?>" type="checkbox" id="prefetch_full_url_switch" value="1" <?php checked($settings['prefetch_full_url_switch']); ?>>
                            <?php _e('Enable', 'servebolt-wp'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="prefetch_max_number_of_lines"><?php _e('Max number of lines', 'servebolt-wp'); ?></label></th>
                <td>
                    <input type="number" name="<?php echo getOptionName('prefetch_max_number_of_lines'); ?>" min="1" id="prefetch_max_number_of_lines" placeholder="Default: <?php echo esc_attr($defaultMaxNumberOfLines); ?>" value="<?php echo esc_attr($settings['prefetch_max_number_of_lines']); ?>" class="regular-text">
                    <label for="<?php echo getOptionName('prefetch_max_number_of_lines'); ?>">
                        <p><?php _e('Use this field to limit the number of lines in the prefetch files. This can be useful for example if the website has a large amount of menu items.', 'servebolt-wp'); ?></p>
                    </label>
                </td>
            </tr>
        </tbody>
    </table>

    <p class="submit">
        <?php submit_button(null, 'primary', 'form-submit', false); ?>
        <a class="btn button button-secondary" id="sb-regenerate-prefetch-files"><?php _e('Regenerate files', 'servebolt-wp'); ?></a>
        <span class="spinner regenerate-prefetch-files-loading-spinner"></span>
    </p>

</form>
