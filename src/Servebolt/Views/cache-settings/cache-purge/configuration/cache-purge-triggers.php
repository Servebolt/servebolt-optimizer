<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use Servebolt\Optimizer\CachePurge\CachePurge; ?>

<?php if (CachePurge::featureIsAvailable()) : ?>
    <p>
        <button type="button" class="sb-purge-all-cache sb-button yellow inline"><?php _e('Purge All Cache', 'servebolt-wp'); ?></button>
        <button type="button" class="sb-purge-url sb-button yellow inline<?php echo CachePurge::driverSupportsUrlCachePurge() ? '' : ' sb-button-hidden'; ?>"><?php _e('Purge a URL', 'servebolt-wp'); ?></button>
        <button type="button" class="sb-purge-url sb-button yellow inline<?php echo CachePurge::driverSupportsUrlCachePrefixPurge() ? '' : ' sb-button-hidden'; ?>"><?php _e('Purge Images', 'servebolt-wp'); ?></button>
    </p>
    <br>
<?php endif; ?>
