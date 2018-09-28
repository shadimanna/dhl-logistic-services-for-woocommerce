jQuery(document).ready(function($) {

    $(document.body).on('click', 'div[data-dhlpwc-dismissable-notice] button.notice-dismiss', function(e) {
        e.preventDefault();

        var notice_tag = $(this).parent().data('dhlpwc-dismissable-notice');

        var data = {
            'action': 'dhlpwc_dismiss_admin_notice',
            'notice_tag': notice_tag
        };

        $.post(ajaxurl, data);
    });

});
