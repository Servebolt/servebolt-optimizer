document.addEventListener('DOMContentLoaded', function() {

    // Add image size
    document.getElementById('sb-add-acd-image-size').addEventListener('click', window.addAcdImageSize);

    // Remove image size
    document.getElementById('acd-image-size-index').addEventListener('click', function (e) {
        if (!e.target.matches('.sb-remove-acd-image-size')) return;
        e.preventDefault();
        window.removeAcdImageSize(e.target);
    }, false);

    // Show/hide options fields
    document.getElementById('acd_image_resize_switch').addEventListener('change', window.showHideOptionsFields);

    // Checkbox change event
    document.getElementById('acd-image-size-index').addEventListener('change', function (e) {
        if (!e.target.matches('input[type="checkbox"]')) return;
        e.preventDefault();
        window.acdCheckForCheckedCheckboxes();
    }, false);

    // Remove selected image sizes
    document.querySelectorAll('.sb-remove-selected-acd-image-sizes').forEach(function(item) {
        item.addEventListener('click', window.removeAcdImageSizes);
    });
}, false);

/**
 * Show/hide options field based on whether the feature is active or not.
 */
window.showHideOptionsFields = function () {
    var el = document.getElementById('acd-image-resize-options');
    if (this.checked) {
        el.removeAttribute('style');
    } else {
        el.style.display = 'none';
    }
}

/**
 * Uncheck all checkboxes.
 */
window.acdUnselectAllCheckboxes = function() {
    document.getElementById('acd-image-size-index').querySelectorAll('input[type="checkbox"]:checked').forEach(function(item) {
        item.checked = false;
    });
};

/**
 * Remove image size.
 */
window.removeAcdImageSizes = function() {
    var title = 'Are you sure?';
    var text = 'Do you really want to remove the sizes?';
    if (window.sb_use_native_js_fallback()) {
        if (window.confirm(title + "\n" + text)) {
            window.removeAcdImageSizeConfirmed();
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
                window.removeAcdImageSizesConfirmed();
            }
        });
    }
};

/**
 * Execute removal of multiple image sizes, sending them off to WP AJAX.
 */
window.removeAcdImageSizesConfirmed = function() {
    window.acdSpinner(true);
    var itemsToRemove = [];
    window.acdGetCheckedCheckboxes().forEach(function(item) {
        itemsToRemove.push(item.value);
    });
    window.submitAcdImageSizeAction(itemsToRemove, 'servebolt_acd_remove_image_sizes', function(response) {
        window.acdSpinner(false);
        window.sb_success('Sizes removed.');
        window.loadAcdImageSizes();
        window.acdUnselectAllCheckboxes();
        window.acdCheckForCheckedCheckboxes();
    }, window.acdHandleAjaxError);
};

/**
 * Check whether removal button should be clickable or not.
 */
window.acdCheckForCheckedCheckboxes = function() {
    const hasCheckedCheckboxes = window.acdHasCheckedCheckboxes();
    document.querySelectorAll('.sb-remove-selected-acd-image-sizes').forEach(function(item) {
        item.disabled = !hasCheckedCheckboxes;
    });
};

/**
 * Check if we have checked one or more checkboxes.
 *
 * @returns {boolean}
 */
window.acdHasCheckedCheckboxes = function() {
    return window.acdCheckboxCheckCount() > 0;
};

/**
 * Get checkboxes that are checked.
 *
 * @returns {NodeListOf<Element>}
 */
window.acdGetCheckedCheckboxes = function() {
    return document.getElementById('acd-image-size-index').querySelector('tbody').querySelectorAll('input[type="checkbox"]:checked');
}

/**
 * Count the number of checked checkboxes.
 *
 * @returns {number}
 */
window.acdCheckboxCheckCount = function() {
    return window.acdGetCheckedCheckboxes().length;
};

/**
 * Toggle spinner.
 *
 * @param shouldSpin
 */
window.acdSpinner = function(shouldSpin) {
    var spinner = document.querySelector('.acd-image-size-index-loading-spinner');
    if (shouldSpin) {
        spinner.classList.add('is-active');
    } else {
        spinner.classList.remove('is-active');
    }
}

/**
 * Load image size list markup.
 */
window.loadAcdImageSizes = function() {
    window.acdSpinner(true);
    const data = new FormData();
    data.append('action', 'servebolt_acd_load_image_sizes');
    data.append('security', sb_ajax_object.ajax_nonce);
    const queryString = new URLSearchParams(data).toString();
    fetch(sb_ajax_object.ajaxurl + '?' + queryString,
        {
            method: 'GET'
        }
    )
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        window.acdSpinner(false);
        if (data.success) {
            window.getAcdImageSizeListElement().innerHTML = data.data.markup;
        }
        window.acdCheckAllCheckboxAvailability();
    })
    .catch(window.acdHandleAjaxError);
};

/**
 * Get the markup element that contains all the image sizes.
 *
 * @param element
 * @returns {Element}
 */
window.getAcdImageSizeListElement = function(element) {
    return document.querySelector('#acd-image-size-index #the-list');
};

