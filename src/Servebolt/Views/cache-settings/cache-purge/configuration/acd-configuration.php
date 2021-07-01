<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php $serveboltApi = Servebolt\Optimizer\Api\Servebolt\Servebolt::getInstance(); ?>
<?php $serveboltCachePurge = Servebolt\Optimizer\CachePurge\Drivers\Servebolt::getInstance(); ?>

<tbody class="sb-config-field-general sb-config-field-acd <?php if (!$cachePurgeIsActive || $settings['cache_purge_driver'] !== 'acd') echo 'sb-config-field-hidden'; ?>">
    <tr>
        <th scope="row" colspan="100%" style="padding-bottom: 5px;">
            <h3 style="margin-bottom: 0;"><?php _e('Servebolt API credentials', 'servebolt-wp'); ?></h3>
            <p style="font-weight: normal;"><?php echo sprintf(__('The information below is read from %sthe environment file in the Servebolt hosting environment%s.', 'servebolt-wp'), '<a href="https://servebo.lt/axeme" target="_blank">', '</a>'); ?></p>
            <?php if (!$serveboltCachePurge->configurationOk()) : ?>
                <p style="font-weight: normal;color: red;"><?php _e('We could not seem to load the configuration from the environment file and therefore the cache purge feature is disabled.', 'servebolt-wp'); ?></p>
            <?php endif; ?>
        </th>
    </tr>

    <tr>
        <th scope="row">
            <label for="sb_api_key"><?php _e('Environment API key', 'servebolt-wp'); ?></label><br>
        </th>
        <td>

            <div class="sb-pwd">
                <input type="password" id="sb_api_key" value="<?php echo esc_attr($serveboltApi->getApiToken()); ?>" class="regular-text validate-field" readonly>
                <button type="button" class="button button-secondary wp-hide-pw sb-hide-pwd hide-if-no-js" data-toggle="0" aria-label="Show password">
                    <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                </button>
            </div>

            <p><em><?php echo sprintf(__('For communication with the %sServebolt API%s.', 'servebolt-wp'), '<a href="https://servebo.lt/bd5c4" target="_blank">', '</a>'); ?></em></p>

            <p class="invalid-message"></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="sb_environment_id"><?php _e('Environment ID', 'servebolt-wp'); ?></label></th>
        <td>
            <input type="text" id="sb_environment_id" value="<?php echo esc_attr($serveboltApi->getEnvironmentId()); ?>" class="regular-text validate-field validation-input-email" readonly>
            <p class="invalid-message"></p>
        </td>
    </tr>

</tbody>
