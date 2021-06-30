<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>

<div class="wrap sb-content" id="sb-configuration">
    <h1><?php _e('Prefetching', 'servebolt-wp'); ?></h1>

    <?php if (is_network_admin()) : ?>
        <?php view('prefetching.network-list-view'); ?>
    <?php else : ?>
        <?php view('prefetching.settings-form', compact('settings')); ?>
    <?php endif; ?>
</div>
