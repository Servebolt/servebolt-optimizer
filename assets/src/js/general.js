jQuery(document).ready(function($) {

  // Input toggle value visibility
  $('#sb-configuration .sb-hide-pwd').click(function() {
    sb_toggle_input_visibility(this);
  });

  // Clear all plugin settings
  $('.sb-content .sb-clear-all-settings').click(function(e) {
    e.preventDefault();
    sb_clear_all_settings();
  });

  /**
   * Toggle input value visibility.
   */
  function sb_toggle_input_visibility(el) {
    var el = $(el),
      parent_el = el.closest('.sb-pwd'),
      input_el = parent_el.find('input'),
      icon = parent_el.find('.dashicons'),
      input_type = input_el.attr('type');
    switch (input_type) {
      case 'password':
        input_el.attr('type', 'text');
        icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        break;
      case 'text':
        input_el.attr('type', 'password');
        icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        break;
    }
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

});

(function($) {

  /**
   * Whether we should use native JS for alert, prompt, confirm etc.
   *
   * @returns {boolean}
   */
  window.sb_use_native_js_fallback = function() {
    return sb_ajax_object.use_native_js_fallback == 'true';
  }

  /**
   * Insert loader markup.
   */
  function sb_insert_loader_markup() {
    var container = 'div.wrap.sb-content',
        markup = '<div id="servebolt-loading" class=""><div class="loader loading-ring"></div></div>';
    if ( ! $(container).length ) {
      container = 'div.wrap'
    }
    if ( ! $(container).length ) {
      container = 'body'
      if ( $(container).length ) {
        $(markup).prependTo(container);
        return true;
      }
    }
    if ( ! $(container).length ) return false;
    $(markup).insertBefore(container);
    return true;
  }

  /**
   * Display success modal.
   *
   * @param title
   * @param text
   * @param html
   */
  window.sb_success = function(title, text, html) {
    window.sb_popup('success', title, text, html);
  }

  /**
   * Display warning modal.
   *
   * @param title
   * @param text
   * @param html
   */
  window.sb_warning = function(title, text, html) {
    window.sb_popup('warning', title, text, html);
  }

  /**
   * Display general error message.
   *
   * @param title
   * @param text
   * @param html
   */
  window.sb_error = function(title, text, html) {
    window.sb_popup('error', title, text, html);
  }

  /**
   * Display Sweetalert2 message.
   *
   * @param type
   * @param title
   * @param text
   * @param html
   */
  window.sb_popup = function(type, title, text, html) {
    if ( window.sb_use_native_js_fallback() ) {
      var verboseType = type.charAt(0).toUpperCase() + type.slice(1);
      var message = html ? window.sb_strip(html, true) : window.sb_strip(text);
      window.alert(verboseType + ': ' + window.sb_strip(title) + "\n" + message);
    } else {
      var config = {
        icon: type,
        customClass: {
          confirmButton: 'servebolt-button yellow'
        },
        buttonsStyling: false
      };
      if ( title ) {
        config.title = title;
      }
      if ( text ) {
        config.text = text;
      }
      if ( html ) {
        config.html = html;
      }
      Swal.fire(config);
    }
  }

  /**
   * Strip HTML from a string.
   *
   * @param html
   * @returns {string | string}
   */
  window.sb_strip = function(html, br2nl) {
    if ( br2nl === true ) {
      html = html.replace('<br>', "\n");
      html = html.replace('<ul><li>', "<ul><li>\n");
      html = html.replace('</li><li>', "</li><li>\n");
    }
    var doc = new DOMParser().parseFromString(html, 'text/html');
    return doc.body.textContent || '';
  };

  /**
   * Get message from jQuery AJAX response object.
   *
   * @param response
   * @returns {*}
   */
  window.sb_get_message_from_response = function(response) {
    return window.sb_get_from_response(response, 'message');
  };

  /**
   * Get message from jQuery AJAX response object.
   *
   * @param response
   * @returns {*}
   */
  window.sb_get_messages_from_response = function(response) {
    return window.sb_get_from_response(response, 'messages');
  }

  /**
   * Get item from data in jQuery AJAX response object.
   *
   * @param response
   * @param key
   * @param defaultValue
   * @returns {*}
   */
  window.sb_get_from_response = function(response, key, defaultValue) {
    if ( typeof response.data !== 'undefined' && typeof response.data[key] !== 'undefined' ) {
      return response.data[key];
    }
    return defaultValue ? defaultValue : false;
  }

  /**
   * Show/hide loading overlay.
   *
   * @param bool
   */
  window.sb_loading = function(bool) {
    var element = $('#servebolt-loading');
    if ( ! element.length ) {
      sb_insert_loader_markup()
      element = $('#servebolt-loading');
    }
    if ( bool ) {
      $('body').addClass('sb-loading-spinner-active');
      element.addClass('active');
    } else {
      $('body').removeClass('sb-loading-spinner-active');
      element.removeClass('active');
    }
  }

})(jQuery);
