<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Display notice about composer-files missing.
 */
add_action('admin_notices', function() {
	?>
	<div class="notice notice-error is-dismissable ">
		<h3><?php sb_e('Servebolt Optimizer'); ?></h3>
    <p><?php sb_e('Seems like we have a problem and the plugin cannot function.'); ?></p>
    <p style="margin-bottom: 0;"><?php sb_e('Possible solutions:'); ?></p>
    <ul style="list-style-type: disc;padding-left: 30px;margin-top: 0;line-height: normal;">
      <li><?php printf(sb__('Check that the vendor-folder-is present in the plugin folder. If not the reinstall the plugin or run %scomposer install%s in the plugin directory.'), '<pre style="display: inline-block;">', '</pre>'); ?></li>
      <li><?php sb_e('Try to deactivate and activate the plugin.'); ?></li>
    </ul>
	</div>
	<?php
});
