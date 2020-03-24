<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap sb-content" id="sb-configuration">

	<h1><?php sb_e('Cloudflare Cache'); ?></h1>

	<?php settings_errors(); ?>

  <p>This feature will automatically purge the Cloudflare cache whenever you do an update in WordPress. Neat right?</p>

	<?php if ( is_network_admin() ) : ?>

    <p>Please navigate to each blog to control settings regarding Cloudflare cache purging.</p>

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
          <td><?php echo sb_cf()->cf_is_active($site->blog_id) ? sb__('Yes') : sb__('No'); ?></td>
          <td><a href="<?php echo get_admin_url( $site->blog_id, 'admin.php?page=servebolt-wp' ); ?>" class="button btn"><?php sb_e('Go to site Cloudflare settings'); ?></a></td>
        </tr>
	  <?php endforeach; ?>
      </tbody>
    </table>

  <?php else : ?>

	<?php $cf_settings = sb_cf_cache_controls()->get_settings_items(); ?>

  <?php if ( sb_cf()->cf_is_active() && sb_cf()->cf_cache_feature_available() ) : ?>

  <p>Active Cloudflare cache zone:
	<?php if ( $cf_settings['cf_zone_id'] ) : ?>
    <?php $zone = sb_cf()->get_zone_by_id($cf_settings['cf_zone_id']); ?>
     <?php echo ( $zone ? $zone->name . ' (' . $cf_settings['cf_zone_id'] . ')' : $cf_settings['cf_zone_id'] ); ?></p>
  <?php else : ?>
    No zone selected
	<?php endif; ?>
    </p>
  <?php if ( ! sb_cf()->cf_cache_feature_available() ) : ?>
	<p>Make sure you have added the API credentials and selected a zone to use this functionality.</p>
  <?php endif; ?>
  <p>
    <button class="sb-purge-all-cache sb-button yellow inline">Purge all cache</button>
    <button class="sb-purge-url sb-button yellow inline">Purge a URL</button>
  </p>
      <br>
  <?php endif; ?>

  <h1>Configuration</h1>
  <p>This feature can be set up using WP CLI or with the form below.</p><p>Run <code>wp servebolt cf --help</code> to see available commands.</p>

  <form method="post" autocomplete="off" action="options.php" name="sb_configuration_table" id="sb-configuration-table" onsubmit="return window.sb_validate_cf_configuration_form(event);">
	  <?php settings_fields( 'sb-cf-options-page' ) ?>
	  <?php do_settings_sections( 'sb-cf-options-page' ) ?>

    <table class="form-table" id="sb-configuration-table" role="presentation">
      <thead>
        <tr>
          <th scope="row">Cloudflare cache-feature</th>
          <td>
            <fieldset>
              <legend class="screen-reader-text"><span>Cloudflare cache-feature active?</span></legend>
              <label for="clourdlare_switch">
                <input name="<?php echo sb_get_option_name('cf_switch'); ?>" type="checkbox" id="cloudflare_switch" value="1" <?php checked($cf_settings['cf_switch']); ?>>
                Active?
              </label>
            </fieldset>
          </td>
        </tr>
      </thead>
      <tbody class="sb-toggle-active-item"<?php if ( ! $cf_settings['cf_switch'] ) echo ' style="display: none;"'; ?>>
        <tr>
          <th scope="row" colspan="100%" style="padding-bottom: 5px;">
            <h3 style="margin-bottom: 0;">API configuration</h3>
            <p style="font-weight: normal;">We'll be using the Cloudflare API to connect your site to your Cloudflare account.<br>We recommend using an API token as this will allow for more granular access control. You can learn more about how to set this up in <a href="https://servebo.lt/xjmkq" target="_blank">our documentation</a>.</p>
          </th>
        </tr>
        <tr>
          <th scope="row">Authentication type</th>
          <td>
            <fieldset>
              <legend class="screen-reader-text"><span>Authentication type</span></legend>
              <label><input type="radio" name="<?php echo sb_get_option_name('cf_auth_type'); ?>" value="api_token" <?php checked($cf_settings['cf_auth_type'] == 'api_token'); ?>> <code>API token</code></label><br>
              <label><input type="radio" name="<?php echo sb_get_option_name('cf_auth_type'); ?>" value="api_key" <?php checked($cf_settings['cf_auth_type'] == 'api_key'); ?>> <code>API key</code></label>
            </fieldset>
          </td>
        </tr>
        <tr class="feature_cf_auth_type-api_token"<?php if ( $cf_settings['cf_auth_type'] != 'api_token' ) echo ' style="display: none;"' ?>>
          <th scope="row"><label for="api_token">API token</label></th>
          <td>
            <input name="<?php echo sb_get_option_name('cf_api_token'); ?>" type="text" id="api_token" data-original-value="<?php echo esc_attr($cf_settings['cf_api_token']); ?>" value="<?php echo esc_attr($cf_settings['cf_api_token']); ?>" class="regular-text validate-field validation-group-api_token">
            <p class="invalid-message"></p>
            <p><small>Make sure to add permissions for <?php echo sb_cf()->api_permissions_needed(); ?> when creating a token.</small></p>
          </td>
        </tr>
        <tr class="feature_cf_auth_type-api_key"<?php if ( $cf_settings['cf_auth_type'] != 'api_key' ) echo ' style="display: none;"' ?>>
          <th scope="row"><label for="email">Cloudflare e-mail</label></th>
          <td>
            <input name="<?php echo sb_get_option_name('cf_email'); ?>" type="email" id="email" data-original-value="<?php echo esc_attr($cf_settings['cf_email']); ?>" value="<?php echo esc_attr($cf_settings['cf_email']); ?>" class="regular-text validate-field validation-input-email validation-group-api_key_credentials">
            <p class="invalid-message"></p>
          </td>
        </tr>
        <tr class="feature_cf_auth_type-api_key"<?php if ( $cf_settings['cf_auth_type'] != 'api_key' ) echo ' style="display: none;"' ?>>
          <th scope="row"><label for="api_key">API key</label></th>
          <td>
            <input name="<?php echo sb_get_option_name('cf_api_key'); ?>" type="text" id="api_key" data-original-value="<?php echo esc_attr($cf_settings['cf_api_key']); ?>" value="<?php echo esc_attr($cf_settings['cf_api_key']); ?>" class="regular-text validate-field validation-input-api_key validation-group-api_key_credentials">
            <p class="invalid-message"></p>
          </td>
        </tr>
        <tr >
          <th scope="row" colspan="100%" style="padding-bottom: 5px;">
            <h3 style="margin-bottom: 0;">Cloudflare zone</h3>
            <p style="font-weight: normal;">A domain in Cloudflare is called a zone. We'll need the ID of the zone you'd like to connect here. You can find the Zone ID in the Cloudflare Overview tab of the domain you'd like to connect, in the right sidebar under the API section.</p>
          </th>
        </tr>
        <tr>
          <th scope="row"><label for="zone_id">Zone ID</label></th>
          <td>

	          <?php
              $have_zones = false;
              $zones = [];
              if ( $cf_settings['cf_auth_type'] === 'api_key' ) {
                $zones = sb_cf()->list_zones();
                $have_zones = ( is_array($zones) && ! empty($zones) );
              }
            ?>

            <input name="<?php echo sb_get_option_name('cf_zone_id'); ?>" type="text" id="zone_id" placeholder="Type zone ID<?php if ( $have_zones ) echo ' or use the choices below'; ?>" value="<?php echo esc_attr($cf_settings['cf_zone_id']); ?>" class="regular-text validate-field validation-group-zone_id">
            <span class="spinner zone-loading-spinner"></span>
            <p class="invalid-message"></p>
            <p class="active-zone"<?php if ( ! isset($zone) || ! $zone ) echo ' style="display: none;"'; ?>>Selected zone: <span><?php if ( isset($zone) && $zone ) echo $zone->name; ?></span></p>

            <div class="zone-selector-container"<?php if ( ! $have_zones ) echo ' style="display: none;"'; ?>>
              <p style="margin-top: 10px;">Available zones:</p>
              <ul class="zone-selector" style="margin: 5px 0;">
                <?php foreach($zones as $zone) : ?>
                  <li><a href="#" data-name="<?php echo esc_attr($zone->name); ?>" data-id="<?php echo esc_attr($zone->id); ?>"><?php echo $zone->name; ?> (<?php echo $zone->id; ?>)</a></li>
                <?php endforeach; ?>
              </ul>
            </div>

          </td>
        </tr>
        <tr>
          <th scope="row" colspan="100%" style="padding-bottom: 0;">
            <h3 style="margin-bottom: 0;">Cron setup</h3>
            <p>Use this feature to trigger cache bust by cron instead of doing it immediately. The cron task is set to run every 1 minute.<br>We recommend that you set WordPress up to use the UNIX-based cron. Read about how to achieve this <a href="https://servebo.lt/vkr8-" target="_">here</a>.</p>
          </th>
        </tr>
        <tr>
          <th scope="row">Cache purge via cron</th>
          <td>
            <fieldset>
              <legend class="screen-reader-text"><span>Cache purge via cron</span></legend>
              <label for="cf_cron_purge">
                <input name="<?php echo sb_get_option_name('cf_cron_purge'); ?>" type="checkbox" id="cf_cron_purge" value="1" <?php checked($cf_settings['cf_cron_purge']); ?>>
                Use cron to purge cache?
              </label>
            </fieldset>
          </td>
        </tr>
        <tr class="feature_cf_cron_purge">
          <th scope="row">
            <label for="items_to_purge">Cache purge queue<p><small>Remember to save after altering the table.</small></p></label>
          </th>
          <td>

	          <?php $itemsToPurge = sb_cf()->get_items_to_purge(); ?>

            <button type="button" style="float:left;" class="button action flush-purge-items-queue"<?php if ( count($itemsToPurge) === 0 ) echo ' disabled'; ?>>Flush queue</button>

            <div class="tablenav top">
              <div class="alignleft actions bulkactions">
                <button type="button" class="button action remove-selected-purge-items" disabled>Remove selected</button>
              </div>
              <br class="clear">
            </div>

            <table class="wp-list-table widefat striped" id="purge-items-table">

              <thead>
                <tr>
                  <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></td>
                  <th scope="col" id="post_id" class="manage-column column-post-id">Post ID</th>
                  <th scope="col" id="url" class="manage-column column-url">URL</th>
                </tr>
              </thead>

              <tfoot>
              <tr>
                <td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2">Select All</label><input id="cb-select-all-2" type="checkbox"></td>
                <th scope="col" class="manage-column column-title column-primary">Post ID</th>
                <th scope="col" class="manage-column column-author">URL</th>
              </tr>
              </tfoot>

              <tbody id="the-list">
                <tr class="no-items<?php if ( count($itemsToPurge) > 0 ) echo ' hidden'; ?>"><td class="colspanchange" colspan="3">No purge items found.</td></tr>
                <?php foreach($itemsToPurge as $i => $item) : ?>
                <?php
                  if ( is_numeric($item) && $post = get_post($item) ) {
                    $title = sprintf( '%s (%s)', get_the_title($item), $item);
                    $url = get_permalink($item);
	                  $isUrl = true;
                  } else {
	                  $title = null;
	                  $url = $item;
	                  $isUrl = filter_var($item, FILTER_VALIDATE_URL) !== false;
                  }
                ?>
                <tr class="purge-item">
                  <th scope="row" class="check-column">
                    <label class="screen-reader-text" for="cb-select-<?php echo $i; ?>">Select Hello world!</label>
                    <input type="hidden" name="<?php echo sb_get_option_name('cf_items_to_purge'); ?>[]" value="<?php echo esc_attr($item); ?>">
                    <input id="cb-select-<?php echo $i; ?>" type="checkbox">
                  </th>
                  <td class="column-post-id has-row-actions" style="padding-left: 0;padding-top: 10px;padding-bottom: 10px;">
                    <?php if ($title) : ?>
                    <strong><?php echo $title; ?></strong>
                    <?php else: ?>
                    Purged via URL only, no post object available.
                    <?php endif; ?>
                    <div class="row-actions">
                      <span class="trash"><a href="#" class="remove-purge-item-from-queue">Trash</a><?php if ( $isUrl ) : ?> | <?php endif; ?></span>
                      <?php if ( $isUrl ) : ?>
                      <span class="view"><a href="<?php echo esc_attr($url); ?>" target="_blank">View</a></span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td class="column-url" style="padding-left: 0;padding-top: 10px;padding-bottom: 10px;">
	                  <?php if ( $isUrl ) : ?>
                      <a href="<?php echo esc_attr($url); ?>" target="_blank"><?php echo $url; ?></a>
                    <?php else: ?>
	                    <?php echo $url; ?>
                    <?php endif; ?>

                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>

            </table>

            <div class="tablenav bottom">
              <div class="alignleft actions bulkactions">
                <button type="button" id="doaction" class="button action remove-selected-purge-items" disabled>Remove selected</button>
              </div>
            </div>

          </td>
        </tr>
      </tbody>
    </table>

    <?php submit_button(); ?>

  </form>

	<?php if ( sb_is_debug() ) : ?>


      <h2>Cron debug</h2>
      <p>Cron is active: <?php

	      $next_run_timestamp = sb_get_next_cron_time(sb_cf()->get_cron_key());

        if ( sb_cf()->cron_purge_is_active(false) ) {

          if ( sb_cf()->cron_active_state_override() ) {
	          sb_e('Yes, due to constant "SERVEBOLT_CF_PURGE_CRON" being set to "true');
          } else {
	          sb_e('Yes');
          }

          if ( sb_cf()->should_purge_cache_queue() ) {
	          sb_e('. Note that cache purge requests are only added to the queue, not executed. This is due to the constant "SERVEBOLT_CF_PURGE_CRON_PARSE_QUEUE" being set to "false".');
          }

        } else {
          sb_e('No');
        }

      ?></p>
      <p>Cron schedule hook: <?php echo sb_cf()->get_cron_key(); ?></p>
      <p>Next run: <?php echo $next_run_timestamp ? date_i18n('Y-m-d H:i:s', $next_run_timestamp) : '-'; ?></p>

  <?php endif; ?>

  <?php endif; ?>

</div>
