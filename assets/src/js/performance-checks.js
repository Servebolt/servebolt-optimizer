jQuery(document).ready(function($) {

  // Clear all plugin settings
  $('.sb-content .sb-clear-all-settings').click(function(e) {
    e.preventDefault();
    sb_clear_all_settings();
  });

  // Revert all optimizations
  $('.sb-content .sb-deoptimize-database').click(function(e) {
    e.preventDefault();
    sb_deoptimize();
  });

  // Run full optimization
  $('.sb-content .sb-optimize-now').click(function(e){
    e.preventDefault();
    sb_optimize();
  });

  // Convert specific table
  $('.sb-content .sb-convert-table').click(function(e){
    e.preventDefault();
    sb_convert_table(this);
  });

  // Create index for specific table
  $('.sb-content .sb-create-index').click(function(e){
    e.preventDefault();
    sb_create_index(this);
  });

  /**
   * Run full optimization.
   */
  function sb_optimize() {
    if (window.sb_use_native_js_fallback()) {
      if (window.confirm('Are you sure?' + "\n" + 'This will add any missing indexes and convert all tables to use modern storage engines.')) {
        sb_optimize_confirmed();
      }
    } else {
      Swal.fire({
        title: 'Are you sure?',
        html: 'This will add any missing indexes and convert all tables to use modern storage engines.',
        icon: 'warning',
        showCancelButton: true,
        customClass: {
          confirmButton: 'servebolt-button yellow',
          cancelButton: 'servebolt-button light'
        },
        buttonsStyling: false
      }).then((result) => {
        if ( result.value ) {
          sb_optimize_confirmed();
        }
      });
    }
  }

  /**
   * Confirm callback for function "sb_optimize".
   */
  function sb_optimize_confirmed() {
    window.sb_loading(true);
    var data = {
      action: 'servebolt_optimize_db',
      security: sb_ajax_object.ajax_nonce,
    };
    $.ajax({
      type: 'POST',
      url: sb_ajax_object.ajaxurl,
      data: data,
      success: function (response) {
        window.sb_loading(false);
        if (response.success) {
          setTimeout(function () {
            sb_optimize_confirmed_2(response);
          }, 100);
        } else {
          sb_optimization_error()
        }
      },
      error: function () {
        window.sb_loading(false);
        sb_optimization_error();
      }
    });
  }

  /**
   * Confirm callback for function "sb_optimize_confirmed".
   */
  function sb_optimize_confirmed_2(response) {
    if ( window.sb_use_native_js_fallback() ) {
      var message = window.sb_strip(response.data.message, true);
      if (response.data.tasks) {
        message += "\n\n" + 'What was done:';
        $.each(response.data.tasks, function (i, task) {
          message += "\n" + '- ' + window.sb_strip(task);
        });
      }
      window.alert('All good!' + message);
      location.reload();
    } else {
      var message = response.data.message;
      if (response.data.tasks) {
        message += '<br><br>What was done:<br><ul>';
        $.each(response.data.tasks, function (i, task) {
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
    }
  }

  /**
   * Debug function to remove indexes and change DB engine.
   */
  function sb_deoptimize() {
    if (window.sb_use_native_js_fallback()) {
      if (window.confirm('Are you sure?' + "\n" + 'WARNING: This functionality is added for development purposes and will remove indexes and convert table engines to MyISAM. This is not something you really want unless you are debugging/developing. Do you want to proceed?')) {
        if (window.confirm('Are you sure?' + "\n" + 'Last warning? You really want to proceed?')) {
          sb_deoptimize_confirmed();
        }
      }
    } else {
      Swal.fire({
        title: 'Are you sure?',
        html: 'WARNING: This functionality is added for development purposes and will remove indexes and convert table engines to MyISAM. This is <strong>not something you really want</strong> unless you are debugging/developing. Do you want to proceed?',
        icon: 'warning',
        showCancelButton: true,
        customClass: {
          confirmButton: 'servebolt-button yellow',
          cancelButton: 'servebolt-button light'
        },
        buttonsStyling: false
      }).then((result) => {
        if (result.value) {
          Swal.fire({
            title: 'Are you sure?',
            text: 'Last warning? You really want to proceed?',
            icon: 'warning',
            showCancelButton: true,
            customClass: {
              confirmButton: 'servebolt-button yellow',
              cancelButton: 'servebolt-button light'
            },
            buttonsStyling: false
          }).then((result) => {
            if ( result.value ) {
              sb_deoptimize_confirmed();
            }
          });
        }
      });
    }
  }

  /**
   * Success callback for function "sb_deoptimize_confirmed".
   */
  function sb_deoptimize_confirmed_success() {
    if ( window.sb_use_native_js_fallback() ) {
      window.alert('All good!');
      location.reload();
    } else {
      Swal.fire({
        icon: 'success',
        title: 'All good!',
        customClass: {
          confirmButton: 'servebolt-button yellow'
        },
        buttonsStyling: false
      }).then(function () {
        location.reload();
      });
    }
  }

  /**
   * Error callback for function "sb_deoptimize_confirmed".
   */
  function sb_deoptimize_confirmed_error() {
    if ( window.sb_use_native_js_fallback() ) {
      window.alert('Ouch, we got an error' + "\n" + 'Unknown error');
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Ouch, we got an error',
        text: 'Unknown error',
        customClass: {
          confirmButton: 'servebolt-button yellow'
        },
        buttonsStyling: false
      });
    }
  }

  /**
   * Confirm callback for function "sb_deoptimize".
   */
  function sb_deoptimize_confirmed() {
    window.sb_loading(true);
    var data = {
      action: 'servebolt_wreak_havoc',
      security: sb_ajax_object.ajax_nonce,
    };
    $.ajax({
      type: 'POST',
      url: sb_ajax_object.ajaxurl,
      data: data,
      success: function (response) {
        window.sb_loading(false);
        setTimeout(function () {
          sb_deoptimize_confirmed_success();
        }, 100);
      },
      error: function() {
        window.sb_loading(false);
        sb_deoptimize_confirmed_error();
      }
    });
  }

  /**
   * Convert a table to InnoDB.
   *
   * @param element
   */
  function sb_convert_table(element) {
    if (window.sb_use_native_js_fallback()) {
      if (window.confirm('Are you sure?' + "\n" + 'Do you really want to convert the table?')) {
        sb_convert_table_confirmed(element);
      }
    } else {
      Swal.fire({
        title: 'Are you sure?',
        text: 'Do you really want to convert the table?',
        icon: 'warning',
        showCancelButton: true,
        customClass: {
          confirmButton: 'servebolt-button yellow',
          cancelButton: 'servebolt-button light'
        },
        buttonsStyling: false
      }).then((result) => {
        if (result.value) {
          sb_convert_table_confirmed(element);
        }
      });
    }
  }

  /**
   * Success callback for function "sb_convert_table_confirmed".
   *
   * @param message
   */
  function sb_convert_table_confirmed_success(message) {
    if (window.sb_use_native_js_fallback()) {
      window.alert('All good!' + "\n" + window.sb_strip(message, true));
      location.reload();
    } else {
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
    }
  }

  /**
   * Confirm callback for function "sb_convert_table".
   *
   * @param element
   */
  function sb_convert_table_confirmed(element) {
    window.sb_loading(true);
    var data = {
      action: 'servebolt_convert_table_to_innodb',
      table_name: $(element).data('table'),
      security: sb_ajax_object.ajax_nonce,
    };
    $.ajax({
      type: 'POST',
      url: sb_ajax_object.ajaxurl,
      data: data,
      success: function(response) {
        window.sb_loading(false);
        if ( response.success ) {
          var message = window.sb_get_message_from_response(response);
          setTimeout(function () {
            sb_convert_table_confirmed_success(message);
          }, 100);
        } else {
          sb_optimization_error()
        }
      },
      error: function() {
        window.sb_loading(false);
        sb_optimization_error();
      }
    });
  }

  /**
   * Create index on table.
   *
   * @param element
   */
  function sb_create_index(element) {
    if (window.sb_use_native_js_fallback()) {
      if (window.confirm('Are you sure?' + "\n" + 'Do you really want to create index?')) {
        sb_create_index_confirmed(element);
      }
    } else {
      Swal.fire({
        title: 'Are you sure?',
        text: 'Do you really want to create index?',
        icon: 'warning',
        showCancelButton: true,
        customClass: {
          confirmButton: 'servebolt-button yellow',
          cancelButton: 'servebolt-button light'
        },
        buttonsStyling: false
      }).then((result) => {
        if ( result.value ) {
          sb_create_index_confirmed(element);
        }
      });
    }
  }

  /**
   * Success callback for function "sb_create_index_confirmed".
   *
   * @param message
   */
  function sb_create_index_confirmed_success(message) {
    if ( window.sb_use_native_js_fallback() ) {
      window.alert('All good!' + "\n" + window.sb_strip(message, true));
      location.reload();
    } else {
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
    }
  }

  /**
   * Confirm callback for function "sb_create_index".
   *
   * @param element
   */
  function sb_create_index_confirmed(element) {
    window.sb_loading(true);
    var data = {
      action: 'servebolt_create_index',
      table_name: $(element).data('table'),
      blog_id: $(element).data('blog-id'),
      security: sb_ajax_object.ajax_nonce,
    };
    $.ajax({
      type: 'POST',
      url: sb_ajax_object.ajaxurl,
      data: data,
      success: function(response) {
        window.sb_loading(false);
        if ( response.success ) {
          setTimeout(function () {
            sb_create_index_confirmed_success(response.data.message)
          }, 100);
        } else {
          sb_optimization_error()
        }
      },
      error: function() {
        window.sb_loading(false);
        sb_optimization_error();
      }
    });
  }

  /**
   * Clear all plugin settings.
   */
  function sb_clear_all_settings() {
    if (window.sb_use_native_js_fallback()) {
      if (window.confirm('Are you sure?' + "\n" + 'Warning: this will clear all settings and essentially reset the whole plugin. You want to proceed?')) {
        if (window.confirm('Last warning' + "\n" + 'Do you really want to proceed?')) {
          sb_clear_all_settings_confirmed();
        }
      }
    } else {
      Swal.fire({
        title: 'Are you sure?',
        text: 'Warning: this will clear all settings and essentially reset the whole plugin. You want to proceed?',
        icon: 'warning',
        showCancelButton: true,
        customClass: {
          confirmButton: 'servebolt-button yellow',
          cancelButton: 'servebolt-button light'
        },
        buttonsStyling: false
      }).then((result) => {
        if ( result.value ) {
          Swal.fire({
            title: 'Last warning',
            text: 'Do you really want to proceed?',
            icon: 'warning',
            showCancelButton: true,
            customClass: {
              confirmButton: 'servebolt-button yellow',
              cancelButton: 'servebolt-button light'
            },
            buttonsStyling: false
          }).then((result) => {
            if ( result.value ) {
              sb_clear_all_settings_confirmed();
            }
          });
        }
      });
    }
  }

  /**
   * Confirm callback for function "sb_clear_all_settings".
   */
  function sb_clear_all_settings_confirmed() {
    window.sb_loading(true);
    var data = {
      action: 'servebolt_clear_all_settings',
      security: sb_ajax_object.ajax_nonce,
    };
    $.ajax({
      type: 'POST',
      url: sb_ajax_object.ajaxurl,
      data: data,
      success: function (response) {
        window.sb_loading(false);
        setTimeout(function () {
          sb_clear_all_settings_confirmed_success();
        }, 100);
      }
    });
  }

  /**
   * Success callback for function "sb_clear_all_settings_confirmed".
   */
  function sb_clear_all_settings_confirmed_success() {
    if ( window.sb_use_native_js_fallback() ) {
      window.alert('Done!');
      location.reload();
    } else {
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
    }
  }

  /**
   * Display general error message.
   */
  function sb_optimization_error() {
    window.sb_error('Ouch...', 'Something went wrong, please check the error logs or contact the administrator.');
  }

});
