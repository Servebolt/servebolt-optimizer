<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>
<?php use function Servebolt\Optimizer\Helpers\isHostedAtServebolt; ?>

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
                    <div>
                        <img src="<?php echo SERVEBOLT_PLUGIN_DIR_URL; ?>assets/dist/images/<?php echo ($wpCronDisabled && $unixCronSetup ? 'checked' : 'cancel'); ?>.png" width="20"></div>
                        <span>
                            <?php if ($wpCronDisabled && $unixCronSetup): ?>
                            <?php _e('The unix server cron is now enabled (WP Cron is disabled).', 'servebolt-wp'); ?>
                            <?php elseif(!$unixCronSetup && !$wpCronDisabled): ?>
                            <strong><?php _e('WP Cron is not disabled and is not set up to run on the server cron.', 'servebolt-wp'); ?></strong>
                            <?php elseif(!$unixCronSetup): ?>
                            <?php echo sprintf(__('WP Cron is disabled %sbut it is not set up to run on the server cron.%s', 'servebolt-wp'), '<strong>', '</strong>'); ?>
                            <?php elseif(!$wpCronDisabled): ?>
                            <?php echo sprintf(__('%sWP Cron is not disabled%s but it is set up to run on the server cron.', 'servebolt-wp'), '<strong>', '</strong>'); ?>
                            <?php endif; ?>

                            <?php _e('Read more about cron based configuration <a href="https://servebolt.com/help/article/how-to-setup-wordpress-and-woocommerce-cron-jobs/" target="_blank">here</a>.', 'servebolt-wp'); ?>

                            <?php if (isHostedAtServebolt()): ?>
                            <?php 
                                $path = (is_multisite()) ? trailingslashit( network_admin_url() ) :  trailingslashit( get_admin_url() ) ;
                                $admin_page =  $path .  "admin.php?page=servebolt-performance-optimizer-advanced"; 
                            ?>

                            <?php if(!$unixCronSetup || !$wpCronDisabled): ?>
                            <br><?php echo sprintf(__('To fix this then run WP CLI-command %swp servebolt cron enable%s, or choose to enable "Run WP Cron from UNIX cron" from the <a href="%s">Advanced tab</a>', 'servebolt-wp'), '<code>', '</code>', $admin_page); ?>
                            <?php endif; ?>

                            <?php if($unixCronSetup || $wpCronDisabled): ?>
                            <br><?php echo sprintf(__('To disable feature then run WP CLI-command %swp servebolt cron disable%s, or choose to disable "Run WP Cron from UNIX cron" from the <a href="%s">Advanced tab</a> ', 'servebolt-wp'), '<code>', '</code>', $admin_page); ?>
                            <?php endif; ?>

                            <?php endif ?>
                        </span>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</div>
