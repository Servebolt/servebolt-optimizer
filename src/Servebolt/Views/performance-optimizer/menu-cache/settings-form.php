<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>

<?php settings_errors(); ?>

<form method="post" autocomplete="off" action="options.php">
    <?php settings_fields('sb-menu-cache-feature-options-page'); ?>
    <?php do_settings_sections('sb-menu-cache-feature-options-page'); ?>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php _e('Menu Cache', 'servebolt-wp'); ?></th>
            <td>
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e('Menu cache-feature active?', 'servebolt-wp'); ?></span></legend>
                    <label for="menu_cache_switch">
                        <input name="<?php echo getOptionName('menu_cache_switch'); ?>" type="checkbox" id="menu_cache_switch" value="1" <?php checked($settings['menu_cache_switch']); ?>>
                        <?php _e('Enabled', 'servebolt-wp'); ?>
                    </label>
                </fieldset>
            </td>
        </tr>
        <tbody id="menu-cache-options"<?php if (!$settings['menu_cache_switch']) echo ' style="display: none;"'; ?>>
            <tr>
                <th scope="row"><?php _e('Disable for logged in users?', 'servebolt-wp'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Disable for logged in users?', 'servebolt-wp'); ?></span></legend>
                        <label for="menu_cache_disabled_for_authenticated_switch">
                            <input name="<?php echo getOptionName('menu_cache_disabled_for_authenticated_switch'); ?>" type="checkbox" id="menu_cache_disabled_for_authenticated_switch" value="1" <?php checked($settings['menu_cache_disabled_for_authenticated_switch']); ?>>
                            <?php _e('Enabled', 'servebolt-wp'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
        </tbody>
    </table>

    <p class="submit">
        <?php submit_button(null, 'primary', 'form-submit', false); ?>
    </p>

</form>
