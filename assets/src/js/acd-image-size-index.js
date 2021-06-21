document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('sb-add-acd-image-size').addEventListener('click', window.addAcdImageSize);

    document.querySelectorAll('.sb-remove-acd-image-size').forEach(item => {
        item.addEventListener('click', window.removeAcdImageSize);
    });
}, false);

window.removeAcdImageSize = function(e) {
    e.preventDefault();
    
};

window.addAcdImageSize = function() {
    if ( window.sb_use_native_js_fallback() ) {
        var value = window.prompt('Please specify image size.' + "\n" + 'Example: 1200w or 600h');
        if (!value) {
            window.alert('Please enter a value.');
            return;
        }
        //sb_purge_url_cache_confirmed(value);
    } else {
        Swal.fire({
            text: 'Please specify image size.',
            input: 'text',
            inputPlaceholder: 'Example: 1200w or 600h',
            customClass: {
                confirmButton: 'servebolt-button yellow',
                cancelButton: 'servebolt-button light'
            },
            buttonsStyling: false,
            inputValidator: (value) => {
                if (!value) {
                    return 'Please enter a value.'
                }
            },
            showCancelButton: true
        }).then((result) => {
            if (result.value) {
                //sb_purge_url_cache_confirmed(result.value);
            }
        });
    }

    fetch('./api/projects',
        {
            method: 'post',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                title: 'Beispielprojekt',
                url: 'http://www.example.com',
            })
        })
        .then(function (response) {
            console.log(response);
        })
        .catch(function (error) {
            console.error(error);
        });
};
