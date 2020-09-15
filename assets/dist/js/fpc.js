jQuery(document).ready(function($) {

  // Toggle Nginx cache active/inactive
  $('.sb-content #sb-nginx_cache_switch').change(function(){
    sb_toggle_nginx_cache_switch($(this).is(':checked'));
  });

  // Toggle all post types for Nginx cache
  $('.sb-content #sb-cache_post_type_all').change(function(){
    sb_toggle_all_post_types($(this).is(':checked'));
  });

  // Add post to FPC post exclude list
  $('#sb-nginx-fpc-form .sb-add-exclude-post').click(function() {
    sb_add_posts_to_fpc_exclude();
  });

  // Remove exclude item from FPC
  $('#sb-nginx-fpc-form').on('click', '.sb-remove-item-from-fpc-post-exclude', function(e) {
    e.preventDefault();
    sb_remove_exclude_item(this);
  });

  // Select exclude item
  $('#sb-nginx-fpc-form #nginx-fpc-ids-to-exclude-table').on('change', 'input[type="checkbox"]', function() {
    var table =  $('#sb-nginx-fpc-form #nginx-fpc-ids-to-exclude-table'),
        checkboxCount = table.find('input[type="checkbox"]:checked').length,
        itemCount = table.find('tbody .exclude-item').length,
        buttons = $('#sb-nginx-fpc-form .sb-remove-selected-exclude-items');
    buttons.prop('disabled', (checkboxCount === 0 || itemCount === 0));
  });

  // Remove selected exclude items from FPC post exclude list
  $('#sb-nginx-fpc-form .sb-remove-selected-exclude-items').click(function() {
    sb_remove_selected_exclude_items();
  });

  // Flush FPC post exclude list
  $('#sb-nginx-fpc-form .sb-flush-fpc-exclude-items').click(function() {
    sb_flush_fpc_exclude_list();
  });

  /**
   * Remove post from FPC exclude list.
   *
   * @param obj
   */
  function sb_remove_exclude_item(obj) {
    if ( window.sb_use_native_js_fallback() ) {
      if ( confirm('Are you sure?' + "\n" + 'Do you really want to remove the item?') ) {
        sb_remove_exclude_item_confirmed(obj);
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
          sb_remove_exclude_item_confirmed(obj);
        }
      });
    }
  }

  /**
   * Confirm callback for function "sb_remove_exclude_item".
   */
  function sb_remove_exclude_item_confirmed() {
    window.sb_loading(true);
    var item = $(obj).closest('.exclude-item'),
        item_value = item.find('.exclude-item-input').val();
    sb_submit_fpc_exclude_list([item_value], function() {
      item.remove();
      window.sb_loading(false);
      window.sb_success('All good!', 'The item was deleted.');
      sb_check_for_empty_fpc_exclude_table(false);
    });
  }

  /**
   * Flush FPC post exclude list.
   */
  function sb_flush_fpc_exclude_list() {
    if ( window.sb_use_native_js_fallback() ) {
      if ( confirm('Are you sure?' + "\n" + 'Do you really want to remove all posts from exclude list?') ) {
        sb_flush_fpc_exclude_list_confirmed();
      }
    } else {
      Swal.fire({
        title: 'Are you sure?',
        text: 'Do you really want to remove all posts from exclude list?',
        icon: 'warning',
        showCancelButton: true,
        customClass: {
          confirmButton: 'servebolt-button yellow',
          cancelButton: 'servebolt-button light'
        },
        buttonsStyling: false
      }).then((result) => {
        if (result.value) {
          sb_flush_fpc_exclude_list_confirmed();
        }
      });
    }
  }

  /**
   * Confirm callback for function "sb_flush_fpc_exclude_list".
   */
  function sb_flush_fpc_exclude_list_confirmed() {
    sb_submit_fpc_exclude_list('all', function () {
      $('#sb-nginx-fpc-form #nginx-fpc-ids-to-exclude-table tbody .exclude-item').remove();
      window.sb_success('All good!', 'The list was emptied.');
      sb_check_for_empty_fpc_exclude_table(false);
    });
  }

  /**
   * Remove selected post from FPC exclude list.
   */
  function sb_remove_selected_exclude_items() {
    if ( window.sb_use_native_js_fallback() ) {
      if ( confirm('Are you sure?' + "\n" + 'Do you really want remove the selected items?') ) {
        sb_remove_selected_exclude_items_confirmed();
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
          sb_remove_selected_exclude_items_confirmed();
        }
      });
    }
  }

  /**
   * Confirm callback for function "sb_remove_selected_exclude_items".
   */
  function sb_remove_selected_exclude_items_confirmed() {
    var items = $('#sb-nginx-fpc-form #nginx-fpc-ids-to-exclude-table tbody .exclude-item input[type="checkbox"]:checked').closest('.exclude-item'),
        input_elements = items.find('.exclude-item-input'),
        ids = [];
    input_elements.each(function (i, el) {
      ids.push($(el).val());
    });
    sb_submit_fpc_exclude_list(ids, function () {
      items.remove();
      var response = ids.length > 1 ? 'The items were deleted.' : 'The item was deleted.';
      window.sb_success('All good!', response);
      sb_check_for_empty_fpc_exclude_table(true);
    });
  }

  /**
   * Add posts to FPC exclude list.
   */
  function sb_add_posts_to_fpc_exclude() {
    if ( window.sb_use_native_js_fallback() ) {
      var value = prompt('Add posts to list' + "\n" + 'Insert the IDs (comma separated) of the post you would like to exclude from the cache');
      if ( ! value ) {
        alert('Please enter a post ID.');
        return;
      }
      sb_add_posts_to_fpc_exclude_confirmed(value);
    } else {
      Swal.fire({
        title: 'Add posts to list',
        text: 'Insert the IDs (comma separated) of the post you would like to exclude from the cache',
        input: 'text',
        customClass: {
          confirmButton: 'servebolt-button yellow',
          cancelButton: 'servebolt-button light'
        },
        buttonsStyling: false,
        inputValidator: (value) => {
          if ( ! value ) {
            return 'Please enter a post ID.'
          }
        },
        showCancelButton: true
      }).then((result) => {
        if ( result.value ) {
          sb_add_posts_to_fpc_exclude_confirmed(result.value);
        }
      });
    }
  }

  /**
   * Prompt callback for function "sb_add_posts_to_fpc_exclude".
   */
  function sb_add_posts_to_fpc_exclude_confirmed(value) {
    window.sb_loading(true);
    var data = {
      action: 'servebolt_fpc_exclude_post',
      security: sb_ajax_object.ajax_nonce,
      post_ids: value,
    };
    $.ajax({
      type: 'POST',
      url: sb_ajax_object.ajaxurl,
      data: data,
      success: function(response) {
        window.sb_loading(false);
        var message = window.sb_get_message_from_response(response),
            type = window.sb_get_from_response(response, 'type'),
            title = window.sb_get_from_response(response, 'title'),
            row_markup = window.sb_get_from_response(response, 'row_markup');
        if ( row_markup ) {
          sb_add_row_to_exclude_list(row_markup);
          setTimeout(function () {
            window.sb_popup(type, title, null, message);
          }, 100);
        } else {
          window.sb_popup(type, title, null, message);
        }
      },
      error: function() {
        window.sb_loading(false);
        window.sb_warning('Ouch...', 'Something went wrong. Please check your data, try again and/or contact support.');
      }
    });
  }

  /**
   * Add row to FPC exclude list table.
   *
   * @param html
   */
  function sb_add_row_to_exclude_list(html) {
    $('#sb-nginx-fpc-form #nginx-fpc-ids-to-exclude-table tbody').append(html);
    sb_check_for_empty_fpc_exclude_table(true);
  }

  /**
   * Update the FPC post exclude list.
   */
  function sb_submit_fpc_exclude_list(items, success_function) {
    setTimeout(function () {
      var spinner = $('#sb-nginx-fpc-form .flush-fpc-exlcude-list-loading-spinner'),
        data = {
          action: 'servebolt_update_fpc_exclude_posts_list',
          security: sb_ajax_object.ajax_nonce,
          items: items,
        };
      spinner.addClass('is-active');
      $.ajax({
        type: 'POST',
        url: sb_ajax_object.ajaxurl,
        data: data,
        success: function (response) {
          spinner.removeClass('is-active');
          if (response.success) {
            if (success_function) success_function();
          } else {
            window.sb_warning('Ouch...', 'Could not alter the post exclude list.');
          }
        },
        error: function () {
          spinner.removeClass('is-active');
          window.sb_warning('Ouch...', 'Could not alter the post exclude list.');
        }
      });
    }, 250);
  }

  /**
   * Check if the FPC exclude list table is empty or not.
   */
  function sb_check_for_empty_fpc_exclude_table(uncheck_all) {
    var table = $('#sb-nginx-fpc-form #nginx-fpc-ids-to-exclude-table'),
        checkboxItems = table.find('input[type="checkbox"]'),
        no_items = table.find('.no-items');
    if ( uncheck_all ) {
      checkboxItems.prop('checked', false);
    }
    checkboxItems.first().change();
    var items = table.find('tbody .exclude-item'),
      flushButton = $('#sb-nginx-fpc-form .sb-flush-fpc-exclude-items');
    if ( items.length === 0 ) {
      no_items.removeClass('hidden');
      flushButton.prop('disabled', true);
      checkboxItems.prop('disabled', true);
    } else {
      no_items.addClass('hidden');
      flushButton.prop('disabled', false);
      checkboxItems.prop('disabled', false);
    }
  }

  /**
   * Toggle whether Nginx post type settings should be displayed or not.
   *
   * @param boolean
   */
  function sb_toggle_nginx_cache_switch(boolean) {
    var form = $('#sb-nginx-fpc-form');
    if ( boolean ) {
      form.show();
    } else {
      form.hide();
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

});
