document.addEventListener('DOMContentLoaded', function() {

    // Trigger prefetch file regeneration
    document.getElementById('sb-regenerate-manifest-files').addEventListener('click', window.generateManifestFiles);
    document.getElementById('sb-regenerate-manifest-files-using-cron').addEventListener('click', window.generateManifestFilesUsingCron);
    var element = document.getElementById('sb-manually-generate-manifest-files');
    if (element) {
        element.addEventListener('click', window.manuallyGenerateManifestFiles);
    }

    // Maybe display success message after manual manifest file generation.
    maybeDisplaySuccessMessageForManualManifestFileGeneration();
});

/**
 * Maybe display success message after manual manifest file generation.
 */
function maybeDisplaySuccessMessageForManualManifestFileGeneration()
{
    var form = document.querySelector('form#sb-prefetching');
    if (form.dataset.didManualGeneration) {
        history.replaceState && history.replaceState(null, '', location.pathname + location.search.replace(/[\?&]manual-prefetch-success[^&]*/, '').replace(/^&/, '?'));
        if (window.sb_use_native_js_fallback()) {
            alert('Manual manifest file generation is completed!');
        } else {
            window.sb_success('Success', 'Manual manifest file generation is completed!');
        }
    }
}

/**
 * Generate manifest files manually - confirmed.
 *
 * @param url
 */
window.manuallyGenerateManifestFilesConfirmed = function(url) {
    var data = new FormData();
    data.append('action', 'servebolt_prefetching_prepare_for_manual_generation');
    data.append('security', servebolt_optimizer_ajax_object.ajax_nonce);
    fetch(servebolt_optimizer_ajax_object.ajaxurl, {
        method: 'POST',
        body: data
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(response) {
        if (response.success) {
            window.location.href = url;
        } else {
            alert('Error. Please try again or contact the plugin author.');
        }
    })
    .catch(function(error) {
        alert('Error. Please try again or contact the plugin author.');
    });
}

/**
 * Generate manifest files manually.
 *
 * @param e
 */
window.manuallyGenerateManifestFiles = function(e) {
    var url = this.dataset.href;
    var title = 'Are you sure?';
    var text = 'To generate manifest files we need to load the front page while not logged in. Continuing will log you out of WP Admin. Do you want to continue?';
    if (window.sb_use_native_js_fallback()) {
        if (window.confirm(title + "\n" + text)) {
            window.manuallyGenerateManifestFilesConfirmed(url);
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
                window.manuallyGenerateManifestFilesConfirmed(url);
            }
        });
    }
}

/**
 * Toggle spinner.
 *
 * @param shouldSpin
 */
window.prefetchingSpinner = function(shouldSpin) {
    var spinner = document.querySelector('.regenerate-manifest-files-loading-spinner');
    if (shouldSpin) {
        spinner.classList.add('is-active');
    } else {
        spinner.classList.remove('is-active');
    }
};

/**
 * Confirm the action for regenerating the manifest files.
 */
window.generateManifestFiles = function() {
    var title = 'Are you sure?';
    //var text = 'Do you really want to generate manifest files?';
    var text = 'To generate manifest files we need to load the front page while not logged in. Continuing will log you out of WP Admin. Do you want to continue?';
    if (window.sb_use_native_js_fallback()) {
        if (window.confirm(title + "\n" + text)) {
            window.generateManifestFilesConfirmed();
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
                window.generateManifestFilesConfirmed();
            }
        });
    }
};

/**
 * Display success message after successfully generating manifest files.
 */
window.generateManifestFilesSuccess = function (url) {
    window.prefetchingSpinner(false);
    window.sb_success('Files were regenerated.', null, null, function () {
        window.location.href = url;
    });
}

/**
 * Execute AJAX request to regenerate manifest files.
 */
window.generateManifestFilesConfirmed = function () {
    window.prefetchingSpinner(true);
    const data = new FormData();
    data.append('action', 'servebolt_prefetching_generate_files_instructions');
    data.append('security', servebolt_optimizer_ajax_object.ajax_nonce);
    fetch(servebolt_optimizer_ajax_object.ajaxurl,
        {
            method: 'POST',
            body: data
        }
    )
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        if (data.success) {
            fetch(data.data.generation_url).then(function() {
                if (data.data.should_expose_manifest_files_after_prefetch_items_record) {
                    fetch(data.data.manifest_files_expose_url).then(function () {
                        window.generateManifestFilesSuccess(data.data.login_url);
                    });
                } else {
                    window.generateManifestFilesSuccess(data.data.login_url);
                }
            });
        } else {
            window.prefetchingSpinner(false);
            window.sb_error('Something went wrong!', 'Please check your input data and try again.');
        }
    })
    .catch(function(error) {
        window.prefetchingSpinner(false);
        window.sb_error('Something went wrong!', 'Please check your input data and try again.');
    });
};

/**
 * Confirm the action for regenerating the manifest files using cron.
 */
window.generateManifestFilesUsingCron = function() {
    var title = 'Are you sure?';
    var text = 'Do you really want to generate manifest files?';
    if (window.sb_use_native_js_fallback()) {
        if (window.confirm(title + "\n" + text)) {
            window.generateManifestFilesUsingCronConfirmed();
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
                window.generateManifestFilesUsingCronConfirmed();
            }
        });
    }
};

/**
 * Execute AJAX request to regenerate manifest files using cron.
 */
window.generateManifestFilesUsingCronConfirmed = function () {
    window.prefetchingSpinner(true);
    const data = new FormData();
    data.append('action', 'servebolt_prefetching_generate_files_using_cron');
    data.append('security', servebolt_optimizer_ajax_object.ajax_nonce);
    fetch(servebolt_optimizer_ajax_object.ajaxurl,
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
            window.sb_success('Files were scheduled for regeneration.', null, null, function () {
                location.reload();
            });
        })
        .catch(function(error) {
            window.prefetchingSpinner(false);
            window.sb_error('Something went wrong!', 'Please check your input data and try again.');
        });
};
