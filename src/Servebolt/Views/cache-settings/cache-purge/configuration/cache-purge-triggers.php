<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use Servebolt\Optimizer\CachePurge\CachePurge; ?>
<?php use function Servebolt\Optimizer\Helpers\isHostedAtServebolt; ?>

<?php if (CachePurge::featureIsAvailable()) : ?>
    <p>
        <button type="button" class="sb-purge-all-cache sb-button yellow inline"><?php _e('Purge All Cache', 'servebolt-wp'); ?></button>
        <?php if (isHostedAtServebolt()): ?>
            <button type="button" class="sb-purge-cdn-cache sb-button yellow inline"><?php _e('Purge CDN Cache', 'servebolt-wp'); ?></button>
        <?php endif; ?>
        <button type="button" class="sb-purge-url sb-button yellow inline"><?php _e('Purge a URL', 'servebolt-wp'); ?></button>
    </p>
    <br>
<?php endif; ?>
