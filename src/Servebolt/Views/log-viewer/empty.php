<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<div class="wrap">
    <h2><?php echo isset($pageTitle) ? esc_html($pageTitle) : esc_html__('Logs', 'servebolt-wp'); ?></h2>
    <div class="notice notice-info" style="padding:16px;">
        <p style="margin:0;">
            <?php echo esc_html__('No readable log files were found for this site.', 'servebolt-wp'); ?>
        </p>
        <p style="margin-top:8px; color:#50575e;">
            <?php echo esc_html__('Once a PHP or HTTP error log is available, it will appear here with tabs and filters.', 'servebolt-wp'); ?>
        </p>
    </div>
</div>

