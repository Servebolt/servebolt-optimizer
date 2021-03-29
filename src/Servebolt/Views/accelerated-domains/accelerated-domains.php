<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>

<div class="wrap sb-content">
    <h1><?php _e('Accelerated Domains', 'servebolt-wp'); ?></h1>

    <?php if ( is_network_admin() ) : ?>
        <?php view('accelerated-domains.network-list-view'); ?>
    <?php else : ?>
        <?php view('accelerated-domains.settings-form', compact('settings')); ?>
    <?php endif; ?>
</div>
