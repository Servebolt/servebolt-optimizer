<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly
use Servebolt\Optimizer\AcceleratedDomains\ImageResize\FeatureAccess;
use function Servebolt\Optimizer\Helpers\view;
?>

<div class="wrap sb-content" id="sb-configuration">
    <h1><?php _e('Accelerated Domains', 'servebolt-wp'); ?></h1>

    <?php if (is_network_admin()) : ?>
        <?php view('accelerated-domains.image-resize.promo'); ?>
        <?php view('accelerated-domains.image-resize.network-list-view'); ?>
    <?php else : ?>
        <?php view('accelerated-domains.tabs-menu', ['selectedTab' => 'servebolt-acd-image-resize']); ?>
        <?php view('accelerated-domains.image-resize.promo'); ?>

        <?php if (apply_filters('sb_optimizer_acd_image_resize_access_check', true) && !FeatureAccess::hasAccess()): ?>
            <h3><?php _e('You do not have access to the Image Resize-feature', 'servebolt-wp'); ?></h3>
            <p><?php _e('Please contact support to enable Image Resize.', 'servebolt-wp'); ?></p>
        <?php else: ?>
            <?php view('accelerated-domains.image-resize.settings-form', compact('settings', 'extraSizes')); ?>
        <?php endif; ?>


    <?php endif; ?>

</div>
