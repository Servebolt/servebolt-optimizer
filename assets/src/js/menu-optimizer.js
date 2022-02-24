document.addEventListener('DOMContentLoaded', function() {
    if (element = document.getElementById('sb-menu-optimizer-purge-all-cache')) {
        element.addEventListener('click', window.menuOptimizerPurgeAll);
    }
});

/**
 * Purge all menu optimizer cache.
 */
window.menuOptimizerPurgeAll = function () {
    var title = 'Are you sure?';
    var text = 'Do you really want to purge all cache for the optimized menus?';
    if (window.sb_use_native_js_fallback()) {
        if (window.confirm(title + "\n" + text)) {
            window.menuOptimizerPurgeAllConfirmed();
        }
    } else {
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            customClass: {
                confirmButton: 'servebolt-button yellow',
                cancelButton: 'servebolt-button light'
            },
            buttonsStyling: false
        }).then(function(result) {
            if (result.value) {
                window.menuOptimizerPurgeAllConfirmed();
            }
        });
    }
};

/**
 * Purge all menu optimizer cache - confirmed.
 */
window.menuOptimizerPurgeAllConfirmed = function () {
    window.sb_loading(true);
    const data = new FormData();
    data.append('action', 'servebolt_menu_optimizer_purge_all');
    data.append('security', sb_ajax_object.ajax_nonce);
    fetch(sb_ajax_object.ajaxurl, {
        method: 'POST',
        body: data
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(response) {
        window.sb_loading(false);
        window.sb_success('Cache was purged');
    })
    .catch(function(error) {
        window.sb_loading(false);
        window.sb_error('Could not purge cache.');
    });
};
