<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\getServeboltAdminUrl; ?>


<div class="welcome-panel" id="acd-welcome-panel">
    <div class="welcome-panel-content">
        <div class="welcome-panel-column-container">
            <div>
                <h2><?php _e('Image Resizing', 'servebolt-wp'); ?></h2>
            </div>
        </div>

        <div class="welcome-panel-column" style="padding: 0 1rem 1.5rem 0;">
            <h3><?php _e('What is Image Resizing?', 'servebolt-wp'); ?></h3>
            <p>
                <?php
                _e('The Image Resize feature inside Accelerated Domains enhances the image delivery on your website. It optimizes and resizes the images on the fly when needed on the Accelerated Domains edge. It helps speed up the delivery and rendering of images in the browser by serving only the most correct image size. Included in this Image Resizing feature is an automatic image format optimization – which makes sure the browser receives a modern image format if it supports it.', 'servebolt-wp');
                ?>
            </p>
            <p>
                <?php
                echo sprintf(__('The service must be enabled in Accelerated Domains for your site before it can be used. %sPlease get in touch with Servebolt support to get Image Resize enabled%s', 'servebolt-wp'), '<a href="' . getServeboltAdminUrl() . '">', '</a>');
                ?>
            </p>

        </div>
        <div class="welcome-panel-column welcome-panel-last" style="padding: 0 1rem 1.5rem 0;">
            <h3><?php _e('Improving Responsive Images', 'servebolt-wp'); ?></h3>
            <p>
                <?php
                _e('The Responsive Images feature inside WordPress allows you to deliver the best image size for your screen by making multiple sizes of an image available to the browser. Responsive Images help improve image performance across different devices.', 'servebolt-wp');
                ?>
            </p>
            <p>
                <?php
                echo sprintf(__('%sResponsive images has been supported by WordPress since version 3.3%s, and the Accelerated Domains Image Resize feature improves and enhances the built-in support for responsive images with its optimizations it does on the fly.', 'servebolt-wp'), '<a href="https://developer.wordpress.org/apis/handbook/responsive-images/">', '</a>' );
                ?>
            </p>
        </div>
        <div class="welcome-panel-column welcome-panel-last" style="padding: 0 1rem 1.5rem 0;">
            <h3><?php _e('Public Beta', 'servebolt-wp'); ?></h3>
            <p>
                <?php
                _e('The Image Resize feature of Accelerated Domains is in public beta until late September 2021. The feature has been thoroughly tested by the Servebolt team. However, if you run into any bugs, please report them to the Servebolt support team.', 'servebolt-wp');
                ?>
            </p>
            <p>
                <?php
                _e('During the public beta period, all Accelerated Domains subscriptions receive 50 000 image resizes per month for free.');
                ?>
            </p>
        </div>
    </div>
</div>
