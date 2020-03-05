<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div class="wrap sb-content">
	<?php if ( array_key_exists('optimize-now', $_GET) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php sb_e('Cache settings saved!'); ?></p></div>
	<?php endif; ?>
	<h2>⚡️<?php sb_e('Servebolt Optimize'); ?></h2>

  <!--<button class="wreak-havoc button">Wreak havoc!</button>-->

	<h3><?php sb_e('Database Indexes'); ?></h3>
	<table class="wp-list-table widefat fixed striped sb-db-indx">
		<thead>
		<tr>
			<th><?php sb_e('Optimization'); ?></th>
			<th><?php sb_e('Status'); ?></th>
      <?php if ( $index_fix_available ) : ?>
      <th><?php sb_e('Fix'); ?></th>
      <?php endif; ?>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<th><?php sb_e('Optimization'); ?></th>
			<th><?php sb_e('Status'); ?></th>
		  <?php if ( $index_fix_available ) : ?>
      <th><?php sb_e('Fix'); ?></th>
      <?php endif; ?>
		</tr>
		</tfoot>
		<tbody>
		<?php if ( $tables === false ) : ?>
			<tr><td><?php sb_e('All the Servebolt recommended indexes exists'); ?></td><td></td>
		<?php else : ?>
			<?php foreach ($tables as $table) : ?>
				<tr>
					<td><?php printf(sb__('Index in the %s table on the %s column'), $table['name'], $table['index']); ?></td>
					<td>
            <div class="status-indicator-container">
              <?php if ( $table['has_index'] ) : ?>
              <div><img src="<?php echo SERVEBOLT_PATH_URL; ?>admin/assets/img/checked.png" width="20"></div> <?php sb_e('This table has the right indexes'); ?>
              <?php else: ?>
              <div><img src="<?php echo SERVEBOLT_PATH_URL; ?>admin/assets/img/cancel.png" width="20"></div> <?php sb_e('Run Optimize to add the index'); ?>
              <?php endif; ?>
            </div>
					</td>
		      <?php if ( $index_fix_available ) : ?>
          <td>
	          <?php if ( ! $table['has_index'] ) : ?>
            <a href="#" class="create-index" data-blog-id="<?php echo array_key_exists('blog_id', $table) ? esc_attr($table['blog_id']) : ''; ?>" data-table="<?php echo esc_attr($table['table']); ?>"><?php sb_e('Create index'); ?></a>
            <?php endif; ?>
          </td>
          <?php endif; ?>
				</tr>
				<?php $run_optimizer = true; ?>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>

  <br>
	<h3><?php sb_e('Database Table Storage Engines'); ?></h3>
	<table class="wp-list-table widefat fixed striped">
		<thead>
		<tr>
			<th><?php sb_e('Table'); ?></th>
			<th><?php sb_e('Engine'); ?></th>
			<th><?php sb_e('Convert to'); ?></th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<th><?php sb_e('Table'); ?></th>
			<th><?php sb_e('Engine'); ?></th>
			<th><?php sb_e('Convert to'); ?></th>
		</tr>
		</tfoot>
		<tbody>
		<?php if (empty($myisam_tables)) : ?>
			<tr>
				<td><?php sb_esc_html_e('All tables use modern storage engines'); ?></td>
				<td></td>
				<td></td>
			</tr>
		<?php else : ?>
			<?php foreach ( $myisam_tables as $obj ) : ?>
				<tr>
					<td><?php echo $obj->TABLE_NAME; ?></td>
					<td><?php echo $obj->ENGINE; ?></td>
					<td><a href="#" class="convert-table" data-table="<?php echo $obj->TABLE_NAME; ?>"><?php sb_e('Create index'); ?></a></td>
				</tr>
				<?php $run_optimizer = true; ?>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>

  <br>
	<div class="optimize">
		<h3><?php sb_e('Run the optimizer'); ?></h3>
		<p><?php sb_e('You can run the optimizer below.'); ?><br>
			<strong><?php sb_e('Always backup your database before running optimization!'); ?></strong>
		</p>
		<a <?php if($run_optimizer === true) echo 'href="#optimize-now"'; ?> class="btn button button-primary optimize-now" <?php if($run_optimizer !== true) echo 'disabled'; ?>><?php sb_e('Optimize!'); ?></a>
	</div>

  <br>
	<h2><?php sb_e('Other suggested optimizations'); ?></h2>
	<p><?php sb_e('These settings can not be optimized by the plugin, but may be implemented manually.'); ?></p>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php sb_e('Optimization'); ?></th>
				<th><?php sb_e('How to'); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th><?php sb_e('Optimization'); ?></th>
				<th><?php sb_e('How to'); ?></th>
			</tr>
		</tfoot>
		<tbody>
			<tr>
				<td>
					<?php sb_e('Disable WP Cron and run it from server cron'); ?>
				</td>
				<td>
          <div class="status-indicator-container">
            <?php if ( $wp_cron_disabled === true ) : ?>
            <div><img src="<?php echo SERVEBOLT_PATH_URL; ?>admin/assets/img/checked.png" width="20"></div> <?php sb_e('WP Cron is disabled. Remember to set on cron on the server.'); ?>
            <?php else : ?>
            <div><img src="<?php echo SERVEBOLT_PATH_URL; ?>admin/assets/img/cancel.png" width="20"></div> <?php sb_e('WP Cron is enabled, and may slow down your site and/or degrade the sites ability to scale. This should be disabled and run with server cron.'); ?>
            <?php endif;?>
          </div>
				</td>
			</tr>
		</tbody>
	</table>

</div>
