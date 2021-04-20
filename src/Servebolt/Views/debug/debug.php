<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use Servebolt\Optimizer\CachePurge\CachePurge; ?>
<?php use  Servebolt\Optimizer\FullPageCache\FullPageCacheHeaders; ?>
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
		<?php if (is_numeric($item)): ?>
		<pre><a href="<?php echo get_permalink($item); ?>"><?php echo get_the_title($item); ?> (<?php echo $item; ?>)</a></pre>
		<?php else : ?>
		<pre><a href="<?php echo esc_url($item); ?>"><?php echo esc_html($item); ?></a></pre>
		<?php endif; ?>
	<?php endforeach; ?>
    */ ?>

	<hr>

	<h2>Nginx FPC</h2>

	<?php
		$selectedPostTypesToCache  = FullPageCacheHeaders::getPostTypesToCache(false, false);
		$selectedPostTypesToCacheWithoutAll = $selectedPostTypesToCache ? array_filter($selectedPostTypesToCache, function($postType) {
			return $postType !== 'all';
		}) : [];
		$postTypesThatWillBeCached  = FullPageCacheHeaders::getPostTypesToCache();
		$availablePostTypes = FullPageCacheHeaders::getAvailablePostTypesToCache(false);
		$idsToExcludeFromCache = FullPageCacheHeaders::getIdsToExcludeFromCache();
		$defaultPostTypesToCache = FullPageCacheHeaders::getDefaultPostTypesToCache();
	?>

	<p>Feature is active? - <?php echo FullPageCacheHeaders::fpcIsActive() ? __('Yes', 'servebolt-wp') : __('No', 'servebolt-wp'); ?></p>
	<p>Cache all post types? - <?php echo ( is_array($selectedPostTypesToCache) && in_array('all', $selectedPostTypesToCache ) ) ? __('Yes', 'servebolt-wp') : __('No', 'servebolt-wp'); ?></p>
	<p>Any specific post types set? - <?php echo ( is_array($selectedPostTypesToCacheWithoutAll) && ! empty($selectedPostTypesToCacheWithoutAll ) ) ? __('Yes', 'servebolt-wp') : __('No', 'servebolt-wp'); ?> </p>
	<p>Default post types used? - <?php echo ( ! is_array($selectedPostTypesToCache) || empty($selectedPostTypesToCache ) ) ? __('Yes', 'servebolt-wp') : __('No', 'servebolt-wp'); ?> </p>

	<br>

	<strong>Raw stored post type data:</strong>
	<?php if ( $selectedPostTypesToCache ) : ?>
		<ul style="margin-top: 5px;">
		<?php foreach ($selectedPostTypesToCache as $postType) : ?>
			<li>- <?php echo $postType; ?></li>
		<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p>No post types selected</p>
	<?php endif; ?>

	<br>

	<strong>Post types that will be cached:</strong>
	<?php if ($postTypesThatWillBeCached) : ?>
		<ul style="margin-top: 5px;">
		<?php foreach ($postTypesThatWillBeCached as $postType) : ?>
			<li>- <?php echo $postType; ?></li>
		<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p>No post types selected</p>
	<?php endif; ?>

	<br>

	<strong>Default post types to cache:</strong>
	<?php if ($defaultPostTypesToCache) : ?>
		<ul style="margin-top: 5px;">
			<?php foreach ($defaultPostTypesToCache as $postType) : ?>
				<li>- <?php echo $postType; ?></li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p>No post types selected</p>
	<?php endif; ?>

	<br>

	<strong>Post types available to cache (all public post types):</strong>
	<?php if ($availablePostTypes) : ?>
		<ul style="margin-top: 5px;">
			<?php foreach ($availablePostTypes as $postType => $postTypeName ) : ?>
				<li>- <?php echo $postType; ?></li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p>No post types selected</p>
	<?php endif; ?>

	<br>
	<br>

	<strong>Posts to exclude from cache:</strong>
	<?php if ( $idsToExcludeFromCache ) : ?>
		<ul style="margin-top: 5px;">
			<?php foreach ($idsToExcludeFromCache as $postId) : ?>
				<li><a href="<?php echo get_permalink($postId); ?>"><?php echo get_the_title($postId); ?> (<?php echo $postId; ?>)</a></li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p>No posts selected</p>
	<?php endif; ?>

</div>
