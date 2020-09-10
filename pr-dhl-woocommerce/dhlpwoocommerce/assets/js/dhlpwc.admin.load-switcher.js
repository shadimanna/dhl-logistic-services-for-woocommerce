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

    // Inject manually if admin notices are disabled
    if ($('a#dhlpwc-admin-load-switcher').length === 0) {
        var data = {
            'action': 'dhlpwc_inject_switcher',
            'message': dhlpwc_load_switcher_object.message
        };
        $.post(ajaxurl, data, function (response) {
            console.log(response);
            try {
                var view = response.data.view;
            } catch (error) {
                return;
            }
            $('form#mainform').prepend(view);
        }, 'json');
    }

});
