<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap">
	<h2><?php sb_e('Errorlog'); ?></h2>
	<p>Log file path: <?php echo $logFilePath; ?></p>
	<?php if (!$logFileExists) : ?>
		<p><?php sb_e('Log file does not exist.'); ?></p>
	<?php elseif (!$logFileReadable) : ?>
		<p><?php sb_e('Log file is not readable.'); ?></p>
	<?php elseif (!$log) : ?>
		<p><?php sb_e('Your error log is empty.'); ?></p>
	<?php else : ?>
		<p><?php printf( sb__('This table lists the %s last entries from today\'s logs/Errorlog'), $numberOfEntries); ?>:</p>
		<table class="wp-list-table widefat striped posts">
			<thead>
			<tr>
				<th><?php sb_e('Timestamp'); ?></th>
				<th><?php sb_e('IP'); ?></th>
				<th><?php sb_e('Error'); ?></th>
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
