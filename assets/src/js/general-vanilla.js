document.addEventListener('DOMContentLoaded', function() {
    window.sbShowHideOptionsFields();
});

/**
 * Show/hide options fields based on whether a checkbox is checked or not.
 */
window.sbShowHideOptionsFields = function() {
    var optionsFieldSwitch = document.querySelector('.options-field-switch');
    if (optionsFieldSwitch) {
        optionsFieldSwitch.addEventListener('change', function () {
            var optionsFieldsWrapper = document.querySelector('#options-fields');
            if (optionsFieldsWrapper) {
                if (this.checked) {
                    optionsFieldsWrapper.removeAttribute('style');
                } else {
                    optionsFieldsWrapper.style.display = 'none';
                }
            }
        });
    }
}

/**
 * Default success title.
 *
 * @returns {string}
 */
window.sb_default_success_title = function() {
    return 'All good!';
}

/**
 * Display cache purge error.
 *
 * @param message
 * @param include_url_message
 * @param title
 */
window.sbCachePurgeError = function(message, include_url_message, title) {
    if (typeof include_url_message === 'undefined') {
        include_url_message = true;
    }
    if (typeof title === 'undefined' || ! title) {
        title = 'Unknown error';
    }
    if (!message) {
        var message = 'Something went wrong. Please check that you:<br><ul style="text-align: left;max-width:350px;margin: 20px auto;">' + ( include_url_message ? '<li>- Specified a valid URL</li>' : '' ) + '<li>- Have configured the cache purge feature</li></ul> If the error still persist then please check the error logs and/or contact support.';
    }
    window.sb_error(title, null, message);
};
