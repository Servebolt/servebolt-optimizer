<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>
<?php use function Servebolt\Optimizer\Helpers\isDevDebug; ?>
<div class="wrap sb-content">
    <h1><?php _e('General settings', 'servebolt-wp'); ?></h1>

    <?php if (isDevDebug()) : ?>
        <button class="sb-clear-all-settings button" style="margin-top: 10px;">Reset all settings</button>
    <?php endif; ?>

    <?php settings_errors(); ?>

    <br>

    <?php if ( is_network_admin() ) : ?>

        <?php view('general-settings.network-list-view', $arguments); ?>

    <?php else : ?>

        <?php view('general-settings.settings-form', $arguments); ?>

    <?php endif; ?>

</div>
