<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\isHostedAtServebolt; ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>

<div class="wrap sb-content">
	<div class="sb-logo"></div>
	<h1 class="heading"><?php _e('Performance Tools', 'servebolt-wp'); ?></h1>

    <?php settings_errors(); ?>

    <div class="actions">

	    <?php if ( is_multisite() ) : ?>

            <?php if ( is_network_admin() && is_super_admin() ) : ?>

                <!-- Multisite - network admin -->
                <?php view('dashboard.dashboard-multisite-super-admin', $arguments); ?>

            <?php else : ?>

                <!-- Multisite - sub site admin -->
                <?php view('dashboard.dashboard-multisite-sub-site', $arguments); ?>

            <?php endif; ?>

        <?php else : ?>

            <!-- Single site - admin -->
            <?php view('dashboard.dashboard-single-site', $arguments); ?>

        <?php endif; ?>

    </div>

	<?php if (!isHostedAtServebolt()) : ?>
        <?php view('dashboard.promo-box', $arguments); ?>
    <?php endif; ?>

</div>
