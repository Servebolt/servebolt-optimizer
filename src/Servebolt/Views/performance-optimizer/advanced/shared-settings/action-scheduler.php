<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>
<?php use function Servebolt\Optimizer\Helpers\actionSchedulerIsActive; ?>
<?php if (actionSchedulerIsActive()): ?>
<tr>
    <th scope="row"><?php _e('Run Action Scheduler from UNIX cron', 'servebolt-wp'); ?></th>
    <td>
        <fieldset>
            <legend class="screen-reader-text"><span><?php _e('Run Action Scheduler from UNIX cron?', 'servebolt-wp'); ?></span></legend>
            <label for="action_scheduler_unix_cron_active">
                <input name="<?php echo getOptionName('action_scheduler_unix_cron_active'); ?>" type="checkbox" id="action_scheduler_unix_cron_active" value="1" <?php checked($settings['action_scheduler_unix_cron_active']); ?>>
                <?php _e('Enable', 'servebolt-wp'); ?>
                <p><?php _e('Activating this feature will run the Action Scheduler for UNIX cron instead of being triggered by WordPress. This allows for much more reliable task scheduling etc.', 'servebolt-wp'); ?></p>
            </label>
        </fieldset>
    </td>
</tr>
<?php endif; ?>
