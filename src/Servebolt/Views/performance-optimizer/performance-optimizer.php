<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>

<div class="wrap sb-content">
    <h1><?php _e('Performance Optimizer', 'servebolt-wp'); ?></h1>
    <?php view('performance-optimizer.tabs-menu', ['selectedTab' => 'servebolt-performance-optimizer']); ?>

    <h2><?php _e('Suggested optimizations', 'servebolt-wp'); ?></h2>

    <p><?php _e('These settings can not be optimized by the plugin, but may be implemented manually.', 'servebolt-wp'); ?></p>
    <table class="wp-list-table widefat fixed striped">
        <thead>
        <tr>
            <th><?php _e('Optimization', 'servebolt-wp'); ?></th>
            <th><?php _e('How to', 'servebolt-wp'); ?></th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <th><?php _e('Optimization', 'servebolt-wp'); ?></th>
            <th><?php _e('How to', 'servebolt-wp'); ?></th>
        </tr>
        </tfoot>
        <tbody>
        <tr>
            <td>
                <?php _e('Disable WP Cron and run it from server cron', 'servebolt-wp'); ?>
            </td>
            <td>
                <div class="status-indicator-container">
                    <?php if ($wpCronDisabled === true) : ?>
                        <div><img src="<?php echo SERVEBOLT_PLUGIN_DIR_URL; ?>assets/dist/images/checked.png" width="20"></div> <span><?php _e('WP Cron is disabled. Remember to activate the cron on the server instead. Read more about this <a href="https://servebo.lt/vkr8-" target="_blank">here</a>.</span>', 'servebolt-wp'); ?></span>
                    <?php else : ?>
                    <div><img src="<?php echo SERVEBOLT_PLUGIN_DIR_URL; ?>assets/dist/images/cancel.png" width="20"></div> <span><?php _e('WP Cron is enabled, and may slow down your site and/or degrade the sites ability to scale. This should be disabled and run with server cron. Read more about this <a href="https://servebo.lt/vkr8-" target="_blank">here</a>.</span>', 'servebolt-wp'); ?>
                        <?php endif;?>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</div>
