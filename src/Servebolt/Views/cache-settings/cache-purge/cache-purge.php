<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>
<?php use function Servebolt\Optimizer\Helpers\isHostedAtServebolt; ?>

<div class="wrap sb-content" id="sb-configuration">

    <h1><?php _e('Cache', 'servebolt-wp'); ?></h1>
    <?php view('cache-settings.tabs-menu', ['selectedTab' => 'cache-purge-settings']); ?>

    <?php if (is_network_admin()) : ?>
        <?php view('cache-settings.cache-purge.network-admin.network-admin', $arguments); ?>
    <?php else : ?>
        <?php view('cache-settings.cache-purge.configuration.configuration', $arguments); ?>
    <?php endif; ?>
</div>
