<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap sb-content">
	<div class="sb-logo"></div>
	<h1><?php sb_e('Performance tools'); ?></h1>

    <div class="boxes">

      <a href="admin.php?page=servebolt-performance-tools" class="sb-box">
        <div class="inner">
          <p class="function"><?php sb_e('Optimize your database'); ?></p>
        </div>
      </a>

	    <?php if ( host_is_servebolt() === true ) : ?>
        <a href="admin.php?page=servebolt-nginx-cache" class="sb-box">
          <div class="inner">
            <p class="function"><?php sb_e('Full Page Cache settings') ?></p>
          </div>
        </a>

        <a href="admin.php?page=servebolt-logs" class="sb-box">
          <div class="inner">
            <p class="function"><?php sb_e('Review the error log') ?></p>
          </div>
        </a>
	    <?php endif; ?>

    </div>

	  <?php if ( host_is_servebolt() !== true ) : ?>

    <div class="sb-box-wide">
      <h2 class="center"><?php sb_e('Need more speed?') ?></h2>
      <div class="wrap">
        <div class="move-content">
          <p class="center"><?php sb_e('Servebolt is a high performance hosting provider, optimized for WordPress.') ?></p>
          <p class="center"><?php sb_e('Our engineers are ready to set up a free, never ending, trial of our hosting service. They will even help you move in, for free. Or you can set everything up by yourself, it\'s easy!') ?></p>
        </div>
        <div class="buttons">
          <a href="https://servebolt.com" class="sb-button light"><?php sb_e('See what we offer'); ?></a>
          <a href="https://admin.servebolt.com/account/register" class="sb-button yellow"><?php sb_e('Sign up'); ?></a>
        </div>
      </div>
    </div>

    <?php endif; ?>

</div>
