<?php use function Servebolt\Optimizer\Helpers\envFileRead;
use function Servebolt\Optimizer\Helpers\getServeboltAdminUrl; ?>
<tr>
    <td></td>
    <td>
        <h2 style="margin: 0;"><?php _e('Run WP Cron via UNIX cron', 'servebolt-wp'); ?></h2>
        <?php if (!envFileRead()): ?>
        <p style="margin-top: 10px;color: red;">The environment file could not be read. Due to this we cannot determine whether the WP Cron is setup running from the UNIX Cron.</p>
        <p style="color: red;"><?php printf(__('To fix this then go to your %ssite settings%s, click "Settings" and make sure that the setting "Environment file in home folder" is <strong>not</strong> to "None". Remember to click "Save settings" to ensure that the file is written to disk regardless of the previous state of the setting.', 'servebolt-wp'), '<a href="' . getServeboltAdminUrl() . '" target="_blank">', '</a>'); ?></p>
        <?php endif; ?>
    </td>
</tr>
