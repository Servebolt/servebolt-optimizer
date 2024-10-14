jQuery(document).ready(function($) {

  // Purge all cache on all sites in a multisite-network
  $('.sb-purge-all-network-cache').click(function (e) {
    e.preventDefault();
    sb_close_admin_bar_menu();
    sb_purge_all_network_cache();
  });

  // Purge all cache
  $('#sb-configuration .sb-purge-all-cache, #wpadminbar .sb-purge-all-cache').click(function (e) {
    e.preventDefault();
    sb_close_admin_bar_menu();
    window.sb_purge_all_cache();
  });

  var list = document.querySelector('#the-list');
  if (list) {

    // Purge post cache
    list.addEventListener('click', function (e) {
      if (!e.target.matches('.sb-purge-post-cache')) return;
      e.preventDefault();
      const postId = $(e.target).data('post-id'),
          objectName = $(e.target).data('object-name');
      window.sbPurgePostCache(postId, objectName);
    }, false);

    // Purge term cache
    list.addEventListener('click', function (e) {
      if (!e.target.matches('.sb-purge-term-cache')) return;
      e.preventDefault();
      const termId = $(e.target).data('term-id'),
          objectName = $(e.target).data('object-name');
      window.sbPurgeTermCache(termId, objectName);
    }, false);
  }

  // Purge current post cache
  $('#wpadminbar .sb-purge-current-post-cache').click(function(e) {
    e.preventDefault();
    sb_close_admin_bar_menu();
    const span = $(this).find('span:not(.servebolt-icon)'),
        postId = span.data('id'),
      objectName = span.data('object-name');
    window.sbPurgePostCache(postId, objectName);
  });

  // Purge current term cache
  $('#wpadminbar .sb-purge-current-term-cache').click(function(e) {
    e.preventDefault();
    sb_close_admin_bar_menu();
    const span = $(this).find('span:not(.servebolt-icon)'),
        termId = span.data('id'),
      objectName = span.data('object-name');
    window.sbPurgeTermCache(termId, objectName);
  });

  // Purge URL cache
  $('#sb-configuration .sb-purge-url, #wpadminbar .sb-purge-url').click(function(e) {
    e.preventDefault();
    window.sb_purge_url_cache();
  });

  /**
   * Close the SB Optimizer admin bar.
   */
  function sb_close_admin_bar_menu() {
    $('#wp-admin-bar-servebolt-optimizer').removeClass('hover');
  }

  /**
   * Update cache purge queue list table.
   */
  window.update_cache_purge_list = function() {
    if ( ! servebolt_optimizer_ajax_object.cron_purge_is_active ) return;
    var table = $('.sb-content #purge-items-table');
    if ( ! table.length ) return;
    var spinner = $('#sb-configuration .purge-queue-loading-spinner');
    spinner.addClass('is-active');
    setTimeout(function () {
      var data = {
        action: 'servebolt_load_cache_purge_queue_list',
        security: servebolt_optimizer_ajax_object.ajax_nonce,
      };
      $.ajax({
        type: 'POST',
        url: servebolt_optimizer_ajax_object.ajaxurl,
        data: data,
        success: function(response) {
          table.html(response.data.html);
          spinner.removeClass('is-active');
          if (typeof window.sb_check_for_empty_purge_items_table == 'function') {
            window.sb_check_for_empty_purge_items_table(false);
          }
        }
      });
    }, 500);
  }

  /**
   * Purge Cloudflare cache on all sites in multisite.
   */
  function sb_purge_all_network_cache() {
    if (window.sb_use_native_js_fallback()) {
      if (window.confirm('Do you want to purge all cache?' + "\n" + 'This includes all your sites in the network')) {
        sb_purge_all_network_cache_confirmed();
      }
    } else {
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
          sb_purge_all_network_cache_confirmed();
        }
      });
    }
  }

  /**
   * Confirm callback for function "sb_purge_all_network_cache".
   */
  function sb_purge_all_network_cache_confirmed() {
    window.sb_loading(true);
    var data = {
      action: 'servebolt_purge_all_network_cache',
      security: servebolt_optimizer_ajax_object.ajax_nonce,
    };
    $.ajax({
      type: 'POST',
      url: servebolt_optimizer_ajax_object.ajaxurl,
      data: data,
      success: function(response) {
        window.sb_loading(false);
        var message = window.sb_get_message_from_response(response);
        if (response.success) {
          setTimeout(function () {
          var title = window.sb_get_from_response(response, 'title', window.sb_default_success_title())
          window.sb_popup(response.data.type, title, null, response.data.markup);
          }, 50);
        } else {
          var message = window.sb_get_message_from_response(response);
          if ( message ) {
            window.sbCachePurgeError(message);
          } else {
            window.sbCachePurgeError(null, false);
          }
        }
      },
      error: function() {
        window.sb_loading(false);
        window.sbCachePurgeError(null, false);
      }
    });
  }

  /**
   * Clear all cache in Cloudflare.
   */
  window.sb_purge_all_cache = function() {
    if (window.sb_use_native_js_fallback()) {
      if (window.confirm('Do you want to purge all cache?')) {
        sb_purge_all_cache_confirmed();
      }
    } else {
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
          sb_purge_all_cache_confirmed();
        }
      });
    }
  }

  /**
   * Confirm callback for function "sb_purge_all_cache".
   */
  function sb_purge_all_cache_confirmed() {
    window.sb_loading(true);
    var data = {
      action: 'servebolt_purge_all_cache',
      security: servebolt_optimizer_ajax_object.ajax_nonce,
    };
    $.ajax({
      type: 'POST',
      url: servebolt_optimizer_ajax_object.ajaxurl,
      data: data,
      success: function(response) {
        window.sb_loading(false);
        if (response.success) {
          setTimeout(function () {
            sb_cache_purge_success(
              window.sb_get_message_from_response(response),
              window.sb_get_from_response(response, 'title')
            );
            //window.update_cache_purge_list();
          }, 100);
          return;
        }
        window.sbCachePurgeError();
        // TODO: Display errors to the user
        //window.handle_unsuccessful_cache_purge(response);
      },
      error: function() {
        window.sb_loading(false);
        window.sbCachePurgeError(); // General error
      }
    });
  };

  window.handle_unsuccessful_cache_purge = function(response) {
    var type = window.sb_get_from_response(response, 'type', 'error'),
        message = window.sb_get_message_from_response(response);
    if (message) {
      // TODO: Display single message
      return;
      switch(type) {
        case 'error':
          window.sbCachePurgeError();
          break;
        case 'warning':
          sb_cache_purge_warning();
          break;
      }
    }
    var messages = window.sb_get_messages_from_response(response);
    if (messages) {
      // TODO: Handle multiple messages
      return;
      switch(type) {
        case 'error':
          window.sbCachePurgeError();
          break;
        case 'warning':
          sb_cache_purge_warning();
          break;
      }
    }
    /*
    var type = window.sb_get_from_response(response, 'type');
    if ( type == 'warning' ) {
      sb_cache_purge_warning(message, title);
    } else {
      window.sbCachePurgeError(message, false, title);
    }
    */
  };

  /**
   * Clear cache for the current post.
   *
   * @param {string|null} objectName  The post type label in singular form.
   */
  window.sbPurgePostCacheWithAutoResolve = function(objectName) {
    var postId = document.getElementById('post_ID').value;
    if (postId) {
      window.sbPurgePostCache(postId, objectName);
    }
  };


  /**
   * Purge cache by term ID.
   *
   * @param {int} termId              The term Id of the term that should be purged cache for.
   * @param {string|null} objectName  The taxonomy name of the term.
   */
  window.sbPurgeTermCache = function(termId, objectName) {
    var confirmText = 'Do you want to purge cache for ' + (objectName ? objectName : 'term') + '?';
    if (window.sb_use_native_js_fallback()) {
      if (window.confirm(confirmText)) {
        sbPurgeTermCacheConfirmed(termId);
      }
    } else {
      Swal.fire({
        title: confirmText,
        icon: 'warning',
        showCancelButton: true,
        customClass: {
          confirmButton: 'servebolt-button yellow',
          cancelButton: 'servebolt-button light',
        },
        buttonsStyling: false,
      }).then((result) => {
        if (result.value) {
          sbPurgeTermCacheConfirmed(termId);
        }
      });
    }
  };

  /**
   * Purge cache by post ID.
   *
   * @param {int} postId              The post Id of the post that should be purged cache for.
   * @param {string|null} objectName  The post type label in singular form.
   */
  window.sbPurgePostCache = function(postId, objectName) {
    var confirmText = 'Do you want to purge cache for ' + (objectName ? objectName : 'post') + '?';
    if (window.sb_use_native_js_fallback()) {
      if (window.confirm(confirmText)) {
        sb_purge_post_cache_confirmed(postId);
      }
    } else {
      Swal.fire({
        title: confirmText,
        icon: 'warning',
        showCancelButton: true,
        customClass: {
          confirmButton: 'servebolt-button yellow',
          cancelButton: 'servebolt-button light'
        },
        buttonsStyling: false
      }).then((result) => {
        if ( result.value ) {
          sb_purge_post_cache_confirmed(postId);
        }
      });
    }
  };


  /**
   * Confirm callback for function "sbPurgeTermCache".
   *
   * @param {int} termId The term Id of the term that should be purged cache for.
   */
  function sbPurgeTermCacheConfirmed(termId) {
    window.sb_loading(true);
    const data = {
      action: 'servebolt_purge_term_cache',
      security: servebolt_optimizer_ajax_object.ajax_nonce,
      term_id: termId,
    };
    $.ajax({
      type: 'POST',
      url: servebolt_optimizer_ajax_object.ajaxurl,
      data: data,
      success: function(response) {
        window.sb_loading(false);
        if (response.success) {
          setTimeout(function() {
            sb_cache_purge_success(
              window.sb_get_message_from_response(response),
              window.sb_get_from_response(response, 'title')
            );
            window.update_cache_purge_list();
          }, 100);
          return;
        }
        window.sbCachePurgeError('success but still a fail.');
        // TODO: Display errors to the user
        /*
        if ( message ) {
          if ( response.data.type == 'warning' ) {
            window.sb_warning(title, null, message);
          } else {
            window.sbCachePurgeError(message);
          }
        } else {

        }
        */
      },
      error: function() {
        window.sb_loading(false);
        window.sbCachePurgeError('api error');
      },
    });
  }

  /**
   * Confirm callback for function "sb_purge_post_cache".
   *
   * @param post_id
   */
  function sb_purge_post_cache_confirmed(post_id) {
    window.sb_loading(true);
    var data = {
      action: 'servebolt_purge_post_cache',
      security: servebolt_optimizer_ajax_object.ajax_nonce,
      post_id: post_id,
    };
    $.ajax({
      type: 'POST',
      url: servebolt_optimizer_ajax_object.ajaxurl,
      data: data,
      success: function(response) {
        window.sb_loading(false);
        if ( response.success ) {
          setTimeout(function () {
            sb_cache_purge_success(
                window.sb_get_message_from_response(response),
                window.sb_get_from_response(response, 'title')
            );
            window.update_cache_purge_list();
          }, 100);
          return;
        } else {
          if( Array.isArray(response.data) ) {
            // Handle multiple messages
            let output = '<details style="text-align:left;max-height:350px;overflow-y:auto"><summary style="text-align:center">review purge errors</summary><dl>';
            for (let i = 0; i < response.data.length; i++) {
               let message = (response.data[i].detail === null || response.data[i].detail === undefined) ? '' : response.data[i].detail;
               output += "<dt>"+response.data[i].title + "</dt><dd>"+ message +" (code: "+ response.data[i].code +")</dd>";
            }
            output += '</dl></details>';
            window.sbCachePurgeError(output, null, 'Purge API Errors');
          } else {
            window.sbCachePurgeError(response.data.message, null, 'Purge API Error');
          }
        }

        
        
      },
      error: function() {
        window.sb_loading(false);
        window.sbCachePurgeError('post cache failed');
      }
    });
  }

  /**
   * Clear cache by URL in Cloudflare.
   */
  window.sb_purge_url_cache = function() {
    if ( window.sb_use_native_js_fallback() ) {
      var value = window.prompt('Which URL do you wish to purge?' + "\n" + 'Please use full URL including "http://"');
      if ( ! value ) {
        window.alert('Please enter a URL.');
        return;
      }
      sb_purge_url_cache_confirmed(value);
    } else {
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
          if ( ! value ) {
            return 'Please enter a URL.'
          }
        },
        showCancelButton: true
      }).then((result) => {
        if ( result.value ) {
          sb_purge_url_cache_confirmed(result.value);
        }
      });
    }
  }

  /**
   * Prompt callback for function "sb_purge_url_cache".
   *
   * @param value
   */
  function sb_purge_url_cache_confirmed(value) {
    window.sb_loading(true);
    var data = {
      action: 'servebolt_purge_url_cache',
      security: servebolt_optimizer_ajax_object.ajax_nonce,
      url: value,
    };
    $.ajax({
      type: 'POST',
      url: servebolt_optimizer_ajax_object.ajaxurl,
      data: data,
      success: function(response) {
        window.sb_loading(false);
        if (response.success) {
          setTimeout(function() {
            var title = window.sb_get_from_response(response, 'title'),
                message = window.sb_get_message_from_response(response);
            sb_cache_purge_success(message, title);
            window.update_cache_purge_list();
          }, 100);
          return;
        }
        window.sbCachePurgeError();
        // TODO: Display errors to the user
        /*
        return;
        if ( message ) {
          if ( response.data.type == 'warning' ) {
            window.sb_warning(title, null, message);
          } else {
            window.sbCachePurgeError(message);
          }
        } else {
          window.sbCachePurgeError();
        }
        */
      },
      error: function() {
        window.sb_loading(false);
        window.sbCachePurgeError();
      }
    });
  }

  /**
   * Display cache purge success.
   *
   * @param message
   * @param title
   */
  function sb_cache_purge_success(message, title) {
    if ( typeof title === 'undefined' || ! title ) title = window.sb_default_success_title();
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
});
