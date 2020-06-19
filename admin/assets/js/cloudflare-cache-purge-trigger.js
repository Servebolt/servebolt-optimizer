jQuery(document).ready(function($) {

  // TODO: Refresh cache purge queue table (if present in DOM) after altering purge queue

  // Purge all cache on all sites in a multisite-network
  $('.sb-purge-network-cache').click(function (e) {
    e.preventDefault();
    sb_close_admin_bar_menu();
    sb_purge_network_cache();
  });

  // Purge all cache
  $('.sb-purge-all-cache').click(function (e) {
    e.preventDefault();
    sb_close_admin_bar_menu();
    sb_purge_all_cache();
  });

  // Purge current post cache
  $('.sb-purge-current-post-cache').click(function (e) {
    e.preventDefault();
    sb_close_admin_bar_menu();
    var post_id = $(this).find('span').data('id');
    sb_purge_post_cache(post_id);
  });

  // Purge URL cache
  $('#sb-configuration .sb-purge-url').click(function(e) {
    e.preventDefault();
    sb_purge_url_cache();
  });

  /**
   * Close the SB Optimizer admin bar.
   */
  function sb_close_admin_bar_menu() {
    $('#wp-admin-bar-servebolt-optimizer').removeClass('hover');
  }

  /**
   * Purge Cloudflare cache on all sites in multisite.
   */
  function sb_purge_network_cache() {
    Swal.fire({
      title: 'Do you want to purge all cache?',
      text: 'This includes all your sites in the network',
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
          action: 'servebolt_purge_network_cache',
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
                var title = window.sb_get_from_response(response, 'title', sb_default_success_title())
                window.sb_popup(response.data.type, title, null, response.data.markup);
              }, 50);
            } else {
              var message = window.sb_get_message_from_response(response);
              if ( message ) {
                sb_cache_purge_error(message);
              } else {
                sb_cache_purge_error(null, false);
              }
            }
          },
          error: function() {
            window.sb_loading(false);
            sb_cache_purge_error(null, false);
          }
        });
      }
    });
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
        window.sb_loading(true);
        var data = {
          action: 'servebolt_purge_all_cache',
          security: ajax_object.ajax_nonce,
        };
        $.ajax({
          type: 'POST',
          url: ajax_object.ajaxurl,
          data: data,
          success: function(response) {
            window.sb_loading(false);
            var title = window.sb_get_from_response(response, 'title'),
                message = window.sb_get_message_from_response(response);
            if ( response.success ) {
              setTimeout(function () {
                sb_cache_purge_success(message, title);
              }, 100);
            } else {
              var type = window.sb_get_from_response(response, 'type');
              if ( type == 'warning' ) {
                sb_cache_purge_warning(message, title);
              } else {
                sb_cache_purge_error(message, false, title);
              }
            }
          },
          error: function() {
            window.sb_loading(false);
            sb_cache_purge_error(null, false);
          }
        });
      }
    });
  }


  /**
   * Clear cache by post ID in Cloudflare.
   */
  function sb_purge_post_cache(post_id) {
    window.sb_loading(true);
    var data = {
      action: 'servebolt_purge_post_cache',
      security: ajax_object.ajax_nonce,
      post_id: post_id,
    };
    $.ajax({
      type: 'POST',
      url: ajax_object.ajaxurl,
      data: data,
      success: function(response) {
        window.sb_loading(false);
        var title = window.sb_get_from_response(response, 'title'),
            message = window.sb_get_message_from_response(response);
        if ( response.success ) {
          setTimeout(function () {
            sb_cache_purge_success(message, title);
          }, 100);
        } else {
          if ( message ) {
            sb_cache_purge_error(message);
          } else {
            sb_cache_purge_error();
          }
        }
      },
      error: function() {
        window.sb_loading(false);
        sb_cache_purge_error();
      }
    });
  }

  /**
   * Clear cache by URL in Cloudflare.
   */
  function sb_purge_url_cache() {
    Swal.fire({
      text: 'Which URL do you wish to purge?',
      input: 'text',
      inputPlaceholder: 'Please use full URL including "http://"',
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
        window.sb_loading(true);
        var data = {
          action: 'servebolt_purge_url_cache',
          security: ajax_object.ajax_nonce,
          url: result.value,
        };
        $.ajax({
          type: 'POST',
          url: ajax_object.ajaxurl,
          data: data,
          success: function(response) {
            window.sb_loading(false);
            var title = window.sb_get_from_response(response, 'title'),
                message = window.sb_get_message_from_response(response);
            if ( response.success ) {
              setTimeout(function () {
                sb_cache_purge_success(message, title);
              }, 100);
            } else {
              if ( message ) {
                sb_cache_purge_error(message);
              } else {
                sb_cache_purge_error();
              }
            }
          },
          error: function() {
            window.sb_loading(false);
            sb_cache_purge_error();
          }
        });
      }
    });
  }

  /**
   * Default success title.
   *
   * @returns {string}
   */
  function sb_default_success_title() {
    return 'All good!';
  }

  /**
   * Display cache purge success.
   *
   * @param message
   * @param title
   */
  function sb_cache_purge_success(message, title) {
    if ( typeof title === 'undefined' || ! title ) title = sb_default_success_title();
    window.sb_success(title, null, message);
  }

  /**
   * Display cache purge warning.
   *
   * @param message
   * @param title
   */
  function sb_cache_purge_warning(message, title) {
    window.sb_warning(title, null, message);
  }

  /**
   * Display cache purge error.
   *
   * @param message
   * @param include_url_message
   * @param title
   */
  function sb_cache_purge_error(message, include_url_message, title) {
    if ( typeof include_url_message === 'undefined' ) include_url_message = true;
    if ( typeof title === 'undefined' || ! title ) title = 'Unknown error';
    var generic_message = 'Something went wrong. Please check that you:<br><ul style="text-align: left;max-width:350px;margin: 20px auto;">' + ( include_url_message ? '<li>- Specified a valid URL</li>' : '' ) + '<li>- Have added valid API credentials</li><li>- Have selected an active zone</li></ul> If the error still persist then please check the error logs and/or contact support.';
    window.sb_error(title, null, ( message ? message : generic_message ));
  }

});
