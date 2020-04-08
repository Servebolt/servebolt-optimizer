<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Display notice about composer-files missing.
 */
add_action('admin_notices', function() {
	?>
	<div class="notice notice-error is-dismissable ">
		<h3>Servebolt Optimizer</h3>
		<p><?php printf('Servebolt Optimizer cannot run since the vendor-folder is missing. Make sure to run the following command in the plugin folder:'); ?></p>
		<pre>composer install</pre>
	</div>
	<?php
});
