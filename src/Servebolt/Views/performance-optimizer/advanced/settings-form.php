<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>
<?php use function Servebolt\Optimizer\Helpers\actionSchedulerIsActive; ?>
<?php use function Servebolt\Optimizer\Helpers\isHostedAtServebolt; ?>

<?php settings_errors(); ?>

<form method="post" autocomplete="off" action="options.php">
    <?php settings_fields('sb-performance-optimizer-advanced-options-page'); ?>
    <?php do_settings_sections('sb-performance-optimizer-advanced-options-page'); ?>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php _e('Load translations from cache', 'servebolt-wp'); ?></th>
            <td>
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e('Custom MO-file loader active?', 'servebolt-wp'); ?></span></legend>
                    <label for="custom_text_domain_loader_switch">
                        <input name="<?php echo getOptionName('custom_text_domain_loader_switch'); ?>" type="checkbox" id="custom_text_domain_loader_switch" value="1" <?php checked($settings['custom_text_domain_loader_switch']); ?>>
                        <?php _e('Enable', 'servebolt-wp'); ?>
                        <p><?php _e('When WordPress reads the translation files for plugins, themes etc. it does so on every page load. This creates disk read activity which is not good for performance. Activating this feature will speed up this process by storing the translations in cache (transients), so that we get the translations from the cache, instead of reading files on the disk.', 'servebolt-wp'); ?></p>
                        <p><?php _e('Recommended for sites running in other languages than en_US.', 'servebolt-wp'); ?></p>
                    </label>
                </fieldset>
            </td>
        </tr>
        <?php if (!is_multisite() && isHostedAtServebolt()) : ?>
            <?php view('performance-optimizer.advanced.shared-settings.action-scheduler', compact('settings')); ?>
            <?php view('performance-optimizer.advanced.shared-settings.wp-cron', compact('settings')); ?>
        <?php endif; ?>
    </table>

    <p class="submit">
        <?php submit_button(null, 'primary', 'form-submit', false); ?>
    </p>

</form>
