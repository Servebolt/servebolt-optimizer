<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php $cfApi = Servebolt\Optimizer\Api\Cloudflare\Cloudflare::getInstance(); ?>

<tbody class="sb-config-field-general sb-config-field-cloudflare <?php if ( ! $settings['cache_purge_switch'] || $settings['cache_purge_driver'] !== 'cloudflare' ) echo 'sb-config-field-hidden'; ?>">
    <tr>
        <th scope="row" colspan="100%" style="padding-bottom: 5px;">
            <h3 style="margin-bottom: 0;"><?php sb_e('API credentials'); ?></h3>
            <p style="font-weight: normal;"><?php echo sprintf(sb__('We\'ll be using the Cloudflare API to connect your site to your Cloudflare account. We recommend using an API token as this will allow for more granular access control. You can learn more about how to set this up in %sour documentation%s.'), '<a href="https://servebo.lt/xjmkq" target="_blank">', '</a>'); ?></p>
        </th>
    </tr>
    <tr>
        <th scope="row"><?php sb_e('Authentication type'); ?></th>
        <td>
            <fieldset>
                <legend class="screen-reader-text"><span><?php sb_e('Authentication type'); ?></span></legend>
                <label><input type="radio" name="<?php echo sb_get_option_name('cf_auth_type'); ?>" value="api_token" <?php checked($settings['cf_auth_type'] == 'api_token'); ?>> <code><?php sb_e('API token'); ?></code></label><br>
                <label><input type="radio" name="<?php echo sb_get_option_name('cf_auth_type'); ?>" value="api_key" <?php checked($settings['cf_auth_type'] == 'api_key'); ?>> <code><?php sb_e('API key'); ?></code></label>
            </fieldset>
        </td>
    </tr>
    <tr class="feature_cf_auth_type-api_token"<?php if ( $settings['cf_auth_type'] != 'api_token' ) echo ' style="display: none;"' ?>>
        <th scope="row"><label for="sb_cf_api_token"><?php sb_e('API token'); ?></label></th>
        <td>

            <div class="sb-pwd">
                <input name="<?php echo sb_get_option_name('cf_api_token'); ?>" type="password" id="sb_cf_api_token" autocomplete="off" data-original-value="<?php echo esc_attr($settings['cf_api_token']); ?>" value="<?php echo esc_attr($settings['cf_api_token']); ?>" class="regular-text validate-field validation-group-api_token validation-group-api_credentials">
                <button type="button" class="button button-secondary wp-hide-pw sb-hide-pwd hide-if-no-js" data-toggle="0" aria-label="Show password">
                    <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                </button>
            </div>

            <p class="invalid-message"></p>
            <p><small><?php echo sprintf(sb__('Make sure to add permissions for %s when creating a token.'), $cfApi->apiPermissionsNeeded()); ?></small></p>
        </td>
    </tr>
    <tr class="feature_cf_auth_type-api_key"<?php if ( $settings['cf_auth_type'] != 'api_key' ) echo ' style="display: none;"' ?>>
        <th scope="row"><label for="sb_cf_email"><?php sb_e('Cloudflare e-mail'); ?></label></th>
        <td>
            <input name="<?php echo sb_get_option_name('cf_email'); ?>" type="text" id="sb_cf_email" autocomplete="off" data-original-value="<?php echo esc_attr($settings['cf_email']); ?>" value="<?php echo esc_attr($settings['cf_email']); ?>" class="regular-text validate-field validation-input-email validation-group-api_key_credentials validation-group-api_credentials">
            <p class="invalid-message"></p>
        </td>
    </tr>
    <tr class="feature_cf_auth_type-api_key"<?php if ( $settings['cf_auth_type'] != 'api_key' ) echo ' style="display: none;"' ?>>
        <th scope="row"><label for="sb_cf_api_key"><?php sb_e('API key'); ?></label></th>
        <td>
            <div class="sb-pwd">
                <input name="<?php echo sb_get_option_name('cf_api_key'); ?>" type="password" id="sb_cf_api_key" autocomplete="off" data-original-value="<?php echo esc_attr($settings['cf_api_key']); ?>" value="<?php echo esc_attr($settings['cf_api_key']); ?>" class="regular-text validate-field validation-input-api_key validation-group-api_key_credentials validation-group-api_credentials">
                <button type="button" class="button button-secondary wp-hide-pw sb-hide-pwd hide-if-no-js" data-toggle="0" aria-label="Show password">
                    <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                </button>
            </div>
            <p class="invalid-message"></p>
        </td>
    </tr>
    <tr>
        <th scope="row" colspan="100%" style="padding-bottom: 5px;">
            <h3 style="margin-bottom: 0;"><?php sb_e('Cloudflare zone'); ?></h3>
            <p style="font-weight: normal;"><?php sb_e('A domain in Cloudflare is called a zone. We\'ll need the ID of the zone you\'d like to connect here. You can find the Zone ID in the Cloudflare Overview tab of the domain you\'d like to connect, in the right sidebar under the API section.'); ?></p>
        </th>
    </tr>
    <tr>
        <th scope="row"><label for="zone_id"><?php sb_e('Zone ID'); ?></label></th>
        <td>

            <?php
                $zone = $settings['cf_zone_id'] ? $cfApi->getZoneById($settings['cf_zone_id']) : false;
                $have_zones = false;
                $zones = [];//sb_cf_cache()->list_zones();
                $have_zones = ( is_array($zones) && ! empty($zones) );
            ?>

            <input name="<?php echo sb_get_option_name('cf_zone_id'); ?>" type="text" id="zone_id" placeholder="Type zone ID<?php if ( $have_zones ) echo ' or use the choices below'; ?>" value="<?php echo esc_attr($settings['cf_zone_id']); ?>" class="regular-text validate-field validation-group-zone_id">
            <span class="spinner zone-loading-spinner"></span>
            <p class="invalid-message"></p>

            <p class="active-zone"<?php if ( ! isset($zone) || ! $zone ) echo ' style="display: none;"'; ?>><?php sb_e('Selected zone:'); ?> <span><?php if ( isset($zone) && $zone ) echo $zone->name; ?></span></p>

            <div class="zone-selector-container"<?php if ( ! $have_zones ) echo ' style="display: none;"'; ?>>
                <p style="margin-top: 10px;"><?php sb_e('Available zones:'); ?></p>
                <ul class="zone-selector" style="margin: 5px 0;">
                    <?php if ( $have_zones ) : ?>
                        <?php foreach($zones as $zone) : ?>
                            <li><a href="#" data-name="<?php echo esc_attr($zone->name); ?>" data-id="<?php echo esc_attr($zone->id); ?>"><?php echo $zone->name; ?> (<?php echo $zone->id; ?>)</a></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

        </td>
    </tr>

</tbody>
