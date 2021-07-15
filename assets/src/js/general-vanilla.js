document.addEventListener('DOMContentLoaded', function() {
    window.sbShowHideOptionsFields();
});

/**
 * Show/hide options fields based on whether a checkbox is checked or not.
 */
window.sbShowHideOptionsFields = function () {
    var optionsFieldSwitch = document.querySelector('.options-field-switch');
    if (optionsFieldSwitch) {
        optionsFieldSwitch.addEventListener('change', function () {
            var optionsFieldsWrapper = document.querySelector('#options-field');
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
