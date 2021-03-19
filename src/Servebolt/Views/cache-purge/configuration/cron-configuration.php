<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\booleanToString; ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>

<tbody class="sb-config-field-general sb-config-field-cloudflare <?php if (!$settings['cache_purge_switch']) echo 'sb-config-field-hidden'; ?>">

    <?php /*
    <tr>
        <th scope="row" colspan="100%" style="padding-bottom: 0;">
            <h3 style="margin-bottom: 0;"><?php _e('Cron setup', 'servebolt-wp'); ?></h3>
            <p style="font-weight: normal;"><?php echo sprintf(__('Use this feature to trigger cache bust by cron instead of doing it immediately. The cron task is set to run every 1 minute. We recommend that you set WordPress up to use the UNIX-based cron. Read about how to achieve this %shere%s.', 'servebolt-wp'), '<a href="https://servebo.lt/vkr8-" target="_">', '</a>'); ?></p>
        </th>
    </tr>
    <tr>
        <th scope="row"><?php _e('Cache purge via cron', 'servebolt-wp'); ?></th>
        <td>
            <fieldset>
                <legend class="screen-reader-text"><span><?php _e('Cache purge via cron', 'servebolt-wp'); ?></span></legend>
                <label for="cf_cron_purge">
                    <input name="<?php if ( ! sb_cf_cache()->cron_state_is_overridden() ) echo getOptionName('cf_cron_purge'); ?>" type="checkbox" id="cf_cron_purge" value="1"<?php if ( sb_cf_cache()->cron_state_is_overridden() ) echo ' disabled'; ?> <?php checked($cf_settings['cf_cron_purge']); ?>>
                    <?php if ( sb_cf_cache()->cron_state_is_overridden() ) : ?>
                        <input type="hidden" name="<?php echo getOptionName('cf_cron_purge'); ?>" value="<?php echo $settings['cf_cron_purge'] ? '1' : '0'; ?>">
                    <?php endif; ?>
                    <?php _e('Use cron to purge cache?', 'servebolt-wp'); ?>
                    <?php if ( sb_cf_cache()->cron_state_is_overridden() ) : ?>
                        <p><em>This value is overriden by the constant "SERVEBOLT_CF_PURGE_CRON" which is currently set to "<?php echo booleanToString(sb_cf_cache()->cron_purge_is_active()); ?>".</em></p>
                    <?php endif; ?>
                </label>
            </fieldset>
        </td>
    </tr>
    */ ?>
</tbody>
