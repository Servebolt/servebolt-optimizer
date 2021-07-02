document.addEventListener('DOMContentLoaded', function() {

    // Show/hide options fields
    document.getElementById('menu_cache_switch').addEventListener('change', window.showHideOptionsFields);

});

/**
 * Show/hide options field based on whether the feature is active or not.
 */
window.showHideOptionsFields = function () {
    var el = document.getElementById('menu-cache-options');
    if (this.checked) {
        el.removeAttribute('style');
    } else {
        el.style.display = 'none';
    }
}
