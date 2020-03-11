jQuery(document).ready(function($) {

  $('.wreak-havoc').click(function(e) {
    e.preventDefault();
    wreak_havoc();
  });

  $('.sb-purge-all-cache').click(function(e) {
    e.preventDefault();
    purge_all_cache();
  });

  $('.sb-purge-url').click(function(e) {
    e.preventDefault();
    purge_url();
  });

  $('.optimize-now').click(function(e){
    e.preventDefault();
    optimize()
  });

  $('.convert-table').click(function(e){
    e.preventDefault();
    convert_table();
  });

  $('.create-index').click(function(e){
    e.preventDefault();
    create_index();
  });

  $('#nginx_cache_switch').change(function(){
    var form = $('#post-types-form');
    if ( $(this).is(':checked') ) {
      form.show();
    } else {
      form.hide();
    }
  });

  $('#sb-configuration #purge-items-table input[type="checkbox"]').change(function() {
    var checkboxCount = $('#sb-configuration #purge-items-table input[type="checkbox"]:checked').length,
        itemCount = $('#sb-configuration #purge-items-table tbody .purge-item').length,
        buttons = $('#sb-configuration .remove-selected-purge-items');
    if (checkboxCount === 0 || itemCount === 0) {
      buttons.prop('disabled', true);
    } else {
      buttons.prop('disabled', false);
    }
  });

  $('#sb-configuration .flush-purge-items-queue').click(function() {
    $('#sb-configuration #purge-items-table tbody .purge-item').remove();
    checkForEmptyPurgeItemsTable();
  });

  $('#sb-configuration .remove-selected-purge-items').click(function() {
    $('#sb-configuration #purge-items-table tbody .purge-item input[type="checkbox"]:checked').each(function () {
      $(this).closest('tr').remove();
    });
    checkForEmptyPurgeItemsTable();
  });

  $('#sb-configuration .remove-purge-item-from-queue').click(function(e) {
    e.preventDefault();
    $(this).closest('tr').remove();
    checkForEmptyPurgeItemsTable();
  });

  $('#sb-configuration input[name="servebolt_cf_auth_type"]').change(function() {
    switch ($(this).val()) {
      case 'apiKey':
        $('#sb-configuration .feature_cf_auth_type-apiToken').hide();
        $('#sb-configuration .feature_cf_auth_type-apiKey').show();
        break;
      case 'apiToken':
        $('#sb-configuration .feature_cf_auth_type-apiToken').show();
        $('#sb-configuration .feature_cf_auth_type-apiKey').hide();
        break;
    }
  });

  $('#sb-configuration .zone-selector a').click(function(e) {
    e.preventDefault();
    $('#sb-configuration input[name="servebolt_cf_zone_id"]').val( $(this).data('id') );
  });

  // Insert loader markup
  insert_loader_markup();

  /**
   * Insert loader mnarkup.
   */
  function insert_loader_markup() {
    $('<div id="servebolt-loading" class=""><div class="loader loading-ring"></div></div>').insertBefore('.wrap.sb-content');
  }

  function checkForEmptyPurgeItemsTable() {
    var checkboxItems = $('#sb-configuration #purge-items-table input[type="checkbox"]');
    checkboxItems.prop('checked', false);
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
   *
   * @returns {boolean}
   */
  window.sb_validate_configuration_form = function() {



    /*
    var errors = [];

    var auth_type = $('#sb-configuration input[name="cf_auth_type"]').val();



    switch (auth_type) {

    }
    var api_token = $('#sb-configuration #api_token').val();
    var email = $('#sb-configuration #email').val();
    var api_key = $('#sb-configuration #api_key').val();
    var zone_id = $('#sb-configuration #zone_id').val();

    return errors.length === 0;
    */
  }

  /**
   * Clear all cache in Cloudflare.
   */
  function purge_all_cache() {
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
                Swal.fire({
                  icon: 'success',
                  title: 'All good!',
                  text: 'All cache was purged.'
                });
              }, 100);
            } else {
              var message = get_message_from_response(response);
              if ( message ) {
                cache_purge_error(message);
              } else {
                cache_purge_error();
              }
            }
          },
          error: function() {
            sb_loading(false);
            cache_purge_error();
          }
        });
      }
    });
  }

  /**
   * Clear all cache in Cloudflare.
   */
  function purge_url() {
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
                Swal.fire({
                  icon: 'success',
                  title: 'All good!',
                  text: 'The cache was purged.'
                });
              }, 100);
            } else {
              var message = get_message_from_response(response);
              if ( message ) {
                cache_purge_error(message);
              } else {
                cache_purge_error();
              }
            }
          },
          error: function() {
            sb_loading(false);
            cache_purge_error();
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
  function get_message_from_response(response) {
    if ( typeof response.data !== 'undefined' && typeof response.data.message !== 'undefined' ) {
      return response.data.message;
    }
    return false;
  }

  /**
   * Display cache purge error.
   */
  function cache_purge_error(message) {
    Swal.fire({
      icon: 'error',
      title: 'Unknown error',
      html: message ? message : 'Something went wrong. Please check that you:<br><ul style="text-align: left;max-width:360px;margin: 20px auto;"><li>- Specified a valid URL</li><li>- Have added valid API credentials</li><li>- Have selected an active zone</li></ul> If the error still persist then please contact support.'
    });
  }

  /**
   * Convert a table to InnoDB.
   */
  function convert_table() {
    sb_loading(true);
    var data = {
      action: 'servebolt_convert_table_to_innodb',
      table_name: $(this).data('table'),
      security: ajax_object.ajax_nonce,
    };
    $.ajax({
      type: 'POST',
      url: ajaxurl,
      data: data,
      success: function(response) {
        sb_loading(false);
        if ( response.success ) {
          var message = get_message_from_response(response);
          setTimeout(function () {
            Swal.fire({
              icon: 'success',
              title: 'All good!',
              text: message
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
  function optimize() {
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
              html: message
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
  function create_index() {
    sb_loading(true);
    var data = {
      action: 'servebolt_create_index',
      table_name: $(this).data('table'),
      blog_id: $(this).data('blog-id'),
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
              text: response.data.message
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
   * Debug function to remove indexes and change DB engine.
   */
  function wreak_havoc() {
    if ( ! confirm('WARNING: This functionality is added for development purposes and will remove indexes and convert table engines to MyISAM. This is not something you really want unless you are debugging/developing. Do you want to proceed?') ) {
      return
    }
    if ( ! confirm('Last warning? You want to proceed?') ) {
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
      text: 'Something went wrong, please check the error logs or contact the administrator.'
    })
  }

});
