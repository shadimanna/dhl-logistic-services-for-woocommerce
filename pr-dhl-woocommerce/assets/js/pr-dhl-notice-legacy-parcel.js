// shorthand no-conflict safe document-ready function
jQuery(document).ready(function($) {
    $(document.body).on('click', 'div.dhl-legacy-parcel-dismiss-migrate-notice button.notice-dismiss', function(e) {
        e.preventDefault();

        var data = {
            'action': 'dhl_legacy_parcel_dismiss_migrate_notice',
            'security': dhl_legacy_parcel_dismiss_notice.security,
        };

        $.post(ajaxurl, data);

    }).on('click', 'a#dhl-legacy-parcel-dismiss-migrate-notice-forever', function(e) {
        e.preventDefault();

        var data = {
            'action': 'dhl_legacy_parcel_dismiss_migrate_notice_forever',
            'security': dhl_legacy_parcel_dismiss_notice.security,
        };

        $.post(ajaxurl, data);

        $('div.dhl-legacy-parcel-dismiss-migrate-notice button.notice-dismiss').trigger('click');

    });

});
