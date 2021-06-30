document.addEventListener('DOMContentLoaded', function() {

    // Show/hide options fields
    document.getElementById('prefetch_switch').addEventListener('change', window.showHideOptionsFields);

    // Trigger prefetch file regeneration
    document.getElementById('sb-regenerate-prefetch-files').addEventListener('click', window.generatePrefetchFiles);

});

/**
 * Toggle spinner.
 *
 * @param shouldSpin
 */
window.prefetchingSpinner = function(shouldSpin) {
    var spinner = document.querySelector('.regenerate-prefetch-files-loading-spinner');
    if (shouldSpin) {
        spinner.classList.add('is-active');
    } else {
        spinner.classList.remove('is-active');
    }
}


/**
 * Execute AJAX request to regenerate prefetch files.
 */
window.generatePrefetchFilesConfirmed = function () {
    window.prefetchingSpinner(true);
    const data = new FormData();
    data.append('action', 'servebolt_prefetching_generate_files');
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
        window.prefetchingSpinner(false);
        window.sb_success('Files were regenerated.');
    })
    .catch(function(error) {
        window.prefetchingSpinner(false);
        window.sb_error('Something went wrong!', 'Please check your input data and try again.');
    });
}

/**
 * Confirm the action for regenerating the prefetch files.
 */
window.generatePrefetchFiles = function() {
    var title = 'Are you sure?';
    var text = 'Do you really want to generate manifest files?';
    if (window.sb_use_native_js_fallback()) {
        if (window.confirm(title + "\n" + text)) {
            window.generatePrefetchFilesConfirmed();
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
                window.generatePrefetchFilesConfirmed();
            }
        });
    }
};

/**
 * Show/hide options field based on whether the feature is active or not.
 */
window.showHideOptionsFields = function () {
    var el = document.getElementById('prefetching-options');
    if (this.checked) {
        el.removeAttribute('style');
    } else {
        el.style.display = 'none';
    }
}
