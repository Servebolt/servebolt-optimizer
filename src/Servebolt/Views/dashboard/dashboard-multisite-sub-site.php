<a href="<?php echo admin_url('admin.php?page=servebolt-cache-purge-control'); ?>" class="sb-button yellow">
    <?php sb_e('Cache Purging'); ?>
</a>

<?php if ( sb_feature_active('cf_image_resize') ) : ?>
    <a href="<?php echo admin_url('admin.php?page=servebolt-cf-image-resizing'); ?>" class="sb-button yellow">
        <span style="position: relative;"><?php sb_e('Cloudflare Image Resizing'); ?> <span style="position: absolute;top: -8px;right: -30px;font-size: 10px;text-transform: uppercase;">Beta</span></span>
    </a>
<?php endif; ?>

<?php if ( host_is_servebolt() ) : ?>
    <a href="<?php echo admin_url('admin.php?page=servebolt-nginx-cache'); ?>" class="sb-button yellow">
        <?php sb_e('Full Page Cache settings') ?>
    </a>

    <a href="<?php echo admin_url('admin.php?page=servebolt-logs'); ?>" class="sb-button yellow">
        <?php sb_e('Review the error log') ?>
    </a>
<?php endif; ?>

<a href="<?php echo admin_url('admin.php?page=servebolt-general-settings'); ?>" class="sb-button yellow">
    <?php sb_e('General settings'); ?>
</a>

<?php if ( is_super_admin() ) : ?>
    <a href="<?php echo network_admin_url('admin.php?page=servebolt-wp'); ?>" class="sb-button yellow">
        <?php sb_e('Go to network admin for more options') ?>
    </a>
<?php endif; ?>
