<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>
<?php $envFileIsRead = \Servebolt\Optimizer\Helpers\envFileRead(); ?>
<tr>
    <th scope="row"><?php _e('Run WP Cron from UNIX cron', 'servebolt-wp'); ?></th>
    <td>
        <fieldset>
            <legend class="screen-reader-text"><span><?php _e('Run WP Cron from UNIX cron?', 'servebolt-wp'); ?></span></legend>
            <label for="wp_unix_cron_active">
                <input name="<?php echo getOptionName('wp_unix_cron_active'); ?>" type="checkbox" id="wp_unix_cron_active" value="1" <?php echo $envFileIsRead ? '': 'disabled'; ?> <?php checked( ( $settings['wp_unix_cron_active'] || DISABLE_WP_CRON ) ); ?>>
                <?php _e('Enable', 'servebolt-wp'); ?>
                <p><?php _e('Activating this feature will run the WP Cron from the UNIX cron instead of being triggered by WordPress. This allows for much more reliable task scheduling etc.', 'servebolt-wp'); ?></p>
            </label>
        </fieldset>
    </td>
</tr>
