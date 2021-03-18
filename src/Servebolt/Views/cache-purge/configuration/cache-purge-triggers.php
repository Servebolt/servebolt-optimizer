<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php use Servebolt\Optimizer\CachePurge\CachePurge; ?>

<?php if (CachePurge::featureIsAvailable()) : ?>
    <p>
        <button class="sb-purge-all-cache sb-button yellow inline"><?php sb_e('Purge all cache'); ?></button>
        <button class="sb-purge-url sb-button yellow inline"><?php sb_e('Purge a URL'); ?></button>
    </p>
    <br>
<?php endif; ?>
