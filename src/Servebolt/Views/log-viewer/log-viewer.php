<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap">
	<h2><?php _e('Errorlog'); ?></h2>
	<p>Log file path: <?php echo $logFilePath; ?></p>
	<?php if (!$logFileExists) : ?>
		<p><?php _e('Log file does not exist.'); ?></p>
	<?php elseif (!$logFileReadable) : ?>
		<p><?php _e('Log file is not readable.'); ?></p>
	<?php elseif (!$log) : ?>
		<p><?php _e('Your error log is empty.'); ?></p>
	<?php else : ?>
		<p><?php printf( __('This table lists the %s last entries from today\'s logs/Errorlog'), $numberOfEntries); ?>:</p>
		<table class="wp-list-table widefat striped posts">
			<thead>
			<tr>
				<th><?php _e('Timestamp'); ?></th>
				<th><?php _e('IP'); ?></th>
				<th><?php _e('Error'); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($entries as $entry) : ?>
				<tr>
					<td><?php echo $entry->date; ?></td>
					<td><?php echo $entry->ip; ?></td>
					<td><?php echo $entry->error; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
