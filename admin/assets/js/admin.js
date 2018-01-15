jQuery(document).ready(function($) {

    // This will run when the optimize-now button is clicked.
    $('.optimize-now').click(function(){
        var data = {
            action: 'servebolt_optimize_db',
            do_optimize: true
        };

        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        $.post(ajaxurl, data, function(response) {
            alert(response);
            location.reload();
        });
    });


});