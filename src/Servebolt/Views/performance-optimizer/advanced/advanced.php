<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>

<div class="wrap sb-content">
    <h1><?php _e('Performance Optimizer', 'servebolt-wp'); ?></h1>
    <?php view('performance-optimizer.tabs-menu', ['selectedTab' => 'servebolt-performance-optimizer-advanced']); ?>

    <?php if (is_network_admin()) : ?>
        <?php view('performance-optimizer.advanced.network-list-view'); ?>
    <?php else : ?>
        <?php view('performance-optimizer.advanced.settings-form', compact('settings')); ?>
    <?php endif; ?>
</div>
