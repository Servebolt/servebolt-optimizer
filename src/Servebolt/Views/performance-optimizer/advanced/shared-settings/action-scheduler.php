<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>
<?php use function Servebolt\Optimizer\Helpers\actionSchedulerIsActive; ?>
<?php $envFileIsRead = \Servebolt\Optimizer\Helpers\envFileRead(); ?>
<tr>
    <th scope="row"><?php _e('Run Action Scheduler from UNIX cron', 'servebolt-wp'); ?></th>
    <td>
        <fieldset>
            <legend class="screen-reader-text"><span><?php _e('Run Action Scheduler from UNIX cron?', 'servebolt-wp'); ?></span></legend>
            <label for="action_scheduler_unix_cron_active">
                <input name="<?php echo getOptionName('action_scheduler_unix_cron_active'); ?>" type="checkbox" id="action_scheduler_unix_cron_active" value="1" <?php echo $envFileIsRead ? '': 'disabled'; ?> <?php checked($settings['action_scheduler_unix_cron_active']); ?>>
                <?php _e('Enable', 'servebolt-wp'); ?>
                <p><?php _e('Activating this feature will run the Action Scheduler for UNIX cron instead of being triggered by WordPress. This allows for much more reliable task scheduling etc.', 'servebolt-wp'); ?></p>
                <?php if (!actionSchedulerIsActive()): ?>
                <p><?php _e('Note: it does not seem like Action Scheduler is active. This might not be right since it depends on how Action Scheduler is used by plugins etc. It is recommended to test to make sure it is working properly.', 'servebolt-wp'); ?></p>
                <?php endif; ?>
            </label>
        </fieldset>
    </td>
</tr>
