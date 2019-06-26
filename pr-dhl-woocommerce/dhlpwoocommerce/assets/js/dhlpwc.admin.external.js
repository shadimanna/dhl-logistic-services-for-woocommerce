jQuery(document).ready(function($) {

    $(document.body).on('click', 'form#posts-filter input#doaction', function(e) {
        var value = $("form#posts-filter select[id^=bulk-action-selector-]").val();
        if (value === 'dhlpwc_download_labels') {
            $(this).attr('formtarget', '_blank');
        } else {
            $(this).removeAttr('formtarget');
        }
    });

});
