<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>

<?php settings_errors(); ?>

<?php /*
<?php if ( $numberOfCachePurgeQueueItems > $maxNumberOfCachePurgeQueueItems ) : ?>
    <div class="notice notice-warning">
        <p><?php echo sprintf(__('Note: It seems like there is over %s items in the %scache purge queue list%s. This indicates that there might be something wrong with the cron-setup since the cache does not get purged automatically. Please make sure you have set everything up correctly and/or contact support.', 'servebolt-wp'), $max_number_of_cache_purge_queue_items, '<a href="#purge-items-table">', '</a>'); ?></p>
    </div>
<?php endif; ?>
 */ ?>

<p><?php _e('This feature will automatically purge the cache whenever you do an update in WordPress. Neat right?', 'servebolt-wp'); ?></p>

    <?php view('cache-purge.configuration.cache-purge-triggers'); ?>

    <h1><?php _e('Configuration', 'servebolt-wp'); ?></h1>
    <!--<p><?php _e('This feature can be set up using WP CLI or with the form below.', 'servebolt-wp'); ?></p>-->
    <!--<p><?php echo sprintf(__('Run %swp servebolt cache-purge --help%s to see available commands.', 'servebolt-wp'), '<code>', '</code>'); ?></p>-->

    <style type="text/css">
        .sb-config-field-hidden {
            display: none;
        }
    </style>

    <form method="post" autocomplete="off" action="options.php" id="sb-configuration-form">
        <?php settings_fields('sb-cache-purge-options-page') ?>
        <?php do_settings_sections('sb-cache-purge-options-page') ?>

        <table class="form-table" id="sb-configuration-table" role="presentation">

            <tr>
                <th scope="row"><?php _e('Cache purge-feature', 'servebolt-wp'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Cache purge-feature active?', 'servebolt-wp'); ?></span></legend>
                        <label for="cache_purge_switch">
                            <input name="<?php echo getOptionName('cache_purge_switch'); ?>" type="checkbox" id="cache_purge_switch" value="1" <?php checked($cachePurgeIsActive); ?>>
                            <?php _e('Active?', 'servebolt-wp'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>

            <tr class="sb-config-field-general <?php if (!$cachePurgeIsActive) echo ' sb-config-field-hidden'; ?>">
                <th scope="row"><?php _e('Automatic purge on content update?', 'servebolt-wp'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Automatic cache purge on content update', 'servebolt-wp'); ?></span></legend>
                        <label for="cache_purge_auto">
                            <input name="<?php echo getOptionName('cache_purge_auto'); ?>" type="checkbox" id="cache_purge_auto" value="1" <?php checked($autoCachePurgeIsActive); ?>>
                            <?php _e('Active?', 'servebolt-wp'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>

            <tr class="sb-config-field-general <?php if (!$cachePurgeIsActive) echo ' sb-config-field-hidden'; ?>">
                <th scope="row"><?php _e('Cache purge provider', 'servebolt-wp'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Cache purge provider', 'servebolt-wp'); ?></span></legend>
                        <label>
                            <input type="radio" name="<?php echo getOptionName('cache_purge_driver'); ?>" value="cloudflare" <?php checked($settings['cache_purge_driver'] == 'cloudflare'); ?>> <code><?php _e('Cloudflare', 'servebolt-wp'); ?></code>
                        </label>
                        <br>
                        <label<?php if (!$isHostedAtServebolt) echo ' style="opacity: 0.5;pointer-events:none;"'; ?>>
                            <input type="radio"<?php if (!$isHostedAtServebolt) echo ' readonly'; ?> name="<?php echo getOptionName('cache_purge_driver'); ?>" value="acd" <?php checked($settings['cache_purge_driver'] == 'acd'); ?>> <code><?php _e('Accelerated Domains', 'servebolt-wp'); ?></code>
                            <em>For Servebolt-customers only</em>
                        </label>
                    </fieldset>
                </td>
            </tr>

            <?php view('cache-purge.configuration.acd-configuration', $arguments); ?>

            <?php view('cache-purge.configuration.cloudflare-configuration', $arguments); ?>

            <?php view('cache-purge.configuration.cron-configuration', $arguments); ?>

            <?php //view('cache-purge.queue.list', $arguments); ?>


            <!--</tbody>-->
        </table>

        <p class="submit">
            <?php submit_button(null, 'primary', 'form-submit', false); ?>
            <span class="spinner form-submit-spinner"></span>
        </p>

    </form>

    <?php if ( apply_filters('sb_optimizer_cf_cache_form_validation_active', true) ) : ?>
        <script>
            document.getElementById('sb-configuration-form').addEventListener('submit', function(event) {
                window.sb_validate_cf_configuration_form(event);
            });
        </script>
    <?php endif; ?>
