<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once 'admin-interface.php';


?>
<div class="wrap sb-content">
	<div class="sb-logo"></div>
	<h1><?php _e('Performance tools', 'servebolt-wp') ?></h1>
    <div class="boxes left">
        <a href="admin.php?page=servebolt-performance-tools" class="sb-box sb-optimize-db">
            <div class="inner">
                <div class="icon"></div>
                <p class="function"><?php _e('Optimize your database', 'servebolt-wp') ?></p>
            </div>
        </a>
        <?php if(host_is_servebolt() === true): ?>
        <a href="admin.php?page=servebolt-nginx-cache" class="sb-box sb-cache">
            <div class="inner">
                <div class="icon"></div>
                <p class="function"><?php _e('Full Page Cache settings', 'servebolt-wp') ?></p>
            </div>
        </a>
        <a href="admin.php?page=servebolt-logs" class="sb-box sb-errors">
            <div class="inner">
                <div class="icon"></div>
                <p class="function"><?php _e('Review the error log', 'servebolt-wp') ?></p>
            </div>
        </a>
        <?php endif; ?>
        <a href="admin.php?page=servebolt-wpvuldb" class="sb-box sb-wpvulndb">
            <div class="inner">
                <div class="icon"></div>
                <p class="function"><?php _e('Check for security issues', 'servebolt-wp') ?></p>
            </div>
        </a>
    </div>
    <?php if(host_is_servebolt() !== true): ?>
    <div class="boxes right">
        <div class="sb-box-wide sb-move">
            <div class="icon"></div>
            <h2 class="center"><?php _e('Need more speed?') ?></h2>
            <div class="wrap">
                <div class="move-content">
                    <p class="center"><?php _e('Servebolt is a high performance hosting provider, optimized for WordPress.', 'servebolt-wp') ?></p>
                    <p class="center"><?php _e('Our engineers are ready to set up a free, never ending, trial of our hosting service. They will even help you move in, for free. Or you can set everything up by yourself, it\'s easy!', 'servebolt-wp') ?></p>
                </div>
                <div class="buttons">
                    <a href="https://servebolt.com" class="sb-button light">
                        <?php _e('See what we offer') ?>
                    </a>
                    <a href="https://admin.servebolt.com/account/register" class="sb-button yellow">
                        <?php _e('Sign up') ?>
                    </a>
                </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
