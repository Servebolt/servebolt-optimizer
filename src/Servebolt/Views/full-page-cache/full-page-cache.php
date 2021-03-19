<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>

<div class="wrap sb-content">
	<h1><?php _e('Full Page Cache', 'servebolt-wp'); ?></h1>

    <?php settings_errors(); ?>

	<div>
	  <?php $sbAdminButton = $sbAdminUrl ? sprintf('<a href="%s" target="_blank">%s</a>', $sbAdminUrl, __('Servebolt Control Panel dashboard', 'servebolt-wp')) : __('Servebolt Control Panel dashboard', 'servebolt-wp'); ?>
		<p><?php _e('Servebolt Full Page Cache is easy to set up, but should always be tested before activating it on production environments.', 'servebolt-wp'); ?></p>
		<p><?php printf( esc_html__('To activate Full Page Cache to go %s and set "Caching" to "Static Files + Full-Page Cache"', 'servebolt-wp'), $sbAdminButton ) ?></p>
    <?php if ( $sbAdminUrl ) : ?>
		<p><a href="<?php echo $sbAdminUrl; ?>" target="_blank" class="button"><?php _e('Servebolt Control Panel dashboard', 'servebolt-wp') ?></a></p>
    <?php endif; ?>
	</div>

	<?php if ( is_network_admin() ) : ?>

        <?php view('full-page-cache.network-list-view'); ?>

    <?php else : ?>

        <?php view('full-page-cache.settings-form'); ?>

	<?php endif; ?>

</div>
