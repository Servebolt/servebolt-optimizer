<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>

<?php settings_errors(); ?>

<?php /*
<?php if ( $numberOfCachePurgeQueueItems > $maxNumberOfCachePurgeQueueItems ) : ?>
    <div class="notice notice-warning">
        <p><?php echo sprintf(sb__('Note: It seems like there is over %s items in the %scache purge queue list%s. This indicates that there might be something wrong with the cron-setup since the cache does not get purged automatically. Please make sure you have set everything up correctly and/or contact support.'), $max_number_of_cache_purge_queue_items, '<a href="#purge-items-table">', '</a>'); ?></p>
    </div>
<?php endif; ?>
 */ ?>

<p><?php sb_e('This feature will automatically purge the cache whenever you do an update in WordPress. Neat right?'); ?></p>

    <?php view('cache-purge.configuration.cache-purge-triggers'); ?>

    <h1><?php sb_e('Configuration'); ?></h1>
    <!--<p><?php sb_e('This feature can be set up using WP CLI or with the form below.'); ?></p>-->
    <!--<p><?php echo sprintf(sb__('Run %swp servebolt cache-purge --help%s to see available commands.'), '<code>', '</code>'); ?></p>-->

    <style type="text/css">
        .sb-config-field-hidden {
            display: none;
        }
    </style>

    <form method="post" autocomplete="off" action="options.php" id="sb-configuration-form">
        <?php settings_fields( 'sb-cf-options-page' ) ?>
        <?php do_settings_sections( 'sb-cf-options-page' ) ?>

        <table class="form-table" id="sb-configuration-table" role="presentation">

            <tr>
                <th scope="row"><?php sb_e('Cache purge-feature'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php sb_e('Cache purge-feature active?'); ?></span></legend>
                        <label for="cache_purge_switch">
                            <input name="<?php echo sb_get_option_name('cache_purge_switch'); ?>" type="checkbox" id="cache_purge_switch" value="1" <?php checked($settings['cache_purge_switch']); ?>>
                            <?php sb_e('Active?'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>

            <tr class="sb-config-field-general <?php if ( ! $settings['cache_purge_switch'] ) echo ' sb-config-field-hidden'; ?>">
                <th scope="row"><?php sb_e('Cache purge driver'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php sb_e('Cache purge driver'); ?></span></legend>
                        <label>
                            <input type="radio" name="<?php echo sb_get_option_name('cache_purge_driver'); ?>" value="cloudflare" <?php checked($settings['cache_purge_driver'] == 'cloudflare'); ?>> <code><?php sb_e('Cloudflare'); ?></code>
                        </label>
                        <br>
                        <label<?php if (!$isHostedAtServebolt) echo ' style="opacity: 0.5;pointer-events:none;"'; ?>>
                            <input type="radio"<?php if (!$isHostedAtServebolt) echo ' readonly'; ?> name="<?php echo sb_get_option_name('cache_purge_driver'); ?>" value="acd" <?php checked($settings['cache_purge_driver'] == 'acd'); ?>> <code><?php sb_e('Accelerated Domains'); ?></code>
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
