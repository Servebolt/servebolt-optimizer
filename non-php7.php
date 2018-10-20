<?php
function servebolt_php_version_notice() {
    ?>
    <div class="notice notice-error is-dismissable ">
        <p><?php echo sprintf(__( 'Servebolt Optimizer cannot run on PHP versions older than PHP7. You currently run PHP version %s. Upgrade PHP to run Servebolt Optimizer.', 'servebolt-wp' ), phpversion()); ?></p>
    </div>
    <?php
}
add_action( 'admin_notices', 'servebolt_php_version_notice' );