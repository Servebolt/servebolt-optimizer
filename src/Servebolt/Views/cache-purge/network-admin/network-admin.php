<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>

<p><?php _e('Please navigate to each blog to control settings regarding automatic cache purging.', 'servebolt-wp'); ?></p>

<?php view('cache-purge.network-admin.list', $arguments); ?>
