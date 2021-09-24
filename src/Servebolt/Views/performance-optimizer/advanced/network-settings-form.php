<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>

<?php settings_errors(); ?>

<form method="post" action="edit.php?action=servebolt-performance-optimizer-advanced">
    <?php wp_nonce_field('servebolt-performance-optimizer-advanced'); ?>

    <table class="form-table" role="presentation">
        <?php view('performance-optimizer.advanced.shared-settings.action-scheduler', compact('settings')); ?>
    </table>

    <p class="submit">
        <?php submit_button(null, 'primary', 'form-submit', false); ?>
    </p>

</form>
