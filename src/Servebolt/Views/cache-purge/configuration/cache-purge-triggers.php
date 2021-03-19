<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php use Servebolt\Optimizer\CachePurge\CachePurge; ?>

<?php if (CachePurge::featureIsAvailable()) : ?>
    <p>
        <button class="sb-purge-all-cache sb-button yellow inline"><?php _e('Purge all cache', 'servebolt-wp'); ?></button>
        <button class="sb-purge-url sb-button yellow inline"><?php _e('Purge a URL', 'servebolt-wp'); ?></button>
    </p>
    <br>
<?php endif; ?>
