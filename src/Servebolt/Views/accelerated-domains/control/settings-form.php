<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>

<?php settings_errors(); ?>

<form method="post" autocomplete="off" action="options.php">
    <?php settings_fields('sb-accelerated-domains-options-page'); ?>
    <?php do_settings_sections('sb-accelerated-domains-options-page'); ?>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php _e('Accelerated Domains', 'servebolt-wp'); ?></th>
            <td>
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e('Accelerated Domains-feature active?', 'servebolt-wp'); ?></span></legend>
                    <label for="acd_switch">
                        <input name="<?php echo getOptionName('acd_switch'); ?>" type="checkbox" id="acd_switch" value="1" <?php checked($settings['acd_switch']); ?>>
                        <?php _e('Enable', 'servebolt-wp'); ?>
                    </label>
                </fieldset>
            </td>
        </tr>
        <?php /*
        <tr>
            <th scope="row"><?php _e('Minify HTML?', 'servebolt-wp'); ?></th>
            <td>
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e('Accelerated Domains HTML minify-feature active?', 'servebolt-wp'); ?></span></legend>
                    <label for="acd_minify_switch">
                        <input name="<?php echo getOptionName('acd_minify_switch'); ?>" type="checkbox" id="acd_minify_switch" value="1" <?php checked($settings['acd_minify_switch']); ?>>
                        <?php _e('Enable', 'servebolt-wp'); ?>
                    </label>
                </fieldset>
            </td>
        </tr>
        */ ?>
    </table>

    <p class="submit">
        <?php submit_button(null, 'primary', 'form-submit', false); ?>
        <button type="button" class="button-secondary" id="sb-acd-purge-all-cache">Purge all cache</button>
    </p>

</form>
