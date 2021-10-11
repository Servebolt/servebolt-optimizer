<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>

<?php settings_errors(); ?>

<form method="post" autocomplete="off" action="options.php">
    <?php settings_fields('sb-menu-optimizer-feature-options-page'); ?>
    <?php do_settings_sections('sb-menu-optimizer-feature-options-page'); ?>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php _e('Menu Optimizer', 'servebolt-wp'); ?></th>
            <td>
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e('Menu optimizer-feature active?', 'servebolt-wp'); ?></span></legend>
                    <label for="menu_optimizer_switch">
                        <input name="<?php echo getOptionName('menu_cache_switch'); ?>" type="checkbox" id="menu_optimizer_switch" class="options-field-switch" value="1" <?php checked($settings['menu_cache_switch']); ?>>
                        <?php _e('Enable', 'servebolt-wp'); ?>
                    </label>
                </fieldset>
            </td>
        </tr>
        <tbody id="options-fields"<?php if (!$settings['menu_cache_switch']) echo ' style="display: none;"'; ?>>
            <tr>
                <th scope="row"><?php _e('Disable for logged in users?', 'servebolt-wp'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Disable for logged in users?', 'servebolt-wp'); ?></span></legend>
                        <label for="menu_optimizer_disabled_for_authenticated_switch">
                            <input name="<?php echo getOptionName('menu_cache_disabled_for_authenticated_switch'); ?>" type="checkbox" id="menu_optimizer_disabled_for_authenticated_switch" value="1" <?php checked($settings['menu_cache_disabled_for_authenticated_switch']); ?>>
                            <?php _e('Enable', 'servebolt-wp'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Automatic cache purging when menu is updated?', 'servebolt-wp'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Automatic cache purging when menu is updated?', 'servebolt-wp'); ?></span></legend>
                        <label for="menu_cache_auto_cache_purge_on_menu_update">
                            <input name="<?php echo getOptionName('menu_cache_auto_cache_purge_on_menu_update'); ?>" type="checkbox" id="menu_cache_auto_cache_purge_on_menu_update" value="1" <?php checked($settings['menu_cache_auto_cache_purge_on_menu_update']); ?>>
                            <?php _e('Enable', 'servebolt-wp'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Automatic cache purging when front page settings is updated?', 'servebolt-wp'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Automatic cache purging when front page settings is updated?', 'servebolt-wp'); ?></span></legend>
                        <label for="menu_cache_auto_cache_purge_on_front_page_settings_update">
                            <input name="<?php echo getOptionName('menu_cache_auto_cache_purge_on_front_page_settings_update'); ?>" type="checkbox" id="menu_cache_auto_cache_purge_on_front_page_settings_update" value="1" <?php checked($settings['menu_cache_auto_cache_purge_on_front_page_settings_update']); ?>>
                            <?php _e('Enable', 'servebolt-wp'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Display timing metrics?', 'servebolt-wp'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Display timing metrics?', 'servebolt-wp'); ?></span></legend>
                        <label for="menu_cache_run_timing">
                            <input name="<?php echo getOptionName('menu_cache_run_timing'); ?>" type="checkbox" id="menu_cache_run_timing" value="1" <?php checked($settings['menu_cache_run_timing']); ?>>
                            <?php _e('Enable', 'servebolt-wp'); ?>
                        </label>
                    </fieldset>
                    <p>
                        <?php _e('Use this feature only for debugging purposes. Metrics will be displayed in the system log.', 'servebolt-wp'); ?>
                        <?php _e('To display the output in the menu markup then one can add the following code: <code>add_filter("sb_optimizer_menu_optimizer_timing_output_to_screen", "__return_true");</code>', 'servebolt-wp'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Execute menu cache without considering current page?', 'servebolt-wp'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Execute menu cache without considering current page?', 'servebolt-wp'); ?></span></legend>
                        <label for="menu_cache_simple_menu_signature">
                            <input name="<?php echo getOptionName('menu_cache_simple_menu_signature'); ?>" type="checkbox" id="menu_cache_simple_menu_signature" value="1" <?php checked($settings['menu_cache_simple_menu_signature']); ?>>
                            <?php _e('Enable', 'servebolt-wp'); ?>
                        </label>
                    </fieldset>
                    <p><?php _e('Since the current menu item are often highlighted the system need to cache the menu output for each page. If the site does not display the active state on menu items you can check this. This will also save some database resources.', 'servebolt-wp'); ?></p>
                </td>
            </tr>
        </tbody>
    </table>

    <p class="submit">
        <?php submit_button(null, 'primary', 'form-submit', false); ?>
        <button type="button" class="button-secondary" id="sb-menu-optimizer-purge-all-cache"><?php _e('Purge all menu cache', 'servebolt-wp'); ?></button>
    </p>

</form>
