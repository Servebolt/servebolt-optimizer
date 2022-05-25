jQuery(document).ready(function($) {

  // Toggle cache purge feature active/inactive
  $('#sb-configuration #cache_purge_switch').change(function() {
    var isActive = $(this).is(':checked');
    sbToggleCachePurgeFeatureActive(isActive);
    if (isActive) {
      $('#sb-configuration input[name="servebolt_cache_purge_driver"]:checked').change();
    }
  });

  // Toggle cache purge driver
  $('#sb-configuration input[name="servebolt_cache_purge_driver"]').change(function() {
    sbToggleCachePurgeDriver($(this).val());
  });

  // Toggle between API key and API token authentication
  $('#sb-configuration input[name="servebolt_cf_auth_type"]').change(function() {
    sb_toggle_auth_type($(this).val());
  });

  // When API credentials are changed - resolve current Zone ID (if any) and populate available zone list.
  $('#sb-configuration .validation-group-api_credentials').on('keyup change', function() {
    sb_lookup_zones();
  });

  // Select zone from available zone
  $('#sb-configuration .zone-selector').on('click', 'a', function(e) {
    e.preventDefault();
    var input = $('#sb-configuration input[name="servebolt_cf_zone_id"]');
    input.val( $(this).data('id') );
    sb_set_active_zone($(this).data('name'));
  });

  // Try to resolve zone name from zone Id
  $('#sb-configuration input[name="servebolt_cf_zone_id"]').on('keyup change', function() {
    sb_resolve_zone_name();
  });

  // Toggle Cloudflare Cron cache purge feature active/inactive
  $('#sb-configuration #cf_cron_purge').change(function() {
    sb_toggle_cf_cron_feature_active($(this).is(':checked'));
  });

  // Remove purge item from purge queue
  $('#sb-configuration').on('click', '.remove-purge-item-from-queue', function(e) {
    e.preventDefault();
    remove_purge_item(this);
  });

  // Select purge item
  $('#sb-configuration #purge-items-table').on('change', 'input[type="checkbox"]', function() {
    var checkboxCount = $('#sb-configuration #purge-items-table input[type="checkbox"]:checked').length,
      itemCount = $('#sb-configuration #purge-items-table tbody .purge-item').length,
      buttons = $('#sb-configuration .remove-selected-purge-items');
    buttons.prop('disabled', (checkboxCount === 0 || itemCount === 0));
  });

  // Remove selected purge items from purge queue
  $('#sb-configuration .remove-selected-purge-items').click(function() {
    remove_selected_purge_items();
  });

  // Flush cache purge queue
  $('#sb-configuration .flush-purge-items-queue').click(function() {
    flush_purge_queue();
  });

  // Flush cache purge queue
  $('#sb-configuration .refresh-purge-items-queue').click(function() {
    refresh_purge_queue();
  });

  /**
   * Delete cache purge queue items.
   */
  function delete_cache_purge_queue_items(items_to_remove, success_function) {
    setTimeout(function () {
      var spinner = $('#sb-configuration .purge-queue-loading-spinner'),
          data = {
            action: 'servebolt_delete_cache_purge_queue_items',
            security: servebolt_optimizer_ajax_object.ajax_nonce,
            items_to_remove: items_to_remove,
          };
      spinner.addClass('is-active');
      $.ajax({
        type: 'POST',
        url: servebolt_optimizer_ajax_object.ajaxurl,
        data: data,
        success: function (response) {
          spinner.removeClass('is-active');
          if (response.success) {
            if (success_function) success_function();
          } else {
            window.sb_warning('Ouch...', 'Could not alter the cache purge queue.');
          }
        },
        error: function () {
          spinner.removeClass('is-active');
          window.sb_warning('Ouch...', 'Could not alter the cache purge queue.');
        }
      });
    }, 250);
  }

  /**
   * Indicate the name of the selected zone.
   *
   * @param name
   */
  function sb_set_active_zone(name) {
    var active_zone_container = $('#sb-configuration .active-zone');
    if ( name ) {
      active_zone_container.find('span').text(name);
      active_zone_container.show();
    } else {
      active_zone_container.find('span').text('');
      active_zone_container.hide();
    }
  }

  /**
   * Check if we got credentials.
   *
   * @returns {boolean}
   */
  function sb_has_credentials() {
    var auth_type = $('#sb-configuration input[name="servebolt_cf_auth_type"]:checked').val();
    switch (auth_type) {
      case 'api_token':
        var api_token = $('#sb-configuration #sb_cf_api_token').val();
        return api_token != '';
        break;
      case 'api_key':
        var email = $('#sb-configuration #sb_cf_email').val(),
          api_key = $('#sb-configuration #sb_cf_api_key').val();
        return email != '' && api_key != '';
        break;
    }
    return false;
  }

  /**
   * Try to get zone name based on the specified zone Id.
   *
   * @type {null}
   */
  let zone_id_type_wait_timeout = null;
  function sb_resolve_zone_name() {
    clearTimeout(zone_id_type_wait_timeout);
    if ( ! sb_has_credentials() ) {
      sb_set_active_zone(false);
      return;
    }
    zone_id_type_wait_timeout = setTimeout(function () {
      var form = $('#sb-configuration-form'),
        zone_id = $('#sb-configuration #zone_id').val(),
        spinner = $('#sb-configuration .zone-loading-spinner'),
        data = {
          action: 'servebolt_lookup_zone',
          security: servebolt_optimizer_ajax_object.ajax_nonce,
          form: form.serialize(),
        };
      if ( ! zone_id ) {
        sb_set_active_zone(false);
        return;
      }
      spinner.addClass('is-active');
      $.ajax({
        type: 'POST',
        url: servebolt_optimizer_ajax_object.ajaxurl,
        data: data,
        success: function (response) {
          spinner.removeClass('is-active');
          if (response.success) {
            sb_set_active_zone(response.data.zone);
          } else {
            sb_set_active_zone(false);
          }
        },
        error: function () {
          spinner.removeClass('is-active');
          sb_set_active_zone(false);
        }
      });
    }, 1000);
  }

  /**
   * Toggle visibility of certain fields on or off.
   *
   * @param {string} itemName - The suffix of the field(s).
   * @param {boolean} isVisible - Whether the field should be visible or not.
   */
  function sbToggleConfigItemVisibility(itemName, isVisible) {
    var items = $('#sb-configuration .sb-config-field-' + itemName);
    if (isVisible) {
      items.removeClass('sb-config-field-hidden');
    } else {
      items.addClass('sb-config-field-hidden');
    }
  }

  /**
   * Toggle visibility of a cache purge button on or off.
   *
   * @param {string} action - The action of the button.
   * @param {boolean} isVisible - Whether the button should be visible or not.
   */
  function sbToggleCachePurgeButtons(action, isVisible)
  {
    var items = $('#sb-configuration .sb-button.sb-' + action);
    if (isVisible) {
      items.removeClass('sb-button-hidden');
    } else {
      items.addClass('sb-button-hidden');
    }
  }

  /**
   * Toggle visibility of a WP Admin bar menu item.
   *
   * @param {string} action - The action of the item.
   * @param {boolean} isVisible - Whether the item should be visible or not.
   */
  function sbToggleWpAdminBarButtons(action, isVisible)
  {
    var items = $('#wpadminbar .sb-' + action);
    if (isVisible) {
      items.removeClass('sb-button-hidden');
    } else {
      items.addClass('sb-button-hidden');
    }
  }

  /**
   * Toggle cache purge-related form elements to show/hide based on active state.
   *
   * @param {boolean} isVisible - Whether the fields should be visible or not.
   */
  function sbToggleCachePurgeFeatureActive(isVisible) {
    sbToggleConfigItemVisibility('general', isVisible);
  }

  function sbToggleCachePurgeDriver(driver) {
    switch (driver) {
    case 'acd':
      sbToggleConfigItemVisibility('acd', true);
      sbToggleConfigItemVisibility('cloudflare', false);
      sbToggleConfigItemVisibility('automatic-purge', true);

      // Purge action triggers
      sbToggleCachePurgeButtons('purge-url', true);
      sbToggleWpAdminBarButtons('purge-url', true);
      sbToggleWpAdminBarButtons('purge-item', true);
      break;
      case 'serveboltcdn':
      sbToggleConfigItemVisibility('acd', true);
      sbToggleConfigItemVisibility('cloudflare', false);
      sbToggleConfigItemVisibility('automatic-purge', false);

      // Purge action triggers
      sbToggleCachePurgeButtons('purge-url', false);
      sbToggleWpAdminBarButtons('purge-url', false);
      sbToggleWpAdminBarButtons('purge-item', false);
      break;
    case 'cloudflare':
      sbToggleConfigItemVisibility('acd', false);
      sbToggleConfigItemVisibility('cloudflare', true);
      sbToggleConfigItemVisibility('automatic-purge', true);

      // Purge action triggers
      sbToggleCachePurgeButtons('purge-url', true);
      sbToggleWpAdminBarButtons('purge-url', true);
      sbToggleWpAdminBarButtons('purge-item', true);
      break;
    }
  }

  /**
   * Toggle CF cron-related form elements to show/hide based on active state.
   *
   * @param boolean
   */
  function sb_toggle_cf_cron_feature_active(boolean) {
    var items = $('#sb-configuration .sb-toggle-active-cron-item');
    if ( boolean ) {
      items.removeClass('cf-hidden-cron');
    } else {
      items.addClass('cf-hidden-cron');
    }
  }

  /**
   * Show/hide available zone container (and populate with html).
   * @param html
   */
  function toggle_available_zones(html) {
    var container = $('#sb-configuration .zone-selector-container');
    if ( html ) {
      container.show();
      container.find('.zone-selector').html(html);
    } else {
      container.hide();
      container.find('.zone-selector').html('');
    }
  }

  /**
   * Fetch available zones and display in list.
   *
   * @param auth_type
   * @param credentials
   */
  function fetch_and_display_available_zones(auth_type, credentials) {
    var spinner = $('#sb-configuration .zone-loading-spinner');
    spinner.addClass('is-active');
    var data = {
      action: 'servebolt_lookup_zones',
      security: servebolt_optimizer_ajax_object.ajax_nonce,
      auth_type: auth_type,
      credentials: credentials
    };
    // TODO: Abort any current requests
    $.ajax({
      type: 'POST',
      url: servebolt_optimizer_ajax_object.ajaxurl,
      data: data,
      success: function (response) {
        spinner.removeClass('is-active');
        toggle_available_zones( response.success ? response.data.markup : false);
      },
      error: function () {
        spinner.removeClass('is-active');
      }
    });
  }

  /**
   * Resolve available zones after changing API credentials.
   *
   * @type {null}
   */
  let api_credentials_zone_lookup_type_wait_timeout = null;
  function sb_lookup_zones() {
    clearTimeout(api_credentials_zone_lookup_type_wait_timeout);
    var auth_type = $('#sb-configuration input[name="servebolt_cf_auth_type"]:checked').val();
    switch (auth_type) {
      case 'api_token':
        var api_token = $('#sb-configuration #sb_cf_api_token').val();
        if ( api_token ) {
          api_credentials_zone_lookup_type_wait_timeout = setTimeout(function () {
            fetch_and_display_available_zones(auth_type, { apiToken: api_token });
          }, 1000);
        } else {
          toggle_available_zones(false);
        }
        break;
      case 'api_key':
        var email = $('#sb-configuration #sb_cf_email').val(),
          api_key = $('#sb-configuration #sb_cf_api_key').val();
        if ( email && api_key ) {
          api_credentials_zone_lookup_type_wait_timeout = setTimeout(function () {
            fetch_and_display_available_zones(auth_type, { email: email, apiKey: api_key });
          }, 1000);
        } else {
          toggle_available_zones(false);
        }
        break;
    }

    sb_resolve_zone_name();
  }

  /**
   * Toggle display of field when selecting authentication type with Cloudflare.
   *
   * @param auth_type
   */
  function sb_toggle_auth_type(auth_type) {
    var api_token_container = $('#sb-configuration .feature_cf_auth_type-api_token'),
        api_key_container = $('#sb-configuration .feature_cf_auth_type-api_key'),
        zone_selector_container = $('#sb-configuration .zone-selector-container');
    switch (auth_type) {
      case 'api_key':
        api_token_container.hide();
        api_key_container.show();
        var email = $('#sb-configuration #sb_cf_email').val(),
          api_key = $('#sb-configuration #sb_cf_api_key').val();
        fetch_and_display_available_zones(auth_type, { email: email, api_key: api_key });
        break;
      case 'api_token':
        api_token_container.show();
        api_key_container.hide();
        zone_selector_container.hide();
        var api_token = $('#sb-configuration #sb_cf_api_token').val();
        fetch_and_display_available_zones(auth_type, { api_token: api_token });
    }
    sb_resolve_zone_name();
  }

  /**
   * Revert hidden fields to original value.
   *
   * @param auth_type
   */
  function sb_cf_revert_values() {
    var auth_type = $('#sb-configuration input[name="servebolt_cf_auth_type"]:checked').val();
    switch (auth_type) {
      case 'api_key':
        var api_token_element = $('#sb-configuration .feature_cf_auth_type-api_token #sb_cf_api_token');
        api_token_element.val(api_token_element.data('original-value'));
        break;
      case 'api_token':
        var api_key_container = $('#sb-configuration .feature_cf_auth_type-api_key'),
          api_key_element = api_key_container.find('#sb_cf_api_key'),
          email_element = api_key_container.find('#sb_cf_email');
        api_key_element.val(api_key_element.data('original-value'));
        email_element.val(email_element.data('original-value'));
        break;
    }
  }

  /**
   * Check if the cache purge queue table is empty or not.
   */
  window.sb_check_for_empty_purge_items_table = function(uncheck_all) {
    var checkboxItems = $('#sb-configuration #purge-items-table input[type="checkbox"]');
    if ( uncheck_all ) {
      checkboxItems.prop('checked', false);
    }
    checkboxItems.first().change();
    var items = $('#sb-configuration #purge-items-table tbody .purge-item'),
      flushButton = $('#sb-configuration .flush-purge-items-queue');
    if ( items.length === 0 ) {
      $('#sb-configuration #purge-items-table .no-items').removeClass('hidden');
      flushButton.prop('disabled', true);
      checkboxItems.prop('disabled', true);
    } else {
      flushButton.prop('disabled', false);
      checkboxItems.prop('disabled', false);
    }
  }

  /**
   * Hide all validation error indicators from form field.
   */
  function clear_validation_errors() {
    $('#sb-configuration .validate-field').each(function(i, el) {
      var el = $(el);
      el.removeClass('invalid');
      el.parent().find('.invalid-message').text('').hide();
    });
  }

  /**
   * Display validation error indicators from form field.
   *
   * @param errors
   */
  function indicate_validation_errors(errors) {
    for (var key in errors) {
      if ( ! $.isNumeric(key) ) {
        var value = errors.hasOwnProperty(key) ? errors[key] : false,
          input_elements = $('#sb-configuration').find('.validation-group-' + key),
          input_element = $('#sb-configuration').find('.validation-input-' + key);
        input_element.addClass('invalid');
        input_element.parent().find('.invalid-message').text(value).show();
        $.each(input_elements, function (i, el) {
          var el = $(el);
          el.addClass('invalid');
          var is_last_item = (i == (input_elements.length - 1));
          if ( is_last_item ) {
            el.parent().find('.invalid-message').text(value).show();
          }
        })

      }
    }
  }

  /**
   * Validate the form before submitting.
   *
   * @returns {boolean}
   */
  window.sb_validate_cf_configuration_form_validation_is_running = false;
  window.sb_validate_cf_configuration_form_can_submit = false;
  window.sb_validate_cf_configuration_form = function(event) {
    if ( window.sb_validate_cf_configuration_form_can_submit ) return; // Allow submission
    event.preventDefault(); // Prevent form from submitting
    if ( window.sb_validate_cf_configuration_form_validation_is_running ) return; // Validation is already running
    window.sb_validate_cf_configuration_form_validation_is_running = true;
    var spinner = $('#sb-configuration .form-submit-spinner'),
      form = $('#sb-configuration-form'),
      data = {
        action: 'servebolt_validate_cf_settings_form',
        security: servebolt_optimizer_ajax_object.ajax_nonce,
        form: form.serialize(),
      };
    form.find('input[type="submit"]').prop('disabled', true);
    spinner.addClass('is-active');
    $.ajax({
      type: 'POST',
      url: servebolt_optimizer_ajax_object.ajaxurl,
      data: data,
      success: function (response) {
        clear_validation_errors();
        if ( response.success ) {
          sb_cf_revert_values(); // Revert hidden fields back to original value
          window.sb_validate_cf_configuration_form_can_submit = true;
          form.submit();
        } else {
          window.sb_validate_cf_configuration_form_can_submit = false;
          window.sb_validate_cf_configuration_form_validation_is_running = false;
          spinner.removeClass('is-active');
          indicate_validation_errors(response.data.errors);
          window.sb_warning('Ouch! Validation failed :(', null, response.data.error_html);
          form.find('input[type="submit"]').prop('disabled', false);
        }
      },
      error: function() {
        clear_validation_errors();
        window.sb_validate_cf_configuration_form_can_submit = false;
        window.sb_validate_cf_configuration_form_validation_is_running = false;
        spinner.removeClass('is-active');
        window.sb_warning('Ouch!', 'An unkown error occurred. Please try again or contact support.');
        form.find('input[type="submit"]').prop('disabled', false);
      }
    });
  }

  /**
   * Remove a single item form the cache purge queue.
   *
   * @param obj
   */
  function remove_purge_item(obj) {
    if (window.sb_use_native_js_fallback()) {
      if (window.confirm('Are you sure?' + "\n" + 'Do you really want to remove the item?')) {
        remove_purge_item_confirmed(obj);
      }
    } else {
      Swal.fire({
        title: 'Are you sure?',
        text: 'Do you really want to remove the item?',
        icon: 'warning',
        showCancelButton: true,
        customClass: {
          confirmButton: 'servebolt-button yellow',
          cancelButton: 'servebolt-button light'
        },
        buttonsStyling: false
      }).then((result) => {
        if (result.value) {
          remove_purge_item_confirmed(obj);
        }
      });
    }
  }

  /**
   * Confirm callback for function "remove_purge_item".
   */
  function remove_purge_item_confirmed(obj) {
    var item = $(obj).closest('.purge-item'),
        item_value = item.find('.purge-item-input').val();
    delete_cache_purge_queue_items([item_value], function() {
      item.remove();
      window.sb_success('All good!', 'The item was deleted.');
      window.sb_check_for_empty_purge_items_table(false);
    });
  }

  /**
   * Remove selected purge items from purge queue.
   */
  function remove_selected_purge_items() {
    if (window.sb_use_native_js_fallback()) {
      if (window.confirm('Are you sure?' + "\n" + 'Do you really want remove the selected items?')) {
        remove_selected_purge_items_confirmed();
      }
    } else {
      Swal.fire({
        title: 'Are you sure?',
        text: 'Do you really want remove the selected items?',
        icon: 'warning',
        showCancelButton: true,
        customClass: {
          confirmButton: 'servebolt-button yellow',
          cancelButton: 'servebolt-button light'
        },
        buttonsStyling: false
      }).then((result) => {
        if (result.value) {
          remove_selected_purge_items_confirmed();
        }
      });
    }
  }

  /**
   * Confirm callback for function "remove_selected_purge_items".
   */
  function remove_selected_purge_items_confirmed() {
    var items = $('#sb-configuration #purge-items-table tbody .purge-item input[type="checkbox"]:checked').closest('.purge-item'),
        input_elements = items.find('.purge-item-input'),
        items_to_remove = [];
    input_elements.each(function (i, el) {
      items_to_remove.push($(el).val());
    });
    delete_cache_purge_queue_items(items_to_remove, function () {
      items.remove();
      var response = items_to_remove.length > 1 ? 'The items were deleted.' : 'The item was deleted.';
      window.sb_success('All good!', null, response);
      window.sb_check_for_empty_purge_items_table(true);
    });
  }

  /**
   * Manuel refresh of cache purge queue.
   */
  function refresh_purge_queue() {
    window.update_cache_purge_list();
  }

  /**
   * Flush cache purge queue.
   */
  function flush_purge_queue() {
    if (window.sb_use_native_js_fallback()) {
      if (window.confirm('Are you sure?' + "\n" + 'Do you really want to empty cache purge queue?')) {
        flush_purge_queue_confirmed();
      }
    } else {
      Swal.fire({
        title: 'Are you sure?',
        text: 'Do you really want to empty cache purge queue?',
        icon: 'warning',
        showCancelButton: true,
        customClass: {
          confirmButton: 'servebolt-button yellow',
          cancelButton: 'servebolt-button light'
        },
        buttonsStyling: false
      }).then((result) => {
        if (result.value) {
          flush_purge_queue_confirmed();
        }
      });
    }
  }

  /**
   * Confirm callback for function "flush_purge_queue".
   */
  function flush_purge_queue_confirmed() {
    window.sb_loading(true);
    delete_cache_purge_queue_items('flush', function () {
      $('#sb-configuration #purge-items-table tbody .purge-item').remove();
      window.sb_loading(false);
      window.sb_success('All good!', 'The queue was emptied.');
      window.sb_check_for_empty_purge_items_table(false);
    });
  }

});
