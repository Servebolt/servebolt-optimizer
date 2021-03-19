<a href="<?php echo admin_url('admin.php?page=servebolt-performance-tools'); ?>" class="sb-button yellow">
    <?php _e('Performance Optimizer'); ?>
</a>

<a href="<?php echo admin_url('admin.php?page=servebolt-cache-purge-control'); ?>" class="sb-button yellow">
    <?php _e('Cache Purging'); ?>
</a>

<?php if (Servebolt\Optimizer\Helpers\featureIsActive('cf_image_resize')) : ?>
    <a href="<?php echo admin_url('admin.php?page=servebolt-cf-image-resizing'); ?>" class="sb-button yellow">
        <span style="position: relative;"><?php _e('Cloudflare Image Resizing'); ?> <span style="position: absolute;top: -8px;right: -30px;font-size: 10px;text-transform: uppercase;">Beta</span></span>
    </a>
<?php endif; ?>

<?php if ( host_is_servebolt() ) : ?>
    <a href="<?php echo admin_url('admin.php?page=servebolt-nginx-cache'); ?>" class="sb-button yellow">
        <?php _e('Full Page Cache settings') ?>
    </a>

    <a href="<?php echo admin_url('admin.php?page=servebolt-logs'); ?>" class="sb-button yellow">
        <?php _e('Review the error log') ?>
    </a>
<?php endif; ?>

<a href="<?php echo admin_url('admin.php?page=servebolt-general-settings'); ?>" class="sb-button yellow"><?php _e('General settings'); ?></a>
