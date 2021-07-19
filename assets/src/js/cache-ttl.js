document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.sb-post-type-ttl-selector').forEach(function(item) {
        item.addEventListener('change', function() {
            var td = this.closest('td'),
                textInputField = td.querySelector('input[type="number"]');
            if (this.value == 'custom') {
                textInputField.removeAttribute('style');
            } else {
                textInputField.style.display = 'none';
            }
        });
    });
});
