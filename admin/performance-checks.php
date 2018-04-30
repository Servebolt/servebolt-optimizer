<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once SERVEBOLT_PATH . 'admin/optimize-db/checks.php';

?>
<div id="optimizations-loading" class=""><img src="<?PHP echo SERVEBOLT_PATH_URL; ?>admin/assets/img/loading.apng" alt="Spinner design by https://loading.io/spinner/magnify" /></div>
<div class="wrap sb-content">
	<?php if (isset($_GET['optimize-now'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php _e('Cache settings saved!', 'servebolt-wp'); ?></p></div>
	<?php endif; ?>
    <h2>⚡️<?php _e('Servebolt Optimize', 'servebolt-wp'); ?></h2>
    <h3><?php _e('Database Indexes', 'servebolt-wp'); ?></h3>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Optimization', 'servebolt-wp'); ?></th>
                <th><?php _e('Status', 'servebolt-wp'); ?></th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th><?php _e('Optimization', 'servebolt-wp'); ?></th>
                <th><?php _e('Status', 'servebolt-wp'); ?></th>
            </tr>
            </tfoot>
            <tbody>
            <?php
            $tables = tables_to_have_index();
            foreach ($tables as $table){
                echo '<tr>';
                echo '<td>'.sprintf(__('Index in the %s table on the %s column', 'servebolt-wp'), $table['name'], $table['index']).'</td>';
                echo '<td>';
                echo ($table['has_index'] === false)
                    ? '<img src="' . SERVEBOLT_PATH_URL . 'admin/assets/img/cancel.png" width="20"> '. __('Run Optimize to add the index')
                    : '<img src="' . SERVEBOLT_PATH_URL . 'admin/assets/img/checked.png" width="20"> '. __('This table has the right indexes');
                echo '</td>';
            }
            ?>
        </tbody>
    </table>
    <h3><?php _e('Database Table Storage Engines'); ?></h3>
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
		<?php
		$myisam_tables = get_myisam_tables();
		if (empty($myisam_tables)) {
          echo '<tr>';
          echo '  <td>' . esc_html__('All tables use modern storage engines','servebolt-wp') . '</td>';
          echo '  <td></td>';
          echo '  <td></td>';
          echo '</tr>';
		}
		else {
          foreach ( $myisam_tables as $obj ) {
            echo '<tr>';
            echo '<td>' . $obj->TABLE_NAME . '</td>';
            echo '<td>' . $obj->ENGINE . '</td>';
            echo '<td><a href="#optimize" class="optimize-now">' . __('Convert to InnoDB', 'servebolt-wp') . '</a></td>';
            echo '</tr>';
          }
		}
		?>
        </tbody>

    </table>
    <div class="optimize">
        <h3><?php _e('Run the optimizer', 'servebolt-wp'); ?></h3>
        <p><?php _e('You can run the optimizer below.', 'servebolt-wp'); ?><br>
        <strong><?php _e('Always backup your database before running optimization!', 'servebolt-wp'); ?></strong>
        </p>
        <a href="#optimize-now" class="btn button button-primary optimize-now"><?php _e('Optimize!', 'servebolt-wp'); ?></a>
    </div>
    <h2><?php _e('Other suggested optimizations'); ?></h2>
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
                <?php echo (wp_cron_disabled() === true)
                    ? '<img src="' . SERVEBOLT_PATH_URL . 'admin/assets/img/checked.png" width="20"> '.__('WP Cron is disabled. Remember to set on cron on the server.', 'servebolt-wp')
                    : '<img src="' . SERVEBOLT_PATH_URL . 'admin/assets/img/cancel.png" width="20"> '.__('WP Cron is enabled, and may slow down your site and/or degrade the sites ability to scale. This should be disabled and run with server cron.', 'servebolt-wp');
                ?>
            </td>
        </tr>
        </tbody>
    </table>
</div>