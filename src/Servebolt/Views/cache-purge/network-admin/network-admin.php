<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php use Servebolt\Optimizer\Helpers as Helpers; ?>

<p><?php sb_e('Please navigate to each blog to control settings regarding Cloudflare cache purging.'); ?></p>

<?php Helpers\view('cache-purge.network-admin.list', $arguments); ?>
