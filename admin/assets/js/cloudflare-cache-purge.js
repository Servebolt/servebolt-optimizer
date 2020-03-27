jQuery(document).ready(function($) {

  // Purge all cache on all sites in multisite-network
  $('.sb-purge-network-cache').click(function (e) {
    e.preventDefault();
    sb_purge_network_cache();
  });

  // Purge all cache
  $('.sb-purge-all-cache').click(function (e) {
    e.preventDefault();
    sb_purge_all_cache();
  });

  // Purge URL
  $('#sb-configuration .sb-purge-url').click(function(e) {
    e.preventDefault();
    sb_purge_url();
  });

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
          url: ajaxurl,
          data: data,
          success: function(response) {
            window.sb_loading(false);
            if ( response.success ) {
              setTimeout(function () {
                window.sb_popup(response.data.type, response.data.title, null, response.data.markup);
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
          url: ajaxurl,
          data: data,
          success: function(response) {
            window.sb_loading(false);
            if ( response.success ) {
              setTimeout(function () {
                window.sb_success('All good!', 'All cache was purged.');
              }, 100);
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
        window.sb_loading(true);
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
            window.sb_loading(false);
            if ( response.success ) {
              setTimeout(function () {
                window.sb_success('All good!', 'The cache was purged.');
              }, 100);
            } else {
              var message = window.sb_get_message_from_response(response);
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
   * Display cache purge error.
   */
  function sb_cache_purge_error(message, include_url_message) {
    if ( typeof include_url_message === 'undefined' ) include_url_message = true;
    var generic_message = 'Something went wrong. Please check that you:<br><ul style="text-align: left;max-width:350px;margin: 20px auto;">' + ( include_url_message ? '<li>- Specified a valid URL</li>' : '' ) + '<li>- Have added valid API credentials</li><li>- Have selected an active zone</li></ul> If the error still persist then please check the error logs and/or contact support.';
    window.sb_error('Unknown error', null, ( message ? message : generic_message ));
  }

});
