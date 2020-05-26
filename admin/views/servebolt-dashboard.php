<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap sb-content">
	<div class="sb-logo"></div>
	<h1 class="heading"><?php sb_e('Performance tools'); ?></h1>

    <div class="actions">

	    <?php if ( ! is_multisite() || is_network_admin() ) : ?>

        <?php $method = is_multisite() && is_network_admin() ? 'network_admin_url' : 'admin_url'; ?>

        <a href="<?php echo $method('admin.php?page=servebolt-performance-tools'); ?>" class="sb-button yellow"><?php sb_e('Performance Optimizer'); ?></a>
        <a href="<?php echo $method('admin.php?page=servebolt-cf'); ?>" class="sb-button yellow"><?php sb_e('Cloudflare Cache'); ?></a>

	      <?php if ( sb_feature_active('cf_image_resize') ) : ?>
        <a href="<?php echo $method('admin.php?page=servebolt-cf-image-resizing'); ?>" class="sb-button yellow"><span style="position: relative;"><?php sb_e('Cloudflare Image Resizing'); ?> <span style="position: absolute;top: -8px;right: -30px;font-size: 10px;text-transform: uppercase;">Beta</span></span></a>
        <?php endif; ?>

	      <?php if ( host_is_servebolt() ) : ?>
          <a href="<?php echo $method('admin.php?page=servebolt-nginx-cache'); ?>" class="sb-button yellow"><?php sb_e('Full Page Cache') ?></a>
          <a href="<?php echo $method('admin.php?page=servebolt-logs'); ?>" class="sb-button yellow"><?php sb_e('Review the error log') ?></a>
	      <?php endif; ?>

	    <?php else : ?>

        <a href="<?php echo admin_url('admin.php?page=servebolt-cf'); ?>" class="sb-button yellow"><?php sb_e('Cloudflare Cache'); ?></a>

        <?php if ( sb_feature_active('cf_image_resize') ) : ?>
        <a href="<?php echo admin_url('admin.php?page=servebolt-cf-image-resizing'); ?>" class="sb-button yellow"><span style="position: relative;"><?php sb_e('Cloudflare Image Resizing'); ?> <span style="position: absolute;top: -8px;right: -30px;font-size: 10px;text-transform: uppercase;">Beta</span></span></a>
        <?php endif; ?>

	      <?php if ( host_is_servebolt() ) : ?>
          <a href="<?php echo admin_url('admin.php?page=servebolt-nginx-cache'); ?>" class="sb-button yellow"><?php sb_e('Full Page Cache settings') ?></a>
	      <?php endif; ?>

        <a href="<?php echo network_admin_url('admin.php?page=servebolt-wp'); ?>" class="sb-button yellow"><?php sb_e('Go to network admin for more options') ?></a>

	    <?php endif; ?>
    </div>

	  <?php if ( ! host_is_servebolt() ) : ?>
    <div class="promo-box">
      <h2 class="center"><?php sb_e('Need more speed?') ?></h2>
      <div class="wrap">
        <div class="move-content">
          <p class="center"><?php sb_e('Servebolt is a high performance hosting provider, optimized for WordPress.') ?></p>
          <p class="center"><?php sb_e('Our engineers are ready to set up a free, never ending, trial of our hosting service. They will even help you move in, for free. Or you can set everything up by yourself, it\'s easy!') ?></p>
        </div>
        <div class="buttons">
          <a href="https://servebolt.com" target="_blank" class="sb-button light"><?php sb_e('See what we offer'); ?></a>
          <a href="https://admin.servebolt.com/account/register" target="_blank" class="sb-button yellow"><?php sb_e('Sign up'); ?></a>
        </div>
      </div>
    </div>
    <?php endif; ?>

</div>
