<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\getServeboltAdminUrl; ?>
<?php $sbAdminUrl = getServeboltAdminUrl(); ?>
<?php $sbAdminButton = $sbAdminUrl ? sprintf('<a href="%s" target="_blank">%s</a>', $sbAdminUrl, __('Servebolt Admin Panel', 'servebolt-wp')) : __('Servebolt Admin Panel', 'servebolt-wp'); ?>


<div class="sb-welcome-panel" id="acd-welcome-panel">
    <div class="sb-welcome-panel-content">
        <div class="sb-welcome-panel-column-container">
            <div>
                <h3 style="margin-top: 0;"><?php _e('Get started', 'servebolt-wp'); ?></h3>
                <p><?php _e('Generating the menus in WordPress is resource intensive. Sites with small menus and few visitors might not notice. But sites with large menus, like in a mega menu, the server must spend a lot of time generating these large menus on each page view that is not served from cache.', 'servebolt-wp'); ?></p>
                <p><?php _e('Enabling the menu optimizer speeds up the delivery of menus by multiples. The cache is updated whenever you update your menus.', 'servebolt-wp'); ?></p>
                <p><?php echo sprintf(__('%sLearn more about this settings here%s and %smake sure to test this setting before activating it in production%s.', 'servebolt-wp'), '<a href="https://servebo.lt/menu" target="_blank">', '</a>', '<strong style="color: red;">', '</strong>'); ?></p>
            </div>
        </div>
    </div>
</div>
