document.addEventListener('DOMContentLoaded', function() {

    // Show/hide options fields
    document.getElementById('prefetch_switch').addEventListener('change', window.showHideOptionsFields);

});

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
