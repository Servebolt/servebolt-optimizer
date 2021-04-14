<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>
<div class="wrap sb-content">
    <h1><?php _e('General settings', 'servebolt-wp'); ?></h1>

    <?php settings_errors(); ?>

    <br>

    <?php if ( is_network_admin() ) : ?>

        <?php view('general-settings.network-list-view', $arguments); ?>

    <?php else : ?>

        <?php view('general-settings.settings-form', $arguments); ?>

    <?php endif; ?>

</div>
