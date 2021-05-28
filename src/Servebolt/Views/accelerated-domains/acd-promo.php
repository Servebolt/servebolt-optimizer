<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\getServeboltAdminUrl; ?>
<div class="welcome-panel" id="acd-welcome-panel">
    <div class="welcome-panel-content">
        <div class="welcome-panel-column-container">
            <div>
                <img style="max-width: 250px; width: 50%;" src="<?= SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/images/acd-logo-color.svg'; ?>">
            </div>

            <div class="welcome-panel-column">
                <h3><?php _e('Get started', 'servebolt-wp'); ?></h3>
                <a class="button button-primary button-hero" href="<?php echo getServeboltAdminUrl('accelerated-domains'); ?>">
                    <?= _e('Get started', 'servebolt-wp'); ?>
                </a>
                <p>or <a href="<?php echo getServeboltAdminUrl('accelerated-domains'); ?>">
                        <?php _e('get in touch through the chat'); ?>
                    </a>
            </div>
        </div>

        <div class="welcome-panel-column" style="padding: .5rem">
            <h3><?php _e('What is Accelerated Domains?', 'servebolt-wp'); ?></h3>
            <p>
                <?php
                echo sprintf(__('Accelerated Domains is a unique performance enhancing service that enables your site to accomplish extraordinary speed, security, and scalability upgrades. It does so in the most efficient, affordable, and sustainable way. Accelerated Domains is a service that requires no configuration, and is continuously evolving.
                        Every Accelerated Domains feature is built to help you unlock new growth, more revenue, and greater reach. %sLearn more about Accelerated Domains here.%s'), '<a href="https://servebo.lt/luiv5">', '</a>');
                ?>
            </p>
        </div>
        <div class="welcome-panel-column welcome-panel-last" style="padding: .5rem">
            <h3><?php _e('What does this plugin do?', 'servebolt-wp'); ?></h3>
            <p>
                <?php
                _e('Accelerated Domains is a service that does its magic between the visitor and the origin server. This plugin simply instructs the Cache Engine and the Security Engine about site specific rules such as which pages to never cache. Without the Servebolt Optimizer plugin, Accelerated Domains is not properly set up and will not get these site specific instructions.');
                ?>
            </p>
        </div>
    </div>
</div>
