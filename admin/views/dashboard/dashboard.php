<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap sb-content">
	<div class="sb-logo"></div>
	<h1 class="heading"><?php sb_e('Performance tools'); ?></h1>

    <?php settings_errors(); ?>

    <div class="actions">

	    <?php if ( is_multisite() ) : ?>

            <?php if ( is_network_admin() && is_super_admin() ) : ?>

                <!-- Multisite - network admin -->
                <?php sb_view('admin/views/dashboard/dashboard-multisite-super-admin'); ?>

            <?php else : ?>

                <!-- Multisite - sub site admin -->
                <?php sb_view('admin/views/dashboard/dashboard-multisite-sub-site'); ?>

            <?php endif; ?>

        <?php else : ?>

            <!-- Single site - admin -->
            <?php sb_view('admin/views/dashboard/dashboard-single-site'); ?>

        <?php endif; ?>

    </div>

	<?php if ( ! host_is_servebolt() ) : ?>
        <?php sb_view('admin/views/dashboard/promo-box'); ?>
    <?php endif; ?>

</div>
