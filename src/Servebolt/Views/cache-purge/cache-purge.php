<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php use Servebolt\Optimizer\Helpers as Helpers; ?>
<div class="wrap sb-content" id="sb-configuration">

    <h1><?php sb_e('Cloudflare Cache'); ?></h1>

    <?php settings_errors(); ?>

    <?php Helpers\view('cache-purge.configuration.configuration', $arguments); ?>
    <?php Helpers\view('cache-purge.queue-list', $arguments); ?>
</div>
