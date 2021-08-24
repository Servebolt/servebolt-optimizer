<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>

<div class="wrap sb-content">
	<h1><?php _e('Cache', 'servebolt-wp'); ?></h1>

    <?php view('cache-settings.tabs-menu', ['selectedTab' => 'cache-ttl-settings']); ?>

    <p><?php echo sprintf(__('The Custom Cache TTL settings affects the cache TTL when using Accelerated Domains. It does not, for now, affect the Servebolt Server cache.', 'servebolt-wp')); ?></p>

	<?php if (is_network_admin()) : ?>
        <?php view('cache-settings.cache-ttl.network-list-view'); ?>
    <?php else : ?>
        <?php view('cache-settings.cache-ttl.settings-form', compact('settings', 'postTypes', 'taxonomies', 'cacheTtlOptions')); ?>
	<?php endif; ?>

</div>
