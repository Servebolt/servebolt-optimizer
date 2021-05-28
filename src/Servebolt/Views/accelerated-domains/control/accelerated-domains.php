<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>

<div class="wrap sb-content">
    <h1><?php _e('Accelerated Domains', 'servebolt-wp'); ?></h1>

    <?php if (is_network_admin()) : ?>
        <?php view('accelerated-domains.control.network-list-view'); ?>
    <?php else : ?>
        <?php view('accelerated-domains.tabs-menu', ['selectedTab' => 'servebolt-acd']); ?>
        <?php view('accelerated-domains.control.settings-form', compact('settings')); ?>
    <?php endif; ?>

    <?php view('accelerated-domains.acd-promo'); ?>
</div>
