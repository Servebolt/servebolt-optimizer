<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>

<p><?php sb_e('Please navigate to each blog to control settings regarding Cloudflare cache purging.'); ?></p>

<?php view('cache-purge.network-admin.list', $arguments); ?>
