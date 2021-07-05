<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>

<?php settings_errors(); ?>

<form method="post" autocomplete="off" action="options.php">
    <?php settings_fields('sb-performance-optimizer-advanced-options-page'); ?>
    <?php do_settings_sections('sb-performance-optimizer-advanced-options-page'); ?>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php _e('Custom MO-file loader', 'servebolt-wp'); ?></th>
            <td>
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e('Custom MO-file loader active?', 'servebolt-wp'); ?></span></legend>
                    <label for="custom_text_domain_loader_switch">
                        <input name="<?php echo getOptionName('custom_text_domain_loader_switch'); ?>" type="checkbox" id="custom_text_domain_loader_switch" value="1" <?php checked($settings['custom_text_domain_loader_switch']); ?>>
                        <?php _e('Enabled', 'servebolt-wp'); ?>
                    </label>
                </fieldset>
            </td>
        </tr>
    </table>

    <p class="submit">
        <?php submit_button(null, 'primary', 'form-submit', false); ?>
    </p>

</form>
