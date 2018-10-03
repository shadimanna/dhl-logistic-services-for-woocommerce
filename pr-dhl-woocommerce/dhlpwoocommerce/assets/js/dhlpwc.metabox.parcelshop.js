var dhlpwc_metabox_parcelshop_timeout = null;
var dhlpwc_metabox_parcelshop_timeout_search = null;

jQuery(document).ready(function($) {

    $(document.body).on('click', '#dhlpwc-metabox-parcelshop-display', function(e) {
        e.preventDefault();
        $('#dhlpwc-metabox-parcelshop-preview-box').hide();
        $('#dhlpwc-metabox-parcelshop-select-box').show();

        $(document.body).trigger('dhlpwc:metabox_parcelshop_trigger');

    }).on('keyup', '#dhlpwc-metabox-parcelshop-search-field', function(e) {
        e.preventDefault();

        $(document.body).trigger('dhlpwc:metabox_parcelshop_trigger');

    }).on('dhlpwc:metabox_parcelshop_trigger', function(e) {
        clearTimeout(dhlpwc_metabox_parcelshop_timeout);

        dhlpwc_metabox_parcelshop_timeout = setTimeout(function () {
            $(document.body).trigger('dhlpwc:metabox_parcelshop_search');
        }, 500);

    }).on('dhlpwc:metabox_parcelshop_search', function(e) {
        dhlpwc_metabox_parcelshop_timeout_search = $('#dhlpwc-metabox-parcelshop-search-field').val().toString();

        if ($('#dhlpwc-metabox-parcelshop-search-field').val().toString() === '') {
            return;
        }

        // Make AJAX call to get the view for div
        var data = {
            'action': 'dhlpwc_metabox_parcelshop_search',
            'post_id': dhlpwc_metabox_parcelshop_object.post_id,
            'search': dhlpwc_metabox_parcelshop_timeout_search
        };

        $.post(ajaxurl, data, function (response) {
            if ($('#dhlpwc-metabox-parcelshop-search-field').val().toString() !== dhlpwc_metabox_parcelshop_timeout_search) {
                // Input is already old, don't show
                return;
            }

            try {
                view = response.data.view;
            } catch (error) {
                alert('Error');
                return;
            }

            $('#dhlpwc-metabox-parcelshop-select-list').html(view);
        }, 'json');

    }).on('click', 'div.dhlpwc-metabox-parcelshop-location', function(e) {
        e.preventDefault();

        var parcelshop_name = $(this).find('strong').html();
        var parcelshop_description = $(this).find('span').html();

        $('#dhlpwc-metabox-parcelshop-hidden-input').val($(this).data('parcelshop-id'));
        $('#dhlpwc-metabox-parcelshop-select-list').html('');
        $('#dhlpwc-metabox-parcelshop-select-box').hide();
        $('#dhlpwc-metabox-parcelshop-preview-text').html(parcelshop_name);
        $('#dhlpwc-metabox-parcelshop-preview-description').html(parcelshop_description);
        $('#dhlpwc-metabox-parcelshop-preview-box').show();
    });

});
