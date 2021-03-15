<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap sb-content" id="sb-configuration">

	<h1><?php sb_e('Cloudflare Cache'); ?></h1>

    <?php settings_errors(); ?>

	<?php $max_number_of_cache_purge_queue_items = sb_cf_cache_admin_controls()->max_number_of_cache_purge_queue_items(); ?>
  <?php $number_of_cache_purge_queue_items = sb_cf_cache()->count_items_to_purge(); ?>
	<?php if ( ! is_network_admin() ) : ?>
		<?php if ( $number_of_cache_purge_queue_items > $max_number_of_cache_purge_queue_items ) : ?>
        <div class="notice notice-warning">
          <p><?php echo sprintf(sb__('Note: It seems like there is over %s items in the %scache purge queue list%s. This indicates that there might be something wrong with the cron-setup since the cache does not get purged automatically. Please make sure you have set everything up correctly and/or contact support.'), $max_number_of_cache_purge_queue_items, '<a href="#purge-items-table">', '</a>'); ?></p>
        </div>
		<?php endif; ?>
	<?php endif; ?>

  <p><?php sb_e('This feature will automatically purge the Cloudflare cache whenever you do an update in WordPress. Neat right?'); ?></p>

	<?php if ( is_network_admin() ) : ?>

    <p><?php sb_e('Please navigate to each blog to control settings regarding Cloudflare cache purging.'); ?></p>

    <table class="wp-list-table widefat striped">
      <thead>
      <tr>
        <th><?php sb_e('Blog ID'); ?></th>
        <th><?php sb_e('URL'); ?></th>
        <th><?php sb_e('Cloudflare Cache Purge Active'); ?></th>
        <th><?php sb_e('Controls'); ?></th>
      </tr>
      </thead>
      <tfoot>
      <tr>
        <th><?php sb_e('Blog ID'); ?></th>
        <th><?php sb_e('URL'); ?></th>
        <th><?php sb_e('Cloudflare Cache Purge Active'); ?></th>
        <th><?php sb_e('Controls'); ?></th>
      </tr>
      </tfoot>
      <tbody>
	  <?php foreach ( get_sites() as $site ) : ?>
        <tr>
          <td><?php echo $site->blog_id; ?></td>
          <td><?php echo $site->domain . $site->path; ?></td>
          <td><?php echo sb_cf_cache()->cf_is_active($site->blog_id) ? sb__('Yes') : sb__('No'); ?></td>
          <td><a href="<?php echo get_admin_url( $site->blog_id, 'admin.php?page=servebolt-cache-purge-control' ); ?>" class="button btn"><?php sb_e('Go to site Cloudflare settings'); ?></a></td>
        </tr>
	  <?php endforeach; ?>
      </tbody>
    </table>

  <?php else : ?>

    <?php $cf_settings = sb_cf_cache_admin_controls()->get_settings_items(); ?>

    <?php if ( sb_cf_cache()->cf_is_active() ) : ?>
      <?php if ( ! sb_cf_cache()->cf_cache_feature_available() ) : ?>
      <p><?php sb_e('Make sure you have added the API credentials and selected a zone to use this functionality.'); ?></p>
      <?php else: ?>
      <p>
        <button class="sb-purge-all-cache sb-button yellow inline"><?php sb_e('Purge all cache'); ?></button>
        <button class="sb-purge-url sb-button yellow inline"><?php sb_e('Purge a URL'); ?></button>
      </p>
      <br>
      <?php endif; ?>
    <?php endif; ?>

    <h1><?php sb_e('Configuration'); ?></h1>
    <p><?php sb_e('This feature can be set up using WP CLI or with the form below.'); ?></p>
    <p><?php echo sprintf(sb__('Run %swp servebolt cf --help%s to see available commands.'), '<code>', '</code>'); ?></p>

    <form method="post" autocomplete="off" action="options.php" id="sb-configuration-form">
      <?php settings_fields( 'sb-cf-options-page' ) ?>
      <?php do_settings_sections( 'sb-cf-options-page' ) ?>

      <table class="form-table" id="sb-configuration-table" role="presentation">
        <thead>
          <tr>
            <th scope="row"><?php sb_e('Cloudflare cache-feature'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><span><?php sb_e('Cloudflare cache-feature active?'); ?></span></legend>
                <label for="clourdlare_switch">
                  <input name="<?php echo sb_get_option_name('cf_switch'); ?>" type="checkbox" id="cloudflare_switch" value="1" <?php checked($cf_settings['cf_switch']); ?>>
                  <?php sb_e('Active?'); ?>
                </label>
              </fieldset>
            </td>
          </tr>
        </thead>
        <tbody class="sb-toggle-active-cf-item<?php if ( ! $cf_settings['cf_switch'] ) echo ' cf-hidden'; ?>">
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
                <label><input type="radio" name="<?php echo sb_get_option_name('cf_auth_type'); ?>" value="api_token" <?php checked($cf_settings['cf_auth_type'] == 'api_token'); ?>> <code><?php sb_e('API token'); ?></code></label><br>
                <label><input type="radio" name="<?php echo sb_get_option_name('cf_auth_type'); ?>" value="api_key" <?php checked($cf_settings['cf_auth_type'] == 'api_key'); ?>> <code><?php sb_e('API key'); ?></code></label>
              </fieldset>
            </td>
          </tr>
          <tr class="feature_cf_auth_type-api_token"<?php if ( $cf_settings['cf_auth_type'] != 'api_token' ) echo ' style="display: none;"' ?>>
            <th scope="row"><label for="sb_api_token"><?php sb_e('API token'); ?></label></th>
            <td>

              <div class="sb-pwd">
                <input name="<?php echo sb_get_option_name('cf_api_token'); ?>" type="password" id="sb_api_token" autocomplete="off" data-original-value="<?php echo esc_attr($cf_settings['cf_api_token']); ?>" value="<?php echo esc_attr($cf_settings['cf_api_token']); ?>" class="regular-text validate-field validation-group-api_token validation-group-api_credentials">
                <button type="button" class="button button-secondary wp-hide-pw sb-hide-pwd hide-if-no-js" data-toggle="0" aria-label="Show password">
                  <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                </button>
              </div>

              <p class="invalid-message"></p>
              <p><small><?php echo sprintf(sb__('Make sure to add permissions for %s when creating a token.'), sb_cf_cache()->api_permissions_needed()); ?></small></p>
            </td>
          </tr>
          <tr class="feature_cf_auth_type-api_key"<?php if ( $cf_settings['cf_auth_type'] != 'api_key' ) echo ' style="display: none;"' ?>>
            <th scope="row"><label for="sb_email"><?php sb_e('Cloudflare e-mail'); ?></label></th>
            <td>
              <input name="<?php echo sb_get_option_name('cf_email'); ?>" type="text" id="sb_email" autocomplete="off" data-original-value="<?php echo esc_attr($cf_settings['cf_email']); ?>" value="<?php echo esc_attr($cf_settings['cf_email']); ?>" class="regular-text validate-field validation-input-email validation-group-api_key_credentials validation-group-api_credentials">
              <p class="invalid-message"></p>
            </td>
          </tr>
          <tr class="feature_cf_auth_type-api_key"<?php if ( $cf_settings['cf_auth_type'] != 'api_key' ) echo ' style="display: none;"' ?>>
            <th scope="row"><label for="sb_api_key"><?php sb_e('API key'); ?></label></th>
            <td>
              <div class="sb-pwd">
                <input name="<?php echo sb_get_option_name('cf_api_key'); ?>" type="password" id="sb_api_key" autocomplete="off" data-original-value="<?php echo esc_attr($cf_settings['cf_api_key']); ?>" value="<?php echo esc_attr($cf_settings['cf_api_key']); ?>" class="regular-text validate-field validation-input-api_key validation-group-api_key_credentials validation-group-api_credentials">
                <button type="button" class="button button-secondary wp-hide-pw sb-hide-pwd hide-if-no-js" data-toggle="0" aria-label="Show password">
                  <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                </button>
              </div>
              <p class="invalid-message"></p>
            </td>
          </tr>
          <tr >
            <th scope="row" colspan="100%" style="padding-bottom: 5px;">
              <h3 style="margin-bottom: 0;"><?php sb_e('Cloudflare zone'); ?></h3>
              <p style="font-weight: normal;"><?php sb_e('A domain in Cloudflare is called a zone. We\'ll need the ID of the zone you\'d like to connect here. You can find the Zone ID in the Cloudflare Overview tab of the domain you\'d like to connect, in the right sidebar under the API section.'); ?></p>
            </th>
          </tr>
          <tr>
            <th scope="row"><label for="zone_id"><?php sb_e('Zone ID'); ?></label></th>
            <td>

              <?php

                $zone = $cf_settings['cf_zone_id'] ? sb_cf_cache()->get_zone_by_id($cf_settings['cf_zone_id']) : false;
                $have_zones = false;
                $zones = sb_cf_cache()->list_zones();
                $have_zones = ( is_array($zones) && ! empty($zones) );
              ?>

              <input name="<?php echo sb_get_option_name('cf_zone_id'); ?>" type="text" id="zone_id" placeholder="Type zone ID<?php if ( $have_zones ) echo ' or use the choices below'; ?>" value="<?php echo esc_attr($cf_settings['cf_zone_id']); ?>" class="regular-text validate-field validation-group-zone_id">
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
          <tr>
            <th scope="row" colspan="100%" style="padding-bottom: 0;">
              <h3 style="margin-bottom: 0;"><?php sb_e('Cron setup'); ?></h3>
              <p style="font-weight: normal;"><?php echo sprintf(sb__('Use this feature to trigger cache bust by cron instead of doing it immediately. The cron task is set to run every 1 minute. We recommend that you set WordPress up to use the UNIX-based cron. Read about how to achieve this %shere%s.'), '<a href="https://servebo.lt/vkr8-" target="_">', '</a>'); ?></p>
            </th>
          </tr>
          <tr>
            <th scope="row"><?php sb_e('Cache purge via cron'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><span><?php sb_e('Cache purge via cron'); ?></span></legend>
                <label for="cf_cron_purge">
                  <input name="<?php if ( ! sb_cf_cache()->cron_state_is_overridden() ) echo sb_get_option_name('cf_cron_purge'); ?>" type="checkbox" id="cf_cron_purge" value="1"<?php if ( sb_cf_cache()->cron_state_is_overridden() ) echo ' disabled'; ?> <?php checked($cf_settings['cf_cron_purge']); ?>>
                    <?php if ( sb_cf_cache()->cron_state_is_overridden() ) : ?>
                    <input type="hidden" name="<?php echo sb_get_option_name('cf_cron_purge'); ?>" value="<?php echo $cf_settings['cf_cron_purge'] ? '1' : '0'; ?>">
                    <?php endif; ?>
                  <?php sb_e('Use cron to purge cache?'); ?>
                    <?php if ( sb_cf_cache()->cron_state_is_overridden() ) : ?>
                        <p><em>This value is overriden by the constant "SERVEBOLT_CF_PURGE_CRON" which is currently set to "<?php echo sb_boolean_to_string(sb_cf_cache()->cron_purge_is_active()); ?>".</em></p>
                    <?php endif; ?>
                </label>
              </fieldset>
            </td>
          </tr>
          <tr class="feature_cf_cron_purge sb-toggle-active-cron-item<?php if ( ! sb_cf_cache()->cron_purge_is_active() ) echo ' cf-hidden-cron'; ?>">
            <th scope="row" colspan="2" style="padding-bottom: 5px;">
              <label for="items_to_purge"><?php sb_e('Cache purge queue'); ?></label>
              <p style="font-weight: normal;"><?php echo sprintf(sb__('The list below contains all the posts/URLs that are scheduled for cache purge. The max number of items in the list is %s, the rest will be unavailable for display. The most recently added item can be seen at the bottom of the list.%s Note: If you have more than %s items in the list then that would indicate that there is something wrong with the cron-setup. If so please investigate and/or contact support.'), $max_number_of_cache_purge_queue_items, '<br>', $max_number_of_cache_purge_queue_items); ?></p>
            </th>
          </tr>
          <tr class="sb-toggle-active-cron-item<?php if ( ! sb_cf_cache()->cron_purge_is_active() ) echo ' cf-hidden-cron'; ?>">
            <td colspan="2" style="padding-top:0;padding-left:0;">

              <?php $items_to_purge = sb_cf_cache()->get_items_to_purge($max_number_of_cache_purge_queue_items); ?>

              <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                  <button type="button" class="button action remove-selected-purge-items" disabled><?php sb_e('Remove selected'); ?></button>
                </div>
                <div class="alignleft actions bulkactions">
                  <button type="button" style="float:left;" class="button action flush-purge-items-queue"<?php if ( count($items_to_purge) === 0 ) echo ' disabled'; ?>><?php sb_e('Flush queue'); ?></button>
                </div>
                <div class="alignleft actions bulkactions">
                  <button type="button" style="float:left;" class="button action refresh-purge-items-queue"><?php sb_e('Refresh queue'); ?></button>
                </div>

                <span class="spinner purge-queue-loading-spinner"></span>
                <br class="clear">
              </div>

              <table class="wp-list-table widefat striped" id="purge-items-table">
                <?php sb_cf_cache_admin_controls()->purge_queue_list($items_to_purge); ?>
              </table>

              <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                  <button type="button" id="doaction" class="button action remove-selected-purge-items" disabled><?php sb_e('Remove selected'); ?></button>
                </div>
              </div>

            </td>
          </tr>
        </tbody>
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

    <?php if ( sb_is_debug() ) : ?>

      <div class="sb-toggle-active-cf-item sb-toggle-active-cron-item<?php if ( ! sb_cf_cache()->cron_purge_is_active() ) echo ' cf-hidden-cron'; ?>">

        <h2><?php sb_e('Cron debug'); ?></h2>
        <p><?php sb_e('Cron is active:'); ?> <?php

          $next_run_timestamp = sb_get_next_cron_time(sb_cf_cache()->get_purge_cron_key());

          if ( sb_cf_cache()->cron_purge_is_active() ) {

              sb_e('Yes');
              if ( sb_cf_cache()->cron_state_is_overridden() ) {
                sb_e(', due to constant "SERVEBOLT_CF_PURGE_CRON" being set to "true".');
              }

            if ( ! sb_cf_cache()->should_purge_cache_queue() ) {
              sb_e('. Note that cache purge requests are only added to the queue, not executed. This is due to the constant "SERVEBOLT_CF_PURGE_CRON_PARSE_QUEUE" being set to "false".');
            }

          } else {
              sb_e('No');
              if ( sb_cf_cache()->cron_state_is_overridden() ) {
                  sb_e(', due to constant "SERVEBOLT_CF_PURGE_CRON" being set to "false".');
              }
          }

        ?></p>
        <p><?php sb_e('Cron schedule hook:'); ?> <?php echo sb_cf_cache()->get_purge_cron_key(); ?></p>
        <p><?php sb_e('Next run:'); ?> <?php echo $next_run_timestamp ? get_date_from_gmt( date_i18n('Y-m-d H:i:s', $next_run_timestamp) ) : '-'; ?></p>

      </div>

    <?php endif; ?>

  <?php endif; ?>

</div>
