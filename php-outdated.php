<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once SERVEBOLT_PLUGIN_PSR4_PATH . 'Helpers/Helpers.php';
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;

/**
 * Display notice about PHP being too outdated.
 */
add_action('admin_notices', function() {
    if (isHostedAtServebolt()) {
        // Warning for Servebolt users, and help to upgrade the PHP version
        ?>
        <div class="notice notice-error is-dismissable">
            <p><?php printf(__('This site is running on a lower PHP version than the Servebolt Optimizer required PHP version. Please upgrade your site to run PHP %s or higher. We highly recommend upgrading to the highest available PHP version.', 'servebolt-wp'), SERVEBOLT_PLUGIN_MINIMUM_PHP_VERSION); ?></p>
            <p><?php printf(__('%sGet in touch with our support%s if you need assistance upgrading your site to a newer PHP version.', 'servebolt-wp'), '<a href="https://admin.servebolt.com/" target="_blank">', '</a>'); ?></p>
        </div>
        <?php
    } else {
        // Warning for non-Servebolt users
        ?>
        <div class="notice notice-error is-dismissable">
            <p><?php printf(__('Servebolt Optimizer cannot run on PHP-versions older than PHP %s. You currently run PHP version %s. Please upgrade PHP to run Servebolt Optimizer.', 'servebolt-wp'), SERVEBOLT_PLUGIN_MINIMUM_PHP_VERSION, phpversion()); ?></p>
        </div>
        <?php
    }
});
