<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly
use function Servebolt\Optimizer\Helpers\view;
use function Servebolt\Optimizer\Helpers\getServeboltAdminUrl;
?>

<div class="wrap sb-content">
    <h1><?php _e('Accelerated Domains', 'servebolt-wp'); ?></h1>

    <?php if (is_network_admin()) : ?>
        <?php view('accelerated-domains.image-resize.network-list-view'); ?>
    <?php else : ?>
        <?php view('accelerated-domains.tabs-menu', ['selectedTab' => 'servebolt-acd-image-resize']); ?>
        <?php view('accelerated-domains.image-resize.settings-form', compact('settings')); ?>
    <?php endif; ?>
</div>
