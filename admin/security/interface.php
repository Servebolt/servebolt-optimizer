<?php

require_once 'wpvuldb.php';

$wpvlun = servebolt_vuln_wp();
$pluginvuln = servebolt_vuln_plugins();

?>
	<div class="wrap sb-content">
		<p><?php _e('We use wpvulndb.com to get information about vulnerabilities in WordPress and Plugins','servebolt-wp'); ?></p>
		<h2>WordPress Vulnerabilities</h2>
		<table class="wp-list-table widefat striped">
			<thead>
			<tr>
				<th><?php _e('Vulnerability', 'servebolt-wp'); ?></th>
				<th><?php _e('Type', 'servebolt-wp'); ?></th>
				<th><?php _e('Fixed in', 'servebolt-wp'); ?></th>
				<th><?php _e('Reference', 'servebolt-wp'); ?></th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<th><?php _e('Vulnerability', 'servebolt-wp'); ?></th>
				<th><?php _e('Type', 'servebolt-wp'); ?></th>
				<th><?php _e('Fixed in', 'servebolt-wp'); ?></th>
				<th><?php _e('Reference', 'servebolt-wp'); ?></th>
			</tr>
			</tfoot>
			<tbody>
			<?php
				foreach ($wpvlun['vulnerabilities'] as $vuln) {
					if ( ! empty( $vuln ) ):
						echo '<tr>';
						echo '<td>' . $vuln['title'] . '</td>';
						echo '<td>' . $vuln['vuln_type'] . '</td>';
						echo '<td>' . $vuln['fixed_in'] . '</td>';
						echo '<td><a href="' . $vuln['references']['url'][0] . '" target="_blank">' . $vuln['references']['url'][0] . '</a></td>';
						echo '</tr>';
					else:
						echo '<tr><td>' . __( 'No known security vulnerabilities in WordPress', 'servebolt-wp' ) . '</td></tr>';

					endif;
				}
			?>
			</tbody>
		</table>
		<h2>Plugin Vulnerabilities</h2>
		<table class="wp-list-table widefat striped">
			<thead>
			<tr>
				<th><?php _e('Plugin', 'servebolt-wp'); ?></th>
				<th><?php _e('Active Version', 'servebolt-wp'); ?></th>
				<th><?php _e('Latest Version', 'servebolt-wp'); ?></th>
				<th><?php _e('Status', 'servebolt-wp'); ?></th>
				<th><?php _e('Vulnerabilities', 'servebolt-wp'); ?></th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<th><?php _e('Plugin', 'servebolt-wp'); ?></th>
				<th><?php _e('Active Version', 'servebolt-wp'); ?></th>
				<th><?php _e('Latest Version', 'servebolt-wp'); ?></th>
				<th><?php _e('Status', 'servebolt-wp'); ?></th>
				<th><?php _e('Vulnerabilities', 'servebolt-wp'); ?></th>
			</tr>
			</tfoot>
			<tbody>
			<?php
			$i = 0;
			foreach ($pluginvuln as $vuln) {
				if ( !empty($vuln['latest_vuln_version']) && version_compare( $vuln['active_version'], $vuln['latest_vuln_version'] ) < 0 ) {
					echo '<tr>';
					echo '<td>' . $vuln['name'] . '</td>';
					echo '<td>' . $vuln['active_version'] . '</td>';
					if ( $vuln['in_wpculndb'] === 1 ) {
						echo '<td>' . $vuln['latest_version'] . '</td>';
						if ( $vuln['is_vulnerable'] === 1 ) {
							echo '<td>' . sprintf( '%s is vulnerable', $vuln['name'] ) . '</td>';
						} else {
							echo '<td>' . sprintf( '%s is OK', $vuln['name'] ) . '</td>';
						}
					} else {
						echo '<td>' . __( 'Not found in WPScan Vulnerability Database', 'servebolt-wp' ) . '</td>';
						echo '<td></td>';
					}
					if ( ! empty( $vuln['all_vulnerabilities'] ) ) {
						echo '<td>';
						echo '<ul>';
						foreach ( $vuln['all_vulnerabilities'] as $thevuln ) {
							if ( ! empty( $thevuln['references']['url'][0] ) ) {
								echo '<li><a href="' . $thevuln['references']['url'][0] . '">' . $thevuln['title'] . '</a></li>';
							}
						}
						echo '</ul>';
						echo '</td>';
					} else {
						echo '<td></td>';
					}
					echo '</tr>';
					$i++;
				}
			}
			if($i === 0){
				echo '<tr>';
				echo '<td>' . __( 'No known security vulnerabilities in your plugins', 'servebolt-wp' ) . '</td>';
			}
			?>
			</tbody>
		</table>
	</div>

<?php
echo '<pre>';
//echo 'checked: ';
//print_r(servebolt_vuln_wp());
echo '</pre>';

