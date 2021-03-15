<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php $cachePurge = Servebolt\Optimizer\CachePurge\CachePurge::getInstance(); ?>

<?php if ( $cachePurge->cachePurgeIsActive() ) : ?>
    <?php if ( ! $cachePurge->featureIsConfigured() ) : ?>
        <p><?php sb_e('Make sure you have added the API credentials and selected a zone to use this functionality.'); ?></p>
    <?php else: ?>
        <p>
            <button class="sb-purge-all-cache sb-button yellow inline"><?php sb_e('Purge all cache'); ?></button>
            <button class="sb-purge-url sb-button yellow inline"><?php sb_e('Purge a URL'); ?></button>
        </p>
        <br>
    <?php endif; ?>
<?php endif; ?>
