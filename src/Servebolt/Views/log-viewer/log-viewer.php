<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\arrayGet; ?>
<div class="wrap">
	<h2><?php _e('Errorlog', 'servebolt-wp'); ?></h2>
	<p>Log file path: <?php echo $logFilePath; ?></p>
	<?php if (!$logFileExists) : ?>
    <div class="notice notice-warning">
		<p><?php _e('The log file does not exist.', 'servebolt-wp'); ?></p>
    </div>
	<?php elseif (!$logFileReadable) : ?>
		<p><?php _e('Log file is not readable.', 'servebolt-wp'); ?></p>
	<?php elseif (!$log) : ?>
		<p><?php _e('Your error log is empty.', 'servebolt-wp'); ?></p>
	<?php else : ?>
		<p><?php printf( __('This table lists the %s last entries from today\'s logs/Errorlog', 'servebolt-wp'), $numberOfEntries); ?>:</p>
		<table class="wp-list-table widefat striped posts">
			<thead>
			<tr>
				<th><?php _e('Timestamp', 'servebolt-wp'); ?></th>
				<th><?php _e('IP', 'servebolt-wp'); ?></th>
				<th><?php _e('Error', 'servebolt-wp'); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($entries as $entry) : ?>
				<tr>
                    <?php if ($unparsedLine = arrayGet('unparsed_line', $entry)): ?>
                    <td colspan="100%" title="Could not parse error line, showing raw content"><?php echo $unparsedLine; ?></td>
                    <?php else: ?>
					<td><?php echo $entry->date; ?></td>
					<td><?php echo $entry->ip; ?></td>
					<td><?php echo $entry->error; ?></td>
                    <?php endif; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
