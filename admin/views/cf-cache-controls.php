<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap sb-content" id="sb-configuration">
	<h1><?php sb_e('Cloudflare Cache'); ?></h1>

  <p>This feature will automatically purge the Cloudflare cache whenever you do an update in Wordpress. Neat right?</p>

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
    <?php $disabled = ' disabled title="Make sure you have set up the CF features correctly to purge cache."'; ?>
    <button class="sb-purge-all-cache sb-button yellow inline"<?php if ( ! sb_cf()->cf_cache_feature_available() ) echo $disabled; ?>>Purge all cache</button>
    <button class="sb-purge-url sb-button yellow inline"<?php if ( ! sb_cf()->cf_cache_feature_available() ) echo $disabled; ?>>Purge a URL</button>
  </p>

  <br>

  <h1>Configuration</h1>
  <p>This feature can be set up using WP CLI or with the form below.</p><p>Run <code>wp servebolt cf --help</code> to see available commands.</p>

  <form method="post" action="options.php" id="sb-configuration-table" onsubmit="return window.sb_validate_configuration_form();">
	  <?php settings_fields( 'sb-cf-options-page' ) ?>
	  <?php do_settings_sections( 'sb-cf-options-page' ) ?>

    <table class="form-table" id="sb-configuration-table" role="presentation">
      <tbody>
        <tr>
          <th scope="row" colspan="100%" style="padding-bottom: 5px;"><h3 style="margin-bottom: 0;">API configuration</h3></th>
        </tr>
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
        <tr>
          <th scope="row">Authentication type</th>
          <td>
            <fieldset>
              <legend class="screen-reader-text"><span>Authentication type</span></legend>
              <label><input type="radio" name="<?php echo sb_get_option_name('cf_auth_type'); ?>" value="apiToken" <?php checked($cf_settings['cf_auth_type'] == 'apiToken'); ?>> <code>API token</code></label><br>
              <label><input type="radio" name="<?php echo sb_get_option_name('cf_auth_type'); ?>" value="apiKey" <?php checked($cf_settings['cf_auth_type'] == 'apiKey'); ?>> <code>API key</code></label>
              <p>Read about Cloudflare API authentication <a href="https://support.cloudflare.com/hc/en-us/articles/200167836-Managing-API-Tokens-and-Keys" target="_blank">here</a>. We recommend using an API token since it allows for more fine grained access control.</p>
            </fieldset>
          </td>
        </tr>
        <tr class="feature_cf_auth_type-apiToken"<?php if ( $cf_settings['cf_auth_type'] != 'apiToken' ) echo ' style="display: none;"' ?>>
          <th scope="row"><label for="api_token">API token</label></th>
          <td>
            <input name="<?php echo sb_get_option_name('cf_api_token'); ?>" type="text" id="api_token" value="<?php echo esc_attr($cf_settings['cf_api_token']); ?>" class="regular-text">
            <p><small>Make sure to add permissions for <?php echo sb_cf()->api_permissions_needed(); ?> when creating a token.</small></p>
          </td>
        </tr>
        <tr class="feature_cf_auth_type-apiKey"<?php if ( $cf_settings['cf_auth_type'] != 'apiKey' ) echo ' style="display: none;"' ?>>
          <th scope="row"><label for="email">Cloudflare e-mail</label></th>
          <td><input name="<?php echo sb_get_option_name('cf_email'); ?>" type="email" id="email" value="<?php echo esc_attr($cf_settings['cf_email']); ?>" class="regular-text"></td>
        </tr>
        <tr class="feature_cf_auth_type-apiKey"<?php if ( $cf_settings['cf_auth_type'] != 'apiKey' ) echo ' style="display: none;"' ?>>
          <th scope="row"><label for="api_key">API key</label></th>
          <td><input name="<?php echo sb_get_option_name('cf_api_key'); ?>" type="text" id="api_key" value="<?php echo esc_attr($cf_settings['cf_api_key']); ?>" class="regular-text"></td>
        </tr>
        <tr>
          <th scope="row" colspan="100%" style="padding-bottom: 5px;">
            <h3 style="margin-bottom: 0;">Cloudflare zone</h3>
            <p>The zone is the Cloudflare resource you would like to interact with.</p>
          </th>
        </tr>
        <tr>
          <th scope="row"><label for="zone_id">Zone ID</label></th>
          <td>
            <input name="<?php echo sb_get_option_name('cf_zone_id'); ?>" type="text" id="zone_id" placeholder="Type zone ID or use the choices below" value="<?php echo esc_attr($cf_settings['cf_zone_id']); ?>" class="regular-text">

            <?php $zones = sb_cf()->list_zones(); ?>

            <?php if ( is_array($zones) && ! empty($zones) ) : ?>
            <p style="margin-top: 10px;">Available zones:</p>
            <ul class="zone-selector" style="margin: 5px 0;">
              <?php foreach($zones as $zone) : ?>
                <li><a href="#" data-id="<?php echo esc_attr($zone->id); ?>"><?php echo $zone->name; ?> (<?php echo $zone->id; ?>)</a></li>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>

          </td>
        </tr>
        <tr>
          <th scope="row" colspan="100%" style="padding-bottom: 0;">
            <h3 style="margin-bottom: 0;">Cron setup</h3>
            <p>Use this feature to trigger cache bust by cron instead of doing it immediately. The cron task is set to run every 1 minute.<br>We recommend that you set WordPress up to use the UNIX-based cron. Read about how to achieve this <a href="https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/" target="_">here</a>.</p>
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

  <?php endif; ?>

</div>
