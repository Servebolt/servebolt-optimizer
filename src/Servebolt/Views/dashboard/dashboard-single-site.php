<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\isHostedAtServebolt; ?>

<a href="<?php echo admin_url('admin.php?page=servebolt-performance-optimizer'); ?>" class="sb-button yellow">
    <?php _e('Performance Optimizer', 'servebolt-wp'); ?>
</a>

<?php /*if (Servebolt\Optimizer\Helpers\featureIsActive('cf_image_resize')) : ?>
    <a href="<?php echo admin_url('admin.php?page=servebolt-cf-image-resizing'); ?>" class="sb-button yellow">
        <span style="position: relative;"><?php _e('Cloudflare Image Resizing', 'servebolt-wp'); ?> <span style="position: absolute;top: -8px;right: -30px;font-size: 10px;text-transform: uppercase;">Beta</span></span>
    </a>
<?php endif;*/ ?>

<?php if (isHostedAtServebolt()) : ?>
    <a href="<?php echo admin_url('admin.php?page=servebolt-acd'); ?>" class="sb-button yellow">
        <?php _e('Accelerated Domains', 'servebolt-wp') ?>
    </a>
<?php endif; ?>

<?php if (isHostedAtServebolt()) : ?>
    <a href="<?php echo admin_url('admin.php?page=servebolt-html-cache'); ?>" class="sb-button yellow">
        <?php _e('Cache Settings', 'servebolt-wp') ?>
    </a>
<?php endif; ?>

<a href="<?php echo admin_url('admin.php?page=servebolt-cache-purge-control'); ?>" class="sb-button yellow">
    <?php _e('Cache Purging', 'servebolt-wp'); ?>
</a>

<?php if (isHostedAtServebolt()) : ?>
    <a href="<?php echo admin_url('admin.php?page=servebolt-logs'); ?>" class="sb-button yellow">
        <?php _e('Review the Error Log', 'servebolt-wp') ?>
    </a>
<?php endif; ?>

<a href="<?php echo admin_url('admin.php?page=servebolt-general-settings'); ?>" class="sb-button yellow">
    <?php _e('General Settings', 'servebolt-wp'); ?>
</a>
