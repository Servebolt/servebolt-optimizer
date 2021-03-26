<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use Servebolt\Optimizer\CachePurge\CachePurge; ?>
<?php use function Servebolt\Optimizer\Helpers\nginxFpc; ?>
<div class="wrap sb-content" id="sb-configuration">

	<h1><?php _e('Servebolt Optimizer - Debug information', 'servebolt-wp'); ?></h1>
	<p>This page displays debug information about the settings. A useful tool to ensure integrity of the settings and how they are being presented in the GUI and in the system.</p>

	<hr>

	<h2>Cache purge</h2>

	<p>Feature is active? - <?php echo CachePurge::isActive() ? __('Yes', 'servebolt-wp') : __('No', 'servebolt-wp'); ?></p>
	<p>Feature is configured? - <?php echo CachePurge::featureIsConfigured() ? __('Yes', 'servebolt-wp') : __('No', 'servebolt-wp'); ?></p>
	<p>Feature is available? - <?php echo CachePurge::featureIsAvailable() ? __('Yes', 'servebolt-wp') : __('No', 'servebolt-wp'); ?></p>
	<p>Automatic cache purge on content update? - <?php echo CachePurge::automaticCachePurgeOnContentUpdateIsActive() ? __('Yes', 'servebolt-wp') : __('No', 'servebolt-wp'); ?></p>

    <?php /*
	<p>Cron purging is active? - <?php echo sb_cf_cache()->cron_purge_is_active() ? __('Yes', 'servebolt-wp') : __('No', 'servebolt-wp'); ?> <?php if ( sb_cf_cache()->cron_active_state_override() !== null ) echo ' (overridden with constant)'; ?></p>
	<p>Cron purge execute is active? - <?php echo sb_cf_cache()->should_purge_cache_queue() ? __('Yes', 'servebolt-wp') : __('No', 'servebolt-wp'); ?></p>

	<strong>URL's / ID's to purge cache for</strong>
	<?php foreach ( sb_cf_cache()->get_items_to_purge() as $item ) : ?>
		<?php if ( is_numeric($item) ) : ?>
		<pre><a href="<?php echo get_permalink($item); ?>"><?php echo get_the_title($item); ?> (<?php echo $item; ?>)</a></pre>
		<?php else : ?>
		<pre><a href="<?php echo esc_url($item); ?>"><?php echo esc_html($item); ?></a></pre>
		<?php endif; ?>
	<?php endforeach; ?>
    */ ?>

	<hr>

	<h2>Nginx FPC</h2>

	<?php
		$selected_post_types_to_cache  = nginxFpc()->getPostTypesToCache(false, false);
		$selected_post_types_to_cache_without_all = $selected_post_types_to_cache ? array_filter($selected_post_types_to_cache, function($post_type) {
			return $post_type !== 'all';
		}) : [];
		$post_types_that_will_be_cached  = nginxFpc()->getPostTypesToCache();
		$available_post_types = nginxFpc()->getAvailablePostTypesToCache(false);
		$ids_to_exclude_from_cache = nginxFpc()->getIdsToExcludeFromCache();
		$default_post_types_to_cache = nginxFpc()->getDefaultPostTypesToCache();
	?>

	<p>Feature is active? - <?php echo nginxFpc()->fpcIsActive() ? __('Yes', 'servebolt-wp') : __('No', 'servebolt-wp'); ?></p>
	<p>Cache all post types? - <?php echo ( is_array($selected_post_types_to_cache) && in_array('all', $selected_post_types_to_cache ) ) ? __('Yes', 'servebolt-wp') : __('No', 'servebolt-wp'); ?></p>
	<p>Any specific post types set? - <?php echo ( is_array($selected_post_types_to_cache_without_all) && ! empty($selected_post_types_to_cache_without_all ) ) ? __('Yes', 'servebolt-wp') : __('No', 'servebolt-wp'); ?> </p>
	<p>Default post types used? - <?php echo ( ! is_array($selected_post_types_to_cache) || empty($selected_post_types_to_cache ) ) ? __('Yes', 'servebolt-wp') : __('No', 'servebolt-wp'); ?> </p>

	<br>

	<strong>Raw stored post type data:</strong>
	<?php if ( $selected_post_types_to_cache ) : ?>
		<ul style="margin-top: 5px;">
		<?php foreach ( $selected_post_types_to_cache as $post_type ) : ?>
			<li>- <?php echo $post_type; ?></li>
		<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p>No post types selected</p>
	<?php endif; ?>

	<br>

	<strong>Post types that will be cached:</strong>
	<?php if ( $post_types_that_will_be_cached ) : ?>
		<ul style="margin-top: 5px;">
		<?php foreach ( $post_types_that_will_be_cached as $post_type ) : ?>
			<li>- <?php echo $post_type; ?></li>
		<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p>No post types selected</p>
	<?php endif; ?>

	<br>

	<strong>Default post types to cache:</strong>
	<?php if ( $default_post_types_to_cache ) : ?>
		<ul style="margin-top: 5px;">
			<?php foreach ( $default_post_types_to_cache as $post_type ) : ?>
				<li>- <?php echo $post_type; ?></li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p>No post types selected</p>
	<?php endif; ?>

	<br>

	<strong>Post types available to cache (all public post types):</strong>
	<?php if ( $available_post_types ) : ?>
		<ul style="margin-top: 5px;">
			<?php foreach ( $available_post_types as $post_type => $post_type_name ) : ?>
				<li>- <?php echo $post_type; ?></li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p>No post types selected</p>
	<?php endif; ?>

	<br>
	<br>

	<strong>Posts to exclude from cache:</strong>
	<?php if ( $ids_to_exclude_from_cache ) : ?>
		<ul style="margin-top: 5px;">
			<?php foreach ( $ids_to_exclude_from_cache as $post_id ) : ?>
				<li><a href="<?php echo get_permalink($post_id); ?>"><?php echo get_the_title($post_id); ?> (<?php echo $post_id; ?>)</a></li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p>No posts selected</p>
	<?php endif; ?>

</div>
