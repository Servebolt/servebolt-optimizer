document.addEventListener('DOMContentLoaded', function() {
   document.getElementById('sb-acd-purge-all-cache').addEventListener('click', window.acdPurgeAll);
});

/*
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
*/

/**
 * Execute AJAX request to regenerate prefetch files.
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
   .then(function(data) {
      window.sb_loading(false);
      //window.sb_success('Files were regenerated.');
   })
   .catch(function(error) {
      window.sb_loading(false);
      sb_cache_purge_error(null, false);
   });
}

/**
 *
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
