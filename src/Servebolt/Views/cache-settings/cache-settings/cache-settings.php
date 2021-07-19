<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>

<div class="wrap sb-content">
	<h1><?php _e('Cache', 'servebolt-wp'); ?></h1>

    <?php view('cache-settings.tabs-menu', ['selectedTab' => 'cache-settings']); ?>

    <?php settings_errors(); ?>

	<?php view('cache-settings.cache-settings.promo'); ?>

	<?php if ( is_network_admin() ) : ?>
        <?php view('cache-settings.cache-settings.network-list-view'); ?>
    <?php else : ?>
        <?php view('cache-settings.cache-settings.settings-form'); ?>
	<?php endif; ?>

</div>
