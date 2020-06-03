<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap sb-content" id="sb-configuration">

	<h1><?php sb_e('Cloudflare Cache'); ?></h1>

	<?php $max_number_of_cache_purge_queue_items = (int) apply_filters('sb_optimizer_purge_item_list_limit', 500); ?>
  <?php $number_of_cache_purge_queue_items = sb_cf_cache()->count_items_to_purge(); ?>
	<?php if ( ! is_network_admin() ) : ?>
		<?php if ( $number_of_cache_purge_queue_items > $max_number_of_cache_purge_queue_items ) : ?>
        <div class="notice notice-warning">
          <p><?php echo sprintf(sb__('Note: It seems like there is over %s items in the %scache purge queue list%s. This indicates that there might be something wrong with the cron-setup since the cache does not get purged automatically. Please make sure you have set everything up correctly and/or contact support.'), $max_number_of_cache_purge_queue_items, '<a href="#purge-items-table">', '</a>'); ?></p>
        </div>
		<?php endif; ?>
	<?php endif; ?>

	<?php settings_errors(); ?>

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
          <td><a href="<?php echo get_admin_url( $site->blog_id, 'admin.php?page=servebolt-cf-cache-control' ); ?>" class="button btn"><?php sb_e('Go to site Cloudflare settings'); ?></a></td>
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
            <th scope="row"><label for="api_token"><?php sb_e('API token'); ?></label></th>
            <td>

              <div class="sb-pwd">
                <input name="<?php echo sb_get_option_name('cf_api_token'); ?>" type="password" id="api_token" data-original-value="<?php echo esc_attr($cf_settings['cf_api_token']); ?>" value="<?php echo esc_attr($cf_settings['cf_api_token']); ?>" class="regular-text validate-field validation-group-api_token validation-group-api_credentials">
                <button type="button" class="button button-secondary wp-hide-pw sb-hide-pwd hide-if-no-js" data-toggle="0" aria-label="Show password">
                  <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                </button>
              </div>

              <p class="invalid-message"></p>
              <p><small><?php echo sprintf(sb__('Make sure to add permissions for %s when creating a token.'), sb_cf_cache()->api_permissions_needed()); ?></small></p>
            </td>
          </tr>
          <tr class="feature_cf_auth_type-api_key"<?php if ( $cf_settings['cf_auth_type'] != 'api_key' ) echo ' style="display: none;"' ?>>
            <th scope="row"><label for="email"><?php sb_e('Cloudflare e-mail'); ?></label></th>
            <td>
              <input name="<?php echo sb_get_option_name('cf_email'); ?>" type="email" id="email" data-original-value="<?php echo esc_attr($cf_settings['cf_email']); ?>" value="<?php echo esc_attr($cf_settings['cf_email']); ?>" class="regular-text validate-field validation-input-email validation-group-api_key_credentials validation-group-api_credentials">
              <p class="invalid-message"></p>
            </td>
          </tr>
          <tr class="feature_cf_auth_type-api_key"<?php if ( $cf_settings['cf_auth_type'] != 'api_key' ) echo ' style="display: none;"' ?>>
            <th scope="row"><label for="api_key"><?php sb_e('API key'); ?></label></th>
            <td>
              <div class="sb-pwd">
                <input name="<?php echo sb_get_option_name('cf_api_key'); ?>" type="password" id="api_key" data-original-value="<?php echo esc_attr($cf_settings['cf_api_key']); ?>" value="<?php echo esc_attr($cf_settings['cf_api_key']); ?>" class="regular-text validate-field validation-input-api_key validation-group-api_key_credentials validation-group-api_credentials">
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
                  <input name="<?php echo sb_get_option_name('cf_cron_purge'); ?>" type="checkbox" id="cf_cron_purge" value="1" <?php checked($cf_settings['cf_cron_purge']); ?>>
                  <?php sb_e('Use cron to purge cache?'); ?>
                </label>
              </fieldset>
            </td>
          </tr>
          <tr class="feature_cf_cron_purge sb-toggle-active-cron-item<?php if ( ! $cf_settings['cf_cron_purge'] ) echo ' cf-hidden-cron'; ?>">
            <th scope="row" colspan="2" style="padding-bottom: 5px;">
              <label for="items_to_purge"><?php sb_e('Cache purge queue'); ?></label>
              <p style="font-weight: normal;"><?php echo sprintf(sb__('The list below contains all the posts/URLs that are scheduled for cache purge. The max number of items in the list is %s, the rest will be unavailable for display. The most recently added item can be seen at the bottom of the list.%s Note: If you have more than %s items in the list then that would indicate that there is something wrong with the cron-setup. If so please investigate and/or contact support.'), $max_number_of_cache_purge_queue_items, '<br>', $max_number_of_cache_purge_queue_items); ?></p>
            </th>
          </tr>
          <tr class="sb-toggle-active-cron-item<?php if ( ! $cf_settings['cf_cron_purge'] ) echo ' cf-hidden-cron'; ?>">
            <td colspan="2" style="padding-top:0;padding-left:0;">

              <?php $items_to_purge = sb_cf_cache()->get_items_to_purge($max_number_of_cache_purge_queue_items); ?>

              <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                  <button type="button" class="button action remove-selected-purge-items" disabled><?php sb_e('Remove selected'); ?></button>
                </div>
                <div class="alignleft actions bulkactions">
                  <button type="button" style="float:left;" class="button action flush-purge-items-queue"<?php if ( count($items_to_purge) === 0 ) echo ' disabled'; ?>><?php sb_e('Flush queue'); ?></button>
                </div>
                <span class="spinner purge-queue-loading-spinner"></span>
                <br class="clear">
              </div>

              <table class="wp-list-table widefat striped" id="purge-items-table">

                <thead>
                  <tr>
                    <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1"><?php sb_e('Select All'); ?></label><input id="cb-select-all-1" type="checkbox"></td>
                    <th scope="col" id="post_id" class="manage-column column-post-id"><?php sb_e('Post ID'); ?></th>
                    <th scope="col" id="post_id" class="manage-column column-post-id"><?php sb_e('Post title'); ?></th>
                    <th scope="col" id="url" class="manage-column column-url"><?php sb_e('URL'); ?></th>
                  </tr>
                </thead>

                <tfoot>
                <tr>
                  <td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2"><?php sb_e('Select All'); ?></label><input id="cb-select-all-2" type="checkbox"></td>
                  <th scope="col" class="manage-column column-title column-primary"><?php sb_e('Post ID'); ?></th>
                  <th scope="col" class="manage-column column-title column-primary"><?php sb_e('Post title'); ?></th>
                  <th scope="col" class="manage-column column-author"><?php sb_e('URL'); ?></th>
                </tr>
                </tfoot>

                <tbody id="the-list">
                  <tr class="no-items<?php if ( count($items_to_purge) > 0 ) echo ' hidden'; ?>"><td colspan="100%"><?php sb_e('No purge items found.'); ?></td></tr>
                  <?php foreach ( $items_to_purge as $i => $item ) : ?>
                  <?php

	                  $is_purge_all = false;
	                  $is_post = false;
	                  $is_url = true;
	                  $url = false;

                    if ( is_numeric($item) && $post = get_post($item) ) {
                      $is_post  = true;
                      $title    = get_the_title($item);
                      $url      = get_permalink($item);
	                    $edit_url = get_edit_post_link($item);
                    } elseif( $item == sb_purge_all_item_name() ) {
	                    $is_url       = false;
	                    $is_purge_all = true;
                    } else {
                      $url = $item;
                      $is_url = filter_var($item, FILTER_VALIDATE_URL) !== false;
                    }

                  ?>
                  <tr class="purge-item">
                    <th scope="row" class="check-column">
                      <label class="screen-reader-text" for="cb-select-<?php echo $i; ?>">Select "<?php echo $is_post ? $title : $url; ?>"</label>
                      <input type="hidden" class="purge-item-input" value="<?php echo esc_attr($item); ?>">
                      <input id="cb-select-<?php echo $i; ?>" type="checkbox">
                    </th>
                    <?php if ( $is_post ) : ?>
                    <td class="column-post-id has-row-actions purge-item-column">
                      <?php echo $item; ?>
                      <div class="row-actions">
                        <span class="trash"><a href="#" class="remove-purge-item-from-queue"><?php sb_e('Delete'); ?></a> | </span>
                        <span class="view"><a href="<?php echo esc_attr($url); ?>" target="_blank"><?php sb_e('View'); ?></a><?php if ( $edit_url ) echo ' | '; ?></span>
                        <?php if ( $edit_url ) : ?>
                        <span class="view"><a href="<?php echo $edit_url; ?>" target="_blank"><?php sb_e('Edit'); ?></a></span>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td class="purge-item-column"><strong><?php echo $title; ?></strong></td>
                    <?php elseif ($is_purge_all) : ?>
                    <td class="column-post-id has-row-actions purge-item-column" colspan="2">
                      <?php sb_e('Purge all-request') ?>
                      <div class="row-actions">
                        <span class="trash"><a href="#" class="remove-purge-item-from-queue"><?php sb_e('Delete'); ?></a><?php if ( $is_url ) : ?> | <?php endif; ?></span>
                      </div>
                    </td>
                    <?php else : ?>
                    <td class="column-post-id has-row-actions purge-item-column" colspan="2">
                      <?php sb_e('Purged via URL only, no post object available.') ?>
                      <div class="row-actions">
                        <span class="trash"><a href="#" class="remove-purge-item-from-queue"><?php sb_e('Delete'); ?></a><?php if ( $is_url ) : ?> | <?php endif; ?></span>
                        <?php if ( $is_url ) : ?>
                            <span class="view"><a href="<?php echo esc_attr($url); ?>" target="_blank"><?php sb_e('View'); ?></a></span>
                        <?php endif; ?>
                      </div>
                    </td>
                    <?php endif; ?>
                    <td class="column-url" style="padding-left: 0;padding-top: 10px;padding-bottom: 10px;">
                      <?php if ( $is_url ) : ?>
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

    <?php if ( apply_filters('sb_cf_form_validation_active', true) ) : ?>
      <script>
        document.getElementById('sb-configuration-form').addEventListener('submit', function(event) {
          return window.sb_validate_cf_configuration_form(event);
        });
      </script>
    <?php endif; ?>

    <?php if ( sb_is_debug() ) : ?>

      <div class="sb-toggle-active-cf-item sb-toggle-active-cron-item<?php if ( ! $cf_settings['cf_cron_purge'] ) echo ' cf-hidden-cron'; ?>">

        <h2><?php sb_e('Cron debug'); ?></h2>
        <p><?php sb_e('Cron is active:'); ?> <?php

          $next_run_timestamp = sb_get_next_cron_time(sb_cf_cache()->get_cron_key());

          if ( sb_cf_cache()->cron_purge_is_active(false) ) {

            if ( sb_cf_cache()->cron_active_state_override() ) {
              sb_e('Yes, due to constant "SERVEBOLT_CF_PURGE_CRON" being set to "true');
            } else {
              sb_e('Yes');
            }

            if ( sb_cf_cache()->should_purge_cache_queue() ) {
              sb_e('. Note that cache purge requests are only added to the queue, not executed. This is due to the constant "SERVEBOLT_CF_PURGE_CRON_PARSE_QUEUE" being set to "false".');
            }

          } else {
            sb_e('No');
          }

        ?></p>
        <p><?php sb_e('Cron schedule hook:'); ?> <?php echo sb_cf_cache()->get_cron_key(); ?></p>
        <p><?php sb_e('Next run:'); ?> <?php echo $next_run_timestamp ? date_i18n('Y-m-d H:i:s', $next_run_timestamp) : '-'; ?></p>

      </div>

    <?php endif; ?>

  <?php endif; ?>

</div>
