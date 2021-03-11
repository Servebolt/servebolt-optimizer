<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php use Servebolt\Optimizer\Helpers as Helpers; ?>

<div class="wrap sb-content" id="sb-configuration">

    <h1><?php sb_e('Cache purging'); ?></h1>

    <?php if ( is_network_admin() ) : ?>

        <?php Helpers\view('cache-purge.network-admin.network-admin', $arguments); ?>

    <?php else : ?>

        <?php Helpers\view('cache-purge.configuration.configuration', $arguments); ?>

    <?php endif; ?>

</div>
