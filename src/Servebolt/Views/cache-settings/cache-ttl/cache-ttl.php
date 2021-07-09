<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>

<div class="wrap sb-content">
	<h1><?php _e('Cache', 'servebolt-wp'); ?></h1>

    <?php view('cache-settings.tabs-menu', ['selectedTab' => 'cache-ttl-settings']); ?>

    <?php settings_errors(); ?>

    <?php /*
	<div>
	  <?php $sbAdminButton = $sbAdminUrl ? sprintf('<a href="%s" target="_blank">%s</a>', $sbAdminUrl, __('Servebolt Control Panel dashboard', 'servebolt-wp')) : __('Servebolt Control Panel dashboard', 'servebolt-wp'); ?>
	  	<p><?php _e('These cache settings let you control the cache headers set by the plugin. The cache headers are used to control how caching works in Accelerated Domains, Cloudflare and the Servebolt Cloud.', 'servebolt-wp'); ?></p>
		<div>
			<h3><?php _e('Servebolt Cloud HTML cache', 'servebolt-wp'); ?></h3>
			<p><?php _e('Servebolt Cloud HTML cache (Full Page Cache) is easy to set up, but should always be tested before activating it on production environments.', 'servebolt-wp'); ?></p>
			<p><?php printf( esc_html__('To activate HTML cache to go %s and set "Caching" to "Static Files + Full-Page Cache".', 'servebolt-wp'), $sbAdminButton ) ?></p>
			<?php if ( $sbAdminUrl ) : ?>
				<p><a href="<?php echo $sbAdminUrl; ?>" target="_blank" class="button"><?php _e('Servebolt Control Panel dashboard', 'servebolt-wp') ?></a></p>
			<?php endif; ?>
		</div>

	</div>

	<?php if ( is_network_admin() ) : ?>
        <?php view('cache-settings.cache-ttl.network-list-view'); ?>
    <?php else : ?>
        <?php view('cache-settings.cache-ttl.settings-form'); ?>
	<?php endif; */ ?>

</div>
