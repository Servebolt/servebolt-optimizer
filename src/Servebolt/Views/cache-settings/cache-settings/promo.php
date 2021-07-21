<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\getServeboltAdminUrl; ?>
<?php $sbAdminUrl = getServeboltAdminUrl(); ?>
<?php $sbAdminButton = $sbAdminUrl ? sprintf('<a href="%s" target="_blank">%s</a>', $sbAdminUrl, __('Servebolt Control Panel dashboard', 'servebolt-wp')) : __('Servebolt Control Panel dashboard', 'servebolt-wp'); ?>


<div class="welcome-panel" id="acd-welcome-panel">
    <div class="welcome-panel-content">
        <div class="welcome-panel-column-container">
            <div>
                <p><?php _e('These cache settings let you control the cache headers set by the plugin. The cache headers are used to control how caching works in Accelerated Domains, Cloudflare and the Servebolt Cloud.', 'servebolt-wp'); ?></p>
            </div>
        </div>

        <div class="welcome-panel-column" style="padding: 0 1rem 1.5rem 0;">
            <h3><?php _e('Servebolt Cloud HTML Cache', 'servebolt-wp'); ?></h3>
            <p><?php _e('Servebolt Cloud HTML Cache (formerly Full Page Cache) is easy to set up, but should always be tested before activating it on production environments.', 'servebolt-wp'); ?></p>
			<p><?php printf( esc_html__('To activate HTML Cache to go %s and set "Caching" to "Static Files + Full-Page Cache".', 'servebolt-wp'), $sbAdminButton ) ?></p>
            <?php if ( $sbAdminUrl ) : ?>
				<p><a href="<?php echo $sbAdminUrl; ?>" target="_blank" class="button"><?php _e('Servebolt Control Panel dashboard', 'servebolt-wp') ?></a></p>
			<?php endif; ?>
        </div>
        <div class="welcome-panel-column" style="padding: 0 1rem 1.5rem 0;">
            <h3><?php _e('Accelerated Domains', 'servebolt-wp'); ?></h3>
            <p>
                <?php
                _e('Accelerated Domains has a optimized cache engine and it respect these settings. Disabling cache for a post type in these settings makes Accelerated Domains bypass it from cache.', 'servebolt-wp');
                ?>
            </p>
            <p>
                <?php
                echo sprintf(__('Not running Accelerated Domains? %sOrder Accelerated Domains in the control panel%s and Servebolt Support will add it to your domain and set it up for you.', 'servebolt-wp'), '<a href="' . getServeboltAdminUrl('accelerated-domains') . '">', '</a>' );
                ?>
            </p>
        </div>
        <div class="welcome-panel-column" style="padding: 0 1rem 1.5rem 0;">
            <h3><?php _e('Cloudflare', 'servebolt-wp'); ?></h3>
            <p>
                <?php
                _e('When Cloudflare is set up to respect origin cache headers, changing these settings also adjust what post types Cloudflare cache and not. After changing these settings the Cloudflare cache should be purged.', 'servebolt-wp');
                ?>
            </p>
            <p>
                <?php
                echo sprintf(__('%sGet in touch with Servebolt Support%s to get Cloudflare added to your domain and set up correctly.', 'servebolt-wp'), '<a href="' . $sbAdminUrl . '">', '</a>' );
                ?>
            </p>
        </div>
    </div>
</div>
