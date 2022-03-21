jQuery(document).ready(function($) {

    $(document.body).on('click', 'div.dhlpwc-dismissable-migrate-notice button.notice-dismiss', function(e) {
        e.preventDefault();

        var data = {
            'action': 'dhlpwc_dismiss_migrate_notice'
        };

        $.post(ajaxurl, data);

    }).on('click', 'a#dhlpwc-dismiss-migrate-notice-forever', function(e) {
        e.preventDefault();

        var data = {
            'action': 'dhlpwc_dismiss_migrate_notice_forever'
        };

        $.post(ajaxurl, data);

        $('div.dhlpwc-dismissable-migrate-notice button.notice-dismiss').trigger('click');

    });

});
