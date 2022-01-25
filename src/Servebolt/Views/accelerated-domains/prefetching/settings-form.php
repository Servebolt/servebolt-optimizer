<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>
<?php use function Servebolt\Optimizer\Helpers\arrayGet; ?>
<?php use Servebolt\Optimizer\AcceleratedDomains\Prefetching\WpPrefetching; ?>

<?php settings_errors(); ?>

<form method="post" autocomplete="off" action="options.php" id="sb-prefetching" data-did-manual-generation="<?php echo array_key_exists('manual-prefetch-success', $_GET); ?>">
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
                    <p>
                        <?php _e('This feature is using WP Cron to write the prefetch manifest files to the disk, so it is strongly recommended that you have a working WP Cron-setup. Otherwise you can use the button below called "Generate manually".', 'servebolt-wp'); ?><br>
                        <?php printf(__('Check out %sSuggested optimizations%s for more information about this.', 'servebolt-wp'), '<a href="' . admin_url('admin.php?page=servebolt-performance-optimizer') . '">', '</a>'); ?>
                    </p>
                    <p>
                        <?php _e('The manifest files will be regenerated when a setting is changed on this page, when the theme is changed, when a plugin is activated/deactivated, or when a menu is changed/deleted or its position is changed.', 'servebolt-wp'); ?>
                    </p>
                    <p></p>
                </fieldset>
            </td>
        </tr>
        <tbody class="options-fields"<?php if (!$settings['prefetch_switch']) echo ' style="display: none;"'; ?>>
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
            <tr>
                <th scope="row"><?php _e('Current prefetch files', 'servebolt-wp'); ?></th>
                <td style="vertical-align: top;padding-top: 20px;">
                    <?php if (is_array($prefetchFiles) && !empty($prefetchFiles)): ?>
                    <ul style="margin-top: 0;">
                        <?php foreach ($prefetchFiles as $prefetchFile): ?>
                        <li><a href="<?php echo esc_attr($prefetchFile); ?>" target="_blank"><?php echo basename($prefetchFile); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <?php _e('No manifest files', 'servebolt-wp'); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Is scheduled for manifest file generation?', 'servebolt-wp'); ?></th>
                <td style="vertical-align: top;padding-top: 20px;">
                    <?php $next = wp_next_scheduled(WpPrefetching::$hook); ?>
                    <?php $nextLocal = get_date_from_gmt(gmdate('Y-m-d H:i:s', $next), 'Y-m-d H:i:s'); ?>
                    <?php echo $next ? sprintf(__('Yes, at %s.', 'servebolt-wp'), $nextLocal) : __('No', 'servebolt-wp'); ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Current prefetch data preview', 'servebolt-wp'); ?></th>
                <td>
                    <textarea class="large-text code" rows="20" readonly><?php echo json_encode($prefetchData, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Actions', 'servebolt-wp'); ?></th>
                <td>
                    <button type="button" class="btn button button-secondary" id="sb-manually-generate-manifest-files" data-href="<?php echo esc_url(WpPrefetching::getFrontPageUrlWithParameters(true)); ?>"<?php if (!$settings['prefetch_switch']) echo ' style="display: none;"'; ?>><?php _e('Generate manually', 'servebolt-wp'); ?></button>
                    <button type="button" class="btn button button-secondary" id="sb-regenerate-manifest-files"<?php if (!$settings['prefetch_switch']) echo ' style="display: none;"'; ?>><?php _e('Regenerate immediately', 'servebolt-wp'); ?></button>
                    <button type="button" class="btn button button-secondary" id="sb-regenerate-manifest-files-using-cron"<?php if (!$settings['prefetch_switch']) echo ' style="display: none;"'; ?>><?php _e('Regenerate using WP Cron', 'servebolt-wp'); ?></button>
                    <span class="spinner regenerate-manifest-files-loading-spinner"></span>
                </td>
            </tr>
        </tbody>
    </table>

    <p class="submit">
        <?php submit_button(null, 'primary', 'form-submit', false); ?>
    </p>

</form>
