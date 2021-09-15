<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>

<div class="wrap sb-content" id="sb-configuration">
    <h1><?php _e('Performance Optimizer', 'servebolt-wp'); ?></h1>

    <?php view('performance-optimizer.tabs-menu', ['selectedTab' => 'servebolt-menu-optimizer']); ?>

    <?php view('performance-optimizer.menu-optimizer.promo'); ?>

    <?php if (is_network_admin()) : ?>
        <?php view('performance-optimizer.menu-optimizer.network-list-view'); ?>
    <?php else : ?>
        <?php view('performance-optimizer.menu-optimizer.settings-form', compact('settings')); ?>
    <?php endif; ?>
</div>
