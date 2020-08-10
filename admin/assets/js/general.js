jQuery(document).ready(function($) {

  // Input toggle value visibility
  $('#sb-configuration .sb-hide-pwd').click(function() {
    sb_toggle_input_visibility(this);
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

});

(function($) {

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

  /**
   * Get message from jQuery AJAX response object.
   *
   * @param response
   * @returns {*}
   */
  window.sb_get_message_from_response = function(response) {
    return window.sb_get_from_response(response, 'message')
  }

  /**
   * Get item from data in jQuery AJAX response object.
   *
   * @param response
   * @param key
   * @param default_value
   * @returns {*}
   */
  window.sb_get_from_response = function(response, key, default_value) {
    if ( typeof response.data !== 'undefined' && typeof response.data[key] !== 'undefined' ) {
      return response.data[key];
    }
    return default_value ? default_value : false;
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
