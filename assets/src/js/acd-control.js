document.addEventListener('DOMContentLoaded', function() {
   if (element = document.querySelector('#sb-acd-purge-all-cache')) {
      element.addEventListener('click', window.acdPurgeAll);
   }
   if (document.querySelector('form#sb-accelerated-domains-image-resize-options-page-form')) {
      window.acdImageResizeAccessCheck();
   }
});

/**
 * Check if the current site has access to the Accelerated Domains Image Resize-feature.
 */
window.acdImageResizeAccessCheck = function () {
   window.envConfig.get('sb_acd_image_resize').then(function(result) {
      if (result) {
         window.acdImageResizeActivate();
      } else {
         window.ensureAcdImageResizeDisabled();
      }
   });
};

/**
 * Disable the Accelerated Domains Image Resize-feature
 */
window.ensureAcdImageResizeDisabled = function () {
   var element = document.getElementById('acd_image_resize_switch');
   if (!element.checked) {
      return;
   }
   element.checked = false;
   const data = new FormData();
   data.append('action', 'servebolt_acd_image_resize_disable');
   data.append('security', sb_ajax_object.ajax_nonce);
   fetch(sb_ajax_object.ajaxurl, {
       method: 'POST',
       body: data
   });
};

/**
 * Activate the option to enable/disable the Accelerated Domains Image Resize-feature.
 */
window.acdImageResizeActivate = function () {
   document.getElementById('acd_image_resize_switch').disabled = false;
};

/**
 * Execute AJAX request to purge all cache.
 */
window.acdPurgeAll = function () {
   if (window.sb_use_native_js_fallback()) {
      if (window.confirm('Do you want to purge all cache?')) {
         window.acdPurgeAllConfirmed();
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
            window.acdPurgeAllConfirmed();
         }
      });
   }
};

/**
 * Execute AJAX request to purge all cache after confirmation.
 */
window.acdPurgeAllConfirmed = function () {
   window.sb_loading(true);
   const data = new FormData();
   data.append('action', 'servebolt_acd_purge_all_cache');
   data.append('security', sb_ajax_object.ajax_nonce);
   fetch(sb_ajax_object.ajaxurl,
       {
          method: 'POST',
          body: data
       }
   )
   .then(function(response) {
      return response.json();
   })
   .then(function(response) {
      window.sb_loading(false);
      if (response.success) {
         setTimeout(function () {
            var title = window.sb_get_from_response(response, 'title', window.sb_default_success_title())
            window.sb_success(title, response.data.message);
         }, 50);
      } else {
         var message = window.sb_get_message_from_response(response);
         if (message) {
            window.sbCachePurgeError(message);
         } else {
            window.sbCachePurgeError(null, false);
         }
      }
   })
   .catch(function(error) {
      window.sb_loading(false);
      window.sbCachePurgeError(null, false);
   });
}
