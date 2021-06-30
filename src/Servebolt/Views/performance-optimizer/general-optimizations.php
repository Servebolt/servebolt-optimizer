<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\isDevDebug; ?>

<div class="wrap sb-content">

	<h1><?php _e('Performance Optimizer', 'servebolt-wp'); ?></h1>

  <?php if (isDevDebug()) : ?>
    <button type="button" class="sb-deoptimize-database button" style="margin-top: 10px;">De-optimize database!</button>
  <?php endif; ?>

	<h3><?php _e('Database Indexes', 'servebolt-wp'); ?></h3>
	<table class="wp-list-table widefat fixed striped sb-db-indx">
		<thead>
		<tr>
			<th><?php _e('Optimization', 'servebolt-wp'); ?></th>
			<th><?php _e('Status', 'servebolt-wp'); ?></th>
      <?php if ( $indexFixAvailable ) : ?>
      <th><?php _e('Fix', 'servebolt-wp'); ?></th>
      <?php endif; ?>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<th><?php _e('Optimization', 'servebolt-wp'); ?></th>
			<th><?php _e('Status', 'servebolt-wp'); ?></th>
		  <?php if ( $indexFixAvailable ) : ?>
      <th><?php _e('Fix', 'servebolt-wp'); ?></th>
      <?php endif; ?>
		</tr>
		</tfoot>
		<tbody>
		<?php if ( $tables === false ) : ?>
			<tr><td><?php _e('All the Servebolt recommended indexes exists', 'servebolt-wp'); ?></td><td></td>
		<?php else : ?>
			<?php foreach ($tables as $table) : ?>
				<tr>
					<td><?php printf(__('Index in the %s table on the %s column', 'servebolt-wp'), $table['name'], $table['index']); ?></td>
					<td>
            <div class="status-indicator-container">
              <?php if ( $table['has_index'] ) : ?>
              <div><img src="<?php echo SERVEBOLT_PLUGIN_DIR_URL; ?>assets/dist/images/checked.png" width="20"></div> <?php _e('This table has the right indexes', 'servebolt-wp'); ?>
              <?php else: ?>
              <div><img src="<?php echo SERVEBOLT_PLUGIN_DIR_URL; ?>assets/dist/images/cancel.png" width="20"></div> <?php _e('Run Optimize to add the index', 'servebolt-wp'); ?>
              <?php endif; ?>
            </div>
					</td>
		      <?php if ($indexFixAvailable) : ?>
          <td>
	          <?php if ( ! $table['has_index'] ) : ?>
            <a href="#" class="sb-create-index" data-blog-id="<?php echo array_key_exists('blog_id', $table) ? esc_attr($table['blog_id']) : ''; ?>" data-table="<?php echo esc_attr($table['table']); ?>"><?php _e('Create index', 'servebolt-wp'); ?></a>
            <?php endif; ?>
          </td>
          <?php endif; ?>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>

  <br>
	<h3><?php _e('Database Table Storage Engines', 'servebolt-wp'); ?></h3>
	<table class="wp-list-table widefat fixed striped">
		<thead>
		<tr>
			<th><?php _e('Table', 'servebolt-wp'); ?></th>
			<th><?php _e('Engine', 'servebolt-wp'); ?></th>
			<th><?php _e('Convert to', 'servebolt-wp'); ?></th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<th><?php _e('Table', 'servebolt-wp'); ?></th>
			<th><?php _e('Engine', 'servebolt-wp'); ?></th>
			<th><?php _e('Convert to', 'servebolt-wp'); ?></th>
		</tr>
		</tfoot>
		<tbody>
		<?php if (empty($myisamTables)) : ?>
			<tr>
				<td><?php esc_html_e('All tables use modern storage engines', 'servebolt-wp'); ?></td>
				<td></td>
				<td></td>
			</tr>
		<?php else : ?>
			<?php foreach ($myisamTables as $obj) : ?>
				<tr>
					<td><?php echo $obj->TABLE_NAME; ?></td>
					<td><?php echo $obj->ENGINE; ?></td>
					<td><a href="#" class="sb-convert-table" data-table="<?php echo $obj->TABLE_NAME; ?>"><?php _e('Convert to InnoDB', 'servebolt-wp'); ?></a></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>

  <br>
	<div class="optimize">
		<h3><?php _e('Run the optimizer', 'servebolt-wp'); ?></h3>
		<p><?php _e('You can run the optimizer below.', 'servebolt-wp'); ?><br>
			<strong><?php _e('Always backup your database before running optimization!', 'servebolt-wp'); ?></strong>
		</p>
		<a class="btn button button-primary sb-optimize-now"><?php _e('Optimize!', 'servebolt-wp'); ?></a>
	</div>

  <br>
	<h2><?php _e('Other suggested optimizations', 'servebolt-wp'); ?></h2>
	<p><?php _e('These settings can not be optimized by the plugin, but may be implemented manually.', 'servebolt-wp'); ?></p>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php _e('Optimization', 'servebolt-wp'); ?></th>
				<th><?php _e('How to', 'servebolt-wp'); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th><?php _e('Optimization', 'servebolt-wp'); ?></th>
				<th><?php _e('How to', 'servebolt-wp'); ?></th>
			</tr>
		</tfoot>
		<tbody>
			<tr>
				<td>
					<?php _e('Disable WP Cron and run it from server cron', 'servebolt-wp'); ?>
				</td>
				<td>
          <div class="status-indicator-container">
            <?php if ($wpCronDisabled === true) : ?>
            <div><img src="<?php echo SERVEBOLT_PLUGIN_DIR_URL; ?>assets/dist/images/checked.png" width="20"></div> <span><?php _e('WP Cron is disabled. Remember to activate the cron on the server instead. Read more about this <a href="https://servebo.lt/vkr8-" target="_blank">here</a>.</span>', 'servebolt-wp'); ?></span>
            <?php else : ?>
            <div><img src="<?php echo SERVEBOLT_PLUGIN_DIR_URL; ?>assets/dist/images/cancel.png" width="20"></div> <span><?php _e('WP Cron is enabled, and may slow down your site and/or degrade the sites ability to scale. This should be disabled and run with server cron. Read more about this <a href="https://servebo.lt/vkr8-" target="_blank">here</a>.</span>', 'servebolt-wp'); ?>
            <?php endif;?>
          </div>
				</td>
			</tr>
		</tbody>
	</table>

</div>
