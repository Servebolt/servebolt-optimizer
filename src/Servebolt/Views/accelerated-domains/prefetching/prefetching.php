<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>

<div class="wrap sb-content" id="sb-configuration">
    <h1><?php _e('Performance Optimizer', 'servebolt-wp'); ?></h1>

    <?php view('accelerated-domains.tabs-menu', ['selectedTab' => 'servebolt-prefetching']); ?>

    <?php if (is_network_admin()) : ?>
        <?php view('accelerated-domains.prefetching.network-list-view'); ?>
    <?php else : ?>
        <?php view('accelerated-domains.prefetching.settings-form', compact('settings', 'defaultMaxNumberOfLines', 'prefetchData', 'prefetchFiles')); ?>
    <?php endif; ?>
</div>