/**
 * Handle the removal of ACD size.
 *
 * @param element
 */
window.removeAcdImageSize = function(element) {
    var title = 'Are you sure?';
    var text = 'Do you really want to remove the size?';
    if (window.sb_use_native_js_fallback()) {
        if (window.confirm(title + "\n" + text)) {
            window.removeAcdImageSizeConfirmed(element);
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
                window.removeAcdImageSizeConfirmed(element);
            }
        });
    }
};

/**
 * Submit the removal request for an image size.
 *
 * @param element
 */
window.removeAcdImageSizeConfirmed = function(element) {
    var tr = element.closest('tr'),
    value = tr.querySelector('span.row-value').innerText;
    window.submitAcdImageSizeAction(value,'servebolt_acd_remove_image_size', function(response) {
        window.acdSpinner(false);
        if (response.success) {
            tr.remove();
            window.acdCheckForEmptyList();
            window.acdCheckAllCheckboxAvailability();
            window.sb_success('Image size removed!');
        } else {
            if (response.data.message) {
                window.sb_error(response.data.message);
            } else {
                window.sb_error('Could not remove image size.', 'Please check your input data and try again.');
            }
        }
    }, window.acdHandleAjaxError);
};

/**
 * Count the rows in the list.
 */
window.acdGetListRowCount = function() {
    return window.getAcdImageSizeListElement().querySelectorAll('tr:not(.no-items)').length;
};

/**
 * Check if list is empty, if so then display the "No rows"-indicator.
 */
window.acdCheckForEmptyList = function() {
    if (window.acdListIsEmpty()) {
        window.getAcdImageSizeListElement().querySelector('tr.no-items').classList.remove('hidden');
    } else {
        window.getAcdImageSizeListElement().querySelector('tr.no-items').classList.add('hidden');
    }
}

/**
 * Check if list is empty.
 * @returns {boolean}
 */
window.acdListIsEmpty = function() {
    return window.acdGetListRowCount() === 0;
};

/**
 * Disable check all-checkboxes is list is empty.
 */
window.acdCheckAllCheckboxAvailability = function() {
    const listIsEmpty = window.acdListIsEmpty();
    document.getElementById('acd-image-size-index').querySelectorAll('thead input[type="checkbox"], tfoot input[type="checkbox"]').forEach(function(item) {
        item.disabled = listIsEmpty;
    });
}

/**
 * Validate a size.
 *
 * @param str
 * @returns {boolean}
 */
function imageSizeValid(str)
{
    var patt = new RegExp(sb_ajax_object_acd_image_size.image_size_regex_pattern);
    return patt.test(str);
}

/**
 * Handle HTTP error.
 *
 * @param error
 */
window.acdHandleAjaxError = function(error) {
    window.acdSpinner(false);
    window.sb_error('Something went wrong!', 'Please check your input data and try again.');
};

/**
 * Handle image size creation response.
 *
 * @param response
 */
window.addAcdImageSizeResponse = function(response) {
    window.acdSpinner(false);
    if (response.success) {
        window.sb_success('Image size added!');
        window.loadAcdImageSizes();
    } else {
        if (response.data.message) {
            window.sb_error(response.data.message);
        } else {
            window.sb_error('Could not add image size.', 'Please check your input data and try again.');
        }
    }
};

/**
 * Add image size.
 */
window.addAcdImageSize = function() {
    const promptText = 'Please specify image size.';
    const exampleString = 'Example: 1200w or 600h';
    if (window.sb_use_native_js_fallback()) {
        var value = window.prompt(promptText + "\n" + exampleString);
        if (!value) {
            window.alert('Please enter a value.');
            return;
        } else if (!imageSizeValid(value)) {
            window.alert('Value not valid, please try again.');
        }
        window.submitAcdImageSizeAction(value, 'servebolt_acd_add_image_size', window.addAcdImageSizeResponse, window.acdHandleAjaxError);
    } else {
        Swal.fire({
            text: promptText,
            input: 'text',
            inputPlaceholder: exampleString,
            customClass: {
                confirmButton: 'servebolt-button yellow',
                cancelButton: 'servebolt-button light'
            },
            buttonsStyling: false,
            inputValidator: function(value) {
                if (!value) {
                    return 'Please enter a value.'
                } else if (!imageSizeValid(value)) {
                    return 'Value not valid, please try again.';
                }
            },
            showCancelButton: true
        }).then(function(result) {
            if (result.value) {
                window.submitAcdImageSizeAction(result.value, 'servebolt_acd_add_image_size', window.addAcdImageSizeResponse, window.acdHandleAjaxError);
            }
        });
    }
};

/**
 * Submit an action for an image size.
 *
 * @param value
 * @param success
 * @param error
 */
window.submitAcdImageSizeAction = function(value, action, success, error) {
    window.acdSpinner(true);
    const data = new FormData();
    data.append('action', action);
    data.append('security', sb_ajax_object.ajax_nonce);
    data.append('value', value);
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
        success(data);
    })
    .catch(error);
};
