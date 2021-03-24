<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>
<?php $cfApi = Servebolt\Optimizer\Api\Cloudflare\Cloudflare::getInstance(); ?>

<tbody class="sb-config-field-general sb-config-field-cloudflare <?php if (!$cachePurgeIsActive|| $settings['cache_purge_driver'] !== 'cloudflare') echo 'sb-config-field-hidden'; ?>">
    <tr>
        <th scope="row" colspan="100%" style="padding-bottom: 5px;">
            <h3 style="margin-bottom: 0;"><?php _e('Cloudflare API', 'servebolt-wp'); ?></h3>
            <p style="font-weight: normal;"><?php _e('Servebolt Optimizer connects to the Cloudflare cache through the Cloudflare API. Best practice is to use API token for authentication.', 'servebolt-wp');?></p>
        </th>
    </tr>
    <tr>
        <th scope="row"><?php _e('Authentication type', 'servebolt-wp'); ?></th>
        <td>
            <fieldset>
                <legend class="screen-reader-text"><span><?php _e('Authentication type', 'servebolt-wp'); ?></span></legend>
                <label>
                    <input type="radio" name="<?php echo getOptionName('cf_auth_type'); ?>" value="api_token" <?php checked($settings['cf_auth_type'] == 'api_token'); ?>> <code><?php _e('API token', 'servebolt-wp'); ?></code>
                    <a href="https://servebo.lt/upuls"><?php _e('How to make an API token', 'servebolt-wp'); ?></a>
                </label><br>
                <label>
                    <input type="radio" name="<?php echo getOptionName('cf_auth_type'); ?>" value="api_key" <?php checked($settings['cf_auth_type'] == 'api_key'); ?>> <code><?php _e('API key', 'servebolt-wp'); ?></code>
                    <a href="https://servebo.lt/6f0rm"><?php _e('How to get your API key', 'servebolt-wp'); ?></a>
                </label>
            </fieldset>
        </td>
    </tr>
    <tr class="feature_cf_auth_type-api_token"<?php if ( $settings['cf_auth_type'] != 'api_token' ) echo ' style="display: none;"' ?>>
        <th scope="row"><label for="sb_cf_api_token"><?php _e('API token', 'servebolt-wp'); ?></label></th>
        <td>

            <div class="sb-pwd">
                <input name="<?php echo getOptionName('cf_api_token'); ?>" type="password" id="sb_cf_api_token" autocomplete="off" data-original-value="<?php echo esc_attr($settings['cf_api_token']); ?>" value="<?php echo esc_attr($settings['cf_api_token']); ?>" class="regular-text validate-field validation-group-api_token validation-group-api_credentials">
                <button type="button" class="button button-secondary wp-hide-pw sb-hide-pwd hide-if-no-js" data-toggle="0" aria-label="Show password">
                    <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                </button>
            </div>

            <p class="invalid-message"></p>
            <p><small><?php echo sprintf(__('Make sure to add permissions for %s when creating a token.', 'servebolt-wp'), $cfApi->apiPermissionsNeeded()); ?></small></p>
        </td>
    </tr>
    <tr class="feature_cf_auth_type-api_key"<?php if ( $settings['cf_auth_type'] != 'api_key' ) echo ' style="display: none;"' ?>>
        <th scope="row"><label for="sb_cf_email"><?php _e('Cloudflare e-mail', 'servebolt-wp'); ?></label></th>
        <td>
            <input name="<?php echo getOptionName('cf_email'); ?>" type="text" id="sb_cf_email" autocomplete="off" data-original-value="<?php echo esc_attr($settings['cf_email']); ?>" value="<?php echo esc_attr($settings['cf_email']); ?>" class="regular-text validate-field validation-input-email validation-group-api_key_credentials validation-group-api_credentials">
            <p class="invalid-message"></p>
        </td>
    </tr>
    <tr class="feature_cf_auth_type-api_key"<?php if ( $settings['cf_auth_type'] != 'api_key' ) echo ' style="display: none;"' ?>>
        <th scope="row"><label for="sb_cf_api_key"><?php _e('API key', 'servebolt-wp'); ?></label></th>
        <td>
            <div class="sb-pwd">
                <input name="<?php echo getOptionName('cf_api_key'); ?>" type="password" id="sb_cf_api_key" autocomplete="off" data-original-value="<?php echo esc_attr($settings['cf_api_key']); ?>" value="<?php echo esc_attr($settings['cf_api_key']); ?>" class="regular-text validate-field validation-input-api_key validation-group-api_key_credentials validation-group-api_credentials">
                <button type="button" class="button button-secondary wp-hide-pw sb-hide-pwd hide-if-no-js" data-toggle="0" aria-label="Show password">
                    <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                </button>
            </div>
            <p class="invalid-message"></p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="zone_id"><?php _e('Cloudflare Zone ID', 'servebolt-wp'); ?></label></th>
        <td>

            <?php $haveZones = (is_array($cfZones) && !empty($cfZones)); ?>
            <input name="<?php echo getOptionName('cf_zone_id'); ?>" type="text" id="zone_id" placeholder="Type zone ID<?php if ($haveZones) echo ' or use the choices below'; ?>" value="<?php echo esc_attr($settings['cf_zone_id']); ?>" class="regular-text validate-field validation-group-zone_id">
            <p style="font-weight: normal;"><?php _e('You can find the Zone ID in the Cloudflare Overview tab of the zone you\'d like to connect, in the right sidebar under the API section.', 'servebolt-wp'); ?></p>
            <span class="spinner zone-loading-spinner"></span>
            <p class="invalid-message"></p>

            <p class="active-zone"<?php if ( ! isset($selectedCfZone) || ! $selectedCfZone ) echo ' style="display: none;"'; ?>><?php _e('Selected zone:', 'servebolt-wp'); ?> <span><?php if ( isset($selectedCfZone) && $selectedCfZone ) echo $selectedCfZone->name; ?></span></p>

            <div class="zone-selector-container"<?php if ( ! $haveZones ) echo ' style="display: none;"'; ?>>
                <p style="margin-top: 10px;"><?php _e('Available zones:', 'servebolt-wp'); ?></p>
                <ul class="zone-selector" style="margin: 5px 0;">
                    <?php if ($haveZones) : ?>
                        <?php foreach($cfZones as $cfZone) : ?>
                            <li><a href="#" data-name="<?php echo esc_attr($cfZone->name); ?>" data-id="<?php echo esc_attr($cfZone->id); ?>"><?php echo $cfZone->name; ?> (<?php echo $cfZone->id; ?>)</a></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

        </td>
    </tr>

</tbody>
