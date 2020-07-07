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
      if (result.value) {
        window.sb_loading(true);
        var data = {
          action: 'servebolt_optimize_db',
          security: ajax_object.ajax_nonce,
        };
        $.ajax({
          type: 'POST',
          url: ajax_object.ajaxurl,
          data: data,
          success: function (response) {
            window.sb_loading(false);
            if (response.success) {
              setTimeout(function () {
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
    });
  }

  /**
   * Debug function to remove indexes and change DB engine.
   */
  function sb_deoptimize() {

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
          if (result.value) {
            window.sb_loading(true);
            var data = {
              action: 'servebolt_wreak_havoc',
              security: ajax_object.ajax_nonce,
            };
            $.ajax({
              type: 'POST',
              url: ajax_object.ajaxurl,
              data: data,
              success: function (response) {
                window.sb_loading(false);
                setTimeout(function () {
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
                }, 100);
              },
              error: function() {
                window.sb_loading(false);
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
            });
          }
        });
      }
    });
  }

  /**
   * Convert a table to InnoDB.
   */
  function sb_convert_table(element) {
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
        window.sb_loading(true);
        var data = {
          action: 'servebolt_convert_table_to_innodb',
          table_name: $(element).data('table'),
          security: ajax_object.ajax_nonce,
        };
        $.ajax({
          type: 'POST',
          url: ajax_object.ajaxurl,
          data: data,
          success: function(response) {
            window.sb_loading(false);
            if ( response.success ) {
              var message = window.sb_get_message_from_response(response);
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
            window.sb_loading(false);
            sb_optimization_error();
          }
        });
      }
    });
  }

  /**
   * Create index on table.
   */
  function sb_create_index(element) {
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
      if (result.value) {
        window.sb_loading(true);
        var data = {
          action: 'servebolt_create_index',
          table_name: $(element).data('table'),
          blog_id: $(element).data('blog-id'),
          security: ajax_object.ajax_nonce,
        };
        $.ajax({
          type: 'POST',
          url: ajax_object.ajaxurl,
          data: data,
          success: function(response) {
            window.sb_loading(false);
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
            window.sb_loading(false);
            sb_optimization_error();
          }
        });
      }
    });
  }

  /**
   * Clear all plugin settings.
   */
  function sb_clear_all_settings() {
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
      if (result.value) {
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
          if (result.value) {
            window.sb_loading(true);
            var data = {
              action: 'servebolt_clear_all_settings',
              security: ajax_object.ajax_nonce,
            };
            $.ajax({
              type: 'POST',
              url: ajax_object.ajaxurl,
              data: data,
              success: function (response) {
                window.sb_loading(false);
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
        });
      }
    });
  }

  /**
   * Display general error message.
   */
  function sb_optimization_error() {
    window.sb_error('Ouch...', 'Something went wrong, please check the error logs or contact the administrator.');
  }

});
