<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use Servebolt\Optimizer\CachePurge\CachePurge; ?>

<?php if (CachePurge::featureIsAvailable()) : ?>
    <p>
        <button type="button" class="sb-purge-all-cache sb-button yellow inline"><?php _e('Purge CDN Cache', 'servebolt-wp'); ?></button>
        <?php if (CachePurge::cachePurgeByUrlIsAvailable()) : ?>
            <button type="button" class="sb-purge-url sb-button yellow inline<?php echo CachePurge::driverSupportsUrlCachePurge() ? '' : ' sb-button-hidden'; ?>">
                <?php _e('Purge a URL', 'servebolt-wp'); ?>
            </button>
        <?php endif; ?>
        <?php if (CachePurge::cachePurgeByServerAvailable() ) : ?>
            <button type="button" class="sb-purge-server-cache sb-button yellow inline<?php echo CachePurge::driverSupportsCachePurgeServer() ? '' : ' sb-button-hidden'; ?>">
                <?php _e('Purge All Caches', 'servebolt-wp'); ?>
            </button>
        <?php endif; ?>
    </p>
    <br>
<?php endif; ?>
