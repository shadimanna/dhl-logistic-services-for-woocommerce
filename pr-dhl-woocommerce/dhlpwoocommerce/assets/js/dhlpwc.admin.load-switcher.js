jQuery(document).ready(function($) {

    $(document.body).on('click', 'a#dhlpwc-admin-load-switcher', function(e) {
        e.preventDefault();
        var data = {
            'action': 'dhlpwc_load_switcher'
        };
        $.post(ajaxurl, data, function (response) {
            try {
                var admin_link = response.data.admin_link;
            } catch (error) {
                return;
            }

            window.location = admin_link;
        }, 'json');
    });

});
