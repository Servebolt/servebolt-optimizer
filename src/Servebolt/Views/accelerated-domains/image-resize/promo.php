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
                _e('The Accelerated Domains Image Resize enhances the image delivery on your website by resizing and optimizing the images on demand and on the fly at the Accelerated Domains edge. It ensures that the browser downloads the most correct image size, and therefore helps speed up the rendering and delivery of images. Included in the Image Resizing feature is also automatic image format optimization which makes sure the browser receives modern image format if it supports it.', 'servebolt-wp');
                ?>
            </p>
            <p>
                <?php
                echo sprintf(__('The service must be enabled on your Accelerated Domain before it can be used. %sGet in touch with Servebolt support to get Image Resize enabled%s', 'servebolt-wp'), '<a href="' . getServeboltAdminUrl() . '">', '</a>');
                ?>
            </p>

        </div>
        <div class="welcome-panel-column welcome-panel-last" style="padding: 0 1rem 1.5rem 0;">
            <h3><?php _e('Responsive Images', 'servebolt-wp'); ?></h3>
            <p>
                <?php
                _e('Responsive Images is a way to structure images in HTML and make multiple sizes of an image available to the browser, so that the browser can determine what image size fits the screen best. This helps to improve performance across different devices.', 'servebolt-wp');
                ?>
            </p>
            <p>
                <?php
                echo sprintf(__('%sResponsive images has been supported by WordPress since version 3.3%s, and the Accelerated Domains Image Resize improves and enhances the built in support for responsive images.', 'servebolt-wp'), '<a href="https://developer.wordpress.org/apis/handbook/responsive-images/">', '</a>' );
                ?>
            </p>
        </div>
        <div class="welcome-panel-column welcome-panel-last" style="padding: 0 1rem 1.5rem 0;">
            <h3><?php _e('Public beta', 'servebolt-wp'); ?></h3>
            <p>
                <?php
                _e('The Image Resize feature of Accelerated Domains is in a public beta until late September 2021. The feature has been thoroughly tested by the Servebolt team. If any bugs occurr please report them to the Servebolt support team.', 'servebolt-wp');
                ?>
            </p>
            <p>
                <?php
                _e('In the public beta period all Accelerated Domains subscriptions receive 50 000 image resizes per month for free.');
                ?>
            </p>
        </div>
    </div>
</div>
