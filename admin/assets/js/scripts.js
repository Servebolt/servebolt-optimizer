jQuery(document).ready(function($) {

  // Clear all plugin settings
  $('.sb-clear-all-settings').click(function(e) {
    e.preventDefault();
    sb_clear_all_settings();
  });

  // Revert all optimizations
  $('.sb-deoptimize-database').click(function(e) {
    e.preventDefault();
    sb_wreak_havoc();
  });

  // Purge all
  $('.sb-purge-all-cache').click(function(e) {
    e.preventDefault();
    sb_purge_all_cache();
  });

  // Purge URL
  $('.sb-purge-url').click(function(e) {
    e.preventDefault();
    sb_purge_url();
  });

  // Run full optimization
  $('.sb-optimize-now').click(function(e){
    e.preventDefault();
    sb_optimize();
  });

  // Convert specific table
  $('.sb-convert-table').click(function(e){
    e.preventDefault();
    sb_convert_table(this);
  });

  // Create index for specific table
  $('.sb-create-index').click(function(e){
    e.preventDefault();
    sb_create_index(this);
  });

  // Toggle Cloudflare feature active/inactive
  $('#cloudflare_switch').change(function() {
    sb_toggle_cf_feature_active($(this).is(':checked'));
  });

  // Toggle Cloudflare feature active/inactive
  $('#cf_cron_purge').change(function() {
    sb_toggle_cf_cron_feature_active($(this).is(':checked'));
  });

  // Toggle all post types for Nginx cache
  $('#sb-cache_post_type_all').change(function(){
    sb_toggle_all_post_types($(this).is(':checked'));
  });

  // Automatic zone list population when API key credentials are filled into form.
  $('#sb-configuration .validation-group-api_credentials').on('keyup', function() {
    sb_apply_api_credentials();
  });

  // Trigger zone name resolve if we change the API credentials
  $('#sb-configuration #api_token').on('keyup', function() {
    sb_apply_api_token_credentials();
  });

  // Toggle Nginx cache active/inactive
  $('#sb-nginx_cache_switch').change(function(){
    sb_toggle_nginx_cache_switch($(this).is(':checked'));
  });

  // Select purge item
  $('#sb-configuration #purge-items-table input[type="checkbox"]').change(function() {
    var checkboxCount = $('#sb-configuration #purge-items-table input[type="checkbox"]:checked').length,
        itemCount = $('#sb-configuration #purge-items-table tbody .purge-item').length,
        buttons = $('#sb-configuration .remove-selected-purge-items');
    buttons.prop('disabled', (checkboxCount === 0 || itemCount === 0));
  });

  // Flush cache purge queue
  $('#sb-configuration .flush-purge-items-queue').click(function() {
    flush_purge_queue();
  });

  // Remove selected purge items from purge queue
  $('#sb-configuration .remove-selected-purge-items').click(function() {
    remove_selected_purge_items();
  });

  // Remove purge item from purge queue
  $('#sb-configuration .remove-purge-item-from-queue').click(function(e) {
    e.preventDefault();
    remove_purge_item(this);
  });

  // Toggle between API key and API token authentication
  $('#sb-configuration input[name="servebolt_cf_auth_type"]').change(function() {
    sb_toggle_auth_type($(this).val());
  });

  // Try to resolve zone name from zone Id
  $('#sb-configuration input[name="servebolt_cf_zone_id"]').on('keyup change', function() {
    sb_resolve_zone_name(this);
  });

  // Select zone from available zone
  $('#sb-configuration .zone-selector').on('click', 'a', function(e) {
    e.preventDefault();
    var input = $('#sb-configuration input[name="servebolt_cf_zone_id"]');
    input.val( $(this).data('id') );
    sb_set_active_zone($(this).data('name'));
  });

  /**
   * Update the cache purge queue.
   */
  function submit_cache_purge_queue(items, success_function, error_function) {
    setTimeout(function () {
      var form = $('#sb-configuration-form'),
          spinner = $('#sb-configuration .purge-queue-loading-spinner'),
          data = {
            action: 'servebolt_update_cf_cache_purge_queue',
            security: ajax_object.ajax_nonce,
            items: items,
          };
      if ( ! zone_id ) {
        sb_set_active_zone(false);
        return;
      }
      spinner.addClass('is-active');
      $.ajax({
        type: 'POST',
        url: ajaxurl,
        data: data,
        success: function (response) {
          spinner.removeClass('is-active');
          if (response.success) {
            if (success_function) success_function();
          } else {
            sb_warning('Ouch...', 'Could not alter the cache purge queue.');
          }
        },
        error: function () {
          spinner.removeClass('is-active');
          sb_warning('Ouch...', 'Could not alter the cache purge queue.');
        }
      });
    }, 250);
  }

  /**
   * Display success modal.
   *
   * @param title
   * @param html
   */
  function sb_success(title, html) {
    Swal.fire({
      icon: 'success',
      title: title,
      html: html,
      customClass: {
        confirmButton: 'servebolt-button yellow'
      },
      buttonsStyling: false
    });
  }

  /**
   * Display warning modal.
   *
   * @param title
   * @param html
   */
  function sb_warning(title, html) {
    Swal.fire({
      icon: 'warning',
      title: title,
      html: html,
      customClass: {
        confirmButton: 'servebolt-button yellow'
      },
      buttonsStyling: false
    });
  }

  /**
   * Remove a single item form the cache purge queue.
   *
   * @param obj
   */
  function remove_purge_item(obj) {
    Swal.fire({
      title: 'Are you sure?',
      text: 'Do you really want remove the item?',
      icon: 'warning',
      showCancelButton: true,
      customClass: {
        confirmButton: 'servebolt-button yellow',
        cancelButton: 'servebolt-button light'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.value) {
        var item = $(obj).closest('.purge-item'),
            item_value = item.find('.purge-item-input').val();
        submit_cache_purge_queue([item_value], function() {
          item.remove();
          sb_success('All good!', 'The item was deleted.');
          sb_check_for_empty_purge_items_table(false);
        });
      }
    });
  }

  /**
   * Remove selected purge items from purge queue.
   */
  function remove_selected_purge_items() {
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
        var items = $('#sb-configuration #purge-items-table tbody .purge-item input[type="checkbox"]:checked').closest('.purge-item'),
            input_elements = items.find('.purge-item-input'),
            ids = [];
        input_elements.each(function (i, el) {
          ids.push($(el).val());
        });
        submit_cache_purge_queue(ids, function () {
          items.remove();
          var response = ids.length > 1 ? 'The items were deleted.' : 'The item was deleted.';
          sb_success('All good!', response);
          sb_check_for_empty_purge_items_table(true);
        });
      }
    });
  }

  /**
   * Flush cache purge queue.
   */
  function flush_purge_queue() {
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
        submit_cache_purge_queue('all', function () {
          $('#sb-configuration #purge-items-table tbody .purge-item').remove();
          sb_success('All good!', 'The queue was emptied.');
          sb_check_for_empty_purge_items_table(false);
        });
      }
    });
  }

  /**
   * Insert loader markup.
   */
  function sb_insert_loader_markup() {
    $('<div id="servebolt-loading" class=""><div class="loader loading-ring"></div></div>').insertBefore('.wrap.sb-content');
  }

  // Insert loader markup
  sb_insert_loader_markup();

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
        var api_token = $('#sb-configuration #api_token').val();
        return api_token != '';
        break;
      case 'api_key':
        var email = $('#sb-configuration #email').val(),
            api_key = $('#sb-configuration #api_key').val();
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
  function sb_resolve_zone_name(el) {
    clearTimeout(zone_id_type_wait_timeout);
    if ( ! sb_has_credentials() ) {
      return;
    }
    zone_id_type_wait_timeout = setTimeout(function () {
      var form = $('#sb-configuration-form'),
          zone_id = $('#sb-configuration #zone_id').val(),
          spinner = $('#sb-configuration .zone-loading-spinner'),
          data = {
            action: 'servebolt_lookup_zone',
            security: ajax_object.ajax_nonce,
            form: form.serialize(),
          };
      if ( ! zone_id ) {
        sb_set_active_zone(false);
        return;
      }
      spinner.addClass('is-active');
      $.ajax({
        type: 'POST',
        url: ajaxurl,
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
   * Toggle CF-related form elements to show/hide based on active state.
   *
   * @param boolean
   */
  function sb_toggle_cf_feature_active(boolean) {
    var items = $('#sb-configuration .sb-toggle-active-cf-item');
    if ( boolean ) {
      items.removeClass('cf-hidden');
    } else {
      items.addClass('cf-hidden');
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
   * Apply API credentials from form a try to fetch available zones.
   *
   * @type {null}
   */
  let api_key_type_wait_timeout = null;
  function sb_apply_api_credentials() {
    clearTimeout(api_key_type_wait_timeout);
    var auth_type = $('#sb-configuration input[name="servebolt_cf_auth_type"]:checked').val();
    switch (auth_type) {
      case 'api_token':
        var api_token = $('#sb-configuration #api_token').val();
        if ( api_token ) {
          api_key_type_wait_timeout = setTimeout(function () {
            fetch_and_display_available_zones(auth_type, { api_token: api_token });
          }, 1000);
        }
        break;
      case 'api_key':
        var email = $('#sb-configuration #email').val(),
            api_key = $('#sb-configuration #api_key').val();
        if ( email && api_key ) {
          api_key_type_wait_timeout = setTimeout(function () {
            fetch_and_display_available_zones(auth_type, { email: email, api_key: api_key });
          }, 1000);
        }
        break;
    }
  }


  /**
   * Fetch available zones and diplay in list.
   *
   * @param email
   * @param api_key
   */
  function fetch_and_display_available_zones(auth_type, credentials) {
    maybe_trigger_zone_id_resolve();
    var spinner = $('#sb-configuration .zone-loading-spinner');
    spinner.addClass('is-active');
    var data = {
      action: 'servebolt_lookup_zones',
      security: ajax_object.ajax_nonce,
      auth_type: auth_type,
      credentials: credentials
    };
    $.ajax({
      type: 'POST',
      url: ajaxurl,
      data: data,
      success: function (response) {
        spinner.removeClass('is-active');
        var container = $('#sb-configuration .zone-selector-container');
        if ( response.success ) {
          container.show();
          container.find('.zone-selector').html(response.data.markup);
        } else {
          container.hide();
          container.find('.zone-selector').html('');
        }
      },
      error: function () {
        spinner.removeClass('is-active');
      }
    });
  }

  /**
   * Wait for credentials to be typed and then try to resolve any existing zone Id.
   *
   * @type {null}
   */
  let api_token_type_wait_timeout = null;
  function sb_apply_api_token_credentials() {
    clearTimeout(api_token_type_wait_timeout);
    var auth_type = $('#sb-configuration input[name="servebolt_cf_auth_type"]:checked').val(),
        api_token = $('#sb-configuration #api_token').val();
    if (auth_type === 'api_token' && api_token) {
      api_token_type_wait_timeout = setTimeout(function () {
        maybe_trigger_zone_id_resolve();
      }, 1000);
    }
  }

  /**
   * Check if we should trigger zone Id resolve.
   */
  function maybe_trigger_zone_id_resolve() {
    var zone_id = $('#sb-configuration #zone_id').val();
    if ( zone_id ) {
      $('#sb-configuration #zone_id').trigger('change');
    }
  }

  /**
   * Toggle select/deselect all post types.
   *
   * @param boolean
   */
  function sb_toggle_all_post_types(boolean) {
    $('.servebolt_fpc_settings_item').not('#sb-cache_post_type_all').each(function (i, el) {
      var item = $(el).closest('span');
      if ( boolean ) {
        item.addClass('disabled');
      } else {
        item.removeClass('disabled');
      }
    });
  }

  /**
   * Toggle whether Nginx post type settings should be displayed or not.
   *
   * @param boolean
   */
  function sb_toggle_nginx_cache_switch(boolean) {
    var form = $('#post-types-form');
    if ( boolean ) {
      form.show();
    } else {
      form.hide();
    }
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
        var email = $('#sb-configuration #email').val(),
            api_key = $('#sb-configuration #api_key').val();
        fetch_and_display_available_zones(auth_type, { email: email, api_key: api_key });
        break;
      case 'api_token':
        api_token_container.show();
        api_key_container.hide();
        zone_selector_container.hide();
        maybe_trigger_zone_id_resolve();
        var api_token = $('#sb-configuration #api_token').val();
        fetch_and_display_available_zones(auth_type, { api_token: api_token });
    }
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
        var api_token_element = $('#sb-configuration .feature_cf_auth_type-api_token #api_token');
        api_token_element.val(api_token_element.data('original-value'));
        break;
      case 'api_token':
        var api_key_container = $('#sb-configuration .feature_cf_auth_type-api_key'),
            api_key_element = api_key_container.find('#api_key'),
          email_element = api_key_container.find('#email');
        api_key_element.val(api_key_element.data('original-value'));
        email_element.val(email_element.data('original-value'));
        break;
    }
  }

  /**
   * Check if the cache purge queue table is empty or not.
   */
  function sb_check_for_empty_purge_items_table(uncheck_all) {
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
  window.sb_validate_cf_configuration_form_is_running = false;
  window.sb_validate_cf_configuration_form = function(event) {
    if ( window.sb_validate_cf_configuration_form_is_running ) event.preventDefault();
    window.sb_validate_cf_configuration_form_is_running = true;
    var spinner = $('#sb-configuration .form-submit-spinner'),
        form = $('#sb-configuration-form'),
        data = {
          action: 'servebolt_validate_cf_settings',
          security: ajax_object.ajax_nonce,
          form: form.serialize(),
        };
    spinner.addClass('is-active');
    $.ajax({
      type: 'POST',
      url: ajaxurl,
      //async: false,
      data: data,
      success: function (response) {
        clear_validation_errors();
        if ( response.success ) {
          sb_cf_revert_values(); // Revert hidden fields back to original value
          form.removeAttr('onsubmit');
          form.submit();
        } else {
          window.sb_validate_cf_configuration_form_is_running = false;
          spinner.removeClass('is-active');
          indicate_validation_errors(response.data.errors);
          sb_warning('Ouch! Validation failed :(', response.data.error_html);
        }
      },
      error: function() {
        clear_validation_errors();
        window.sb_validate_cf_configuration_form_is_running = false;
        spinner.removeClass('is-active');
        sb_warning('Ouch!', 'An unkown error occurred. Please try agin or contact support.');
      }
    });
    return false;
  }

  /**
   * Clear all cache in Cloudflare.
   */
  function sb_purge_all_cache() {
    Swal.fire({
      title: 'Do you want to purge all cache?',
      icon: 'warning',
      showCancelButton: true,
      customClass: {
        confirmButton: 'servebolt-button yellow',
        cancelButton: 'servebolt-button light'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.value) {
        sb_loading(true);
        var data = {
          action: 'servebolt_purge_all_cache',
          security: ajax_object.ajax_nonce,
        };
        $.ajax({
          type: 'POST',
          url: ajaxurl,
          data: data,
          success: function(response) {
            sb_loading(false);
            if ( response.success ) {
              setTimeout(function () {
                sb_success('All good!', 'All cache was purged.');
              }, 100);
            } else {
              var message = sb_get_message_from_response(response);
              if ( message ) {
                sb_cache_purge_error(message);
              } else {
                sb_cache_purge_error(null, false);
              }
            }
          },
          error: function() {
            sb_loading(false);
            sb_cache_purge_error(null, false);
          }
        });
      }
    });
  }

  /**
   * Clear all cache in Cloudflare.
   */
  function sb_purge_url() {
    Swal.fire({
      text: 'Which URL do you wish to purge?',
      input: 'text',
      customClass: {
        confirmButton: 'servebolt-button yellow',
        cancelButton: 'servebolt-button light'
      },
      buttonsStyling: false,
      inputValidator: (value) => {
        if (!value) {
          return 'Please enter a URL.'
        }
      },
      showCancelButton: true
    }).then((result) => {
      if (result.value) {
        sb_loading(true);
        var data = {
          action: 'servebolt_purge_url',
          security: ajax_object.ajax_nonce,
          url: result.value,
        };
        $.ajax({
          type: 'POST',
          url: ajaxurl,
          data: data,
          success: function(response) {
            sb_loading(false);
            if ( response.success ) {
              setTimeout(function () {
                sb_success('All good!', 'The cache was purged.');
              }, 100);
            } else {
              var message = sb_get_message_from_response(response);
              if ( message ) {
                sb_cache_purge_error(message);
              } else {
                sb_cache_purge_error();
              }
            }
          },
          error: function() {
            sb_loading(false);
            sb_cache_purge_error();
          }
        });
      }
    });

  }

  /**
   * Get message from jQuery AJAX response object.
   *
   * @param response
   * @returns {*}
   */
  function sb_get_message_from_response(response) {
    if ( typeof response.data !== 'undefined' && typeof response.data.message !== 'undefined' ) {
      return response.data.message;
    }
    return false;
  }

  /**
   * Display cache purge error.
   */
  function sb_cache_purge_error(message, include_url_message) {
    if ( typeof include_url_message === 'undefined' ) include_url_message = true;
    var generic_message = 'Something went wrong. Please check that you:<br><ul style="text-align: left;max-width:350px;margin: 20px auto;">' + ( include_url_message ? '<li>- Specified a valid URL</li>' : '' ) + '<li>- Have added valid API credentials</li><li>- Have selected an active zone</li></ul> If the error still persist then please check the error logs and/or contact support.';
    Swal.fire({
      icon: 'error',
      title: 'Unknown error',
      html: message ? message : generic_message,
      customClass: {
        confirmButton: 'servebolt-button yellow'
      },
      buttonsStyling: false
    });
  }

  /**
   * Convert a table to InnoDB.
   */
  function sb_convert_table(element) {
    sb_loading(true);
    var data = {
      action: 'servebolt_convert_table_to_innodb',
      table_name: $(element).data('table'),
      security: ajax_object.ajax_nonce,
    };
    $.ajax({
      type: 'POST',
      url: ajaxurl,
      data: data,
      success: function(response) {
        sb_loading(false);
        if ( response.success ) {
          var message = sb_get_message_from_response(response);
          setTimeout(function () {
            Swal.fire({
              icon: 'success',
              title: 'All good!',
              text: message,
              customClass: {
                confirmButton: 'servebolt-button yellow'
              },
              buttonsStyling: false
            }).then(function () {
              location.reload();
            });
          }, 100);
        } else {
          sb_optimization_error()
        }
      },
      error: function() {
        sb_loading(false);
        sb_optimization_error();
      }
    });
  }

  /**
   * Run full optimization.
   */
  function sb_optimize() {
    sb_loading(true);
    var data = {
      action: 'servebolt_optimize_db',
      security: ajax_object.ajax_nonce,
    };
    $.ajax({
      type: 'POST',
      url: ajaxurl,
      data: data,
      success: function(response) {
        sb_loading(false);
        if ( response.success ) {
          setTimeout(function () {
            var message = response.data.message;
            if ( response.data.tasks ) {
              message += '<br><br>What was done:<br><ul>';
              $.each(response.data.tasks, function(i, task) {
                message += '<li>' + task + '</li>';
              });
              message += '</ul>';
            }
            Swal.fire({
              width: 800,
              icon: 'success',
              title: 'All good!',
              html: message,
              customClass: {
                confirmButton: 'servebolt-button yellow'
              },
              buttonsStyling: false
            }).then(function () {
              location.reload();
            });
          }, 100);
        } else {
          sb_optimization_error()
        }
      },
      error: function() {
        sb_loading(false);
        sb_optimization_error();
      }
    });
  }

  /**
   * Create index on table.
   */
  function sb_create_index(element) {
    sb_loading(true);
    var data = {
      action: 'servebolt_create_index',
      table_name: $(element).data('table'),
      blog_id: $(element).data('blog-id'),
      security: ajax_object.ajax_nonce,
    };
    $.ajax({
      type: 'POST',
      url: ajaxurl,
      data: data,
      success: function(response) {
        sb_loading(false);
        if ( response.success ) {
          setTimeout(function () {
            Swal.fire({
              icon: 'success',
              title: 'All good!',
              text: response.data.message,
              customClass: {
                confirmButton: 'servebolt-button yellow'
              },
              buttonsStyling: false
            }).then(function () {
              location.reload();
            });
          }, 100);
        } else {
          sb_optimization_error()
        }
      },
      error: function() {
        sb_loading(false);
        sb_optimization_error();
      }
    });
  }

  /**
   * Clear all plugin settings.
   */
  function sb_clear_all_settings() {
    if ( ! confirm('Warning: this will clear all settings and essentially reset the whole plugin. You want to proceed?') ) {
      return;
    }
    if ( ! confirm('Last warning? You really want to proceed?') ) {
      return;
    }
    sb_loading(true);
    var data = {
      action: 'servebolt_clear_all_settings',
      security: ajax_object.ajax_nonce,
    };
    $.ajax({
      type: 'POST',
      url: ajaxurl,
      data: data,
      success: function (response) {
        sb_loading(false);
        setTimeout(function () {
          Swal.fire({
            icon: 'success',
            title: 'Done!',
            customClass: {
              confirmButton: 'servebolt-button yellow'
            },
            buttonsStyling: false
          }).then(function () {
            location.reload();
          });
        }, 100);
      }
    });
  }

  /**
   * Debug function to remove indexes and change DB engine.
   */
  function sb_wreak_havoc() {
    if ( ! confirm('WARNING: This functionality is added for development purposes and will remove indexes and convert table engines to MyISAM. This is not something you really want unless you are debugging/developing. Do you want to proceed?') ) {
      return
    }
    if ( ! confirm('Last warning? You really want to proceed?') ) {
      return;
    }
    sb_loading(true);
    var data = {
        action: 'servebolt_wreak_havoc',
        security: ajax_object.ajax_nonce,
    };
    $.ajax({
      type: 'POST',
      url: ajaxurl,
      data: data,
      success: function (response) {
        sb_loading(false);
        setTimeout(function () {
          alert('Done!');
          location.reload();
        }, 100);
      }
    });
  }

  /**
   * Show/hide loading overlay.
   *
   * @param bool
   */
  function sb_loading(bool) {
    var element = $('#servebolt-loading');
    if ( bool ) {
      element.addClass('active');
    } else {
      element.removeClass('active');
    }
  }

  /**
   * Display general error message.
   */
  function sb_optimization_error() {
    Swal.fire({
      icon: 'error',
      title: 'Ouch...',
      text: 'Something went wrong, please check the error logs or contact the administrator.',
      customClass: {
        confirmButton: 'servebolt-button yellow'
      },
      buttonsStyling: false
    })
  }

});
