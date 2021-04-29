<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Display notice about PHP being too outdated.
 */
add_action('admin_notices', function() {
	?>
  <div class="notice notice-error is-dismissable">
    <!--<pre>composer install</pre>-->
    <p><?php printf(__('Servebolt Optimizer cannot run on PHP-versions older than PHP 7. You currently run PHP version %s. Please upgrade PHP to run Servebolt Optimizer.', 'servebolt-wp'), phpversion()); ?></p>
  </div>
	<?php
});
