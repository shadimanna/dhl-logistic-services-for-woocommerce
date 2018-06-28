jQuery(document).ready(function($) {
    $(document.body).on('click', '#dhlpwc-label-create', function(e) {
        e.preventDefault();

        var label_size = $('.dhlpwc-label-create-size:checked').val();
        if (typeof label_size === "undefined" ) {
            // TODO update alert to a more user friendly user feedback
            alert('Select a label');
            return;
        }

        var label_options = [];
        $("input[name='dhlpwc-label-create-option[]']:checked").each(function() {
            label_options.push($(this).val().toString());
        });

        var to_business = $("input[name='dhlpwc-label-create-to-business']").is(':checked') ?  'yes' : 'no';

        var data = $.extend(true, $(this).data(), {
            action: 'dhlpwc_label_create',
            security: $( '#dhlpwc-ajax-nonce' ).val(),
            post_id: dhlpwc_metabox_object.post_id,
            label_size: label_size,
            label_options: label_options,
            to_business: to_business,
            form_data: $('#post').serializeArray()
        });

        // Click-2-much prevention
        if ($('#dhlpwc-label').attr('metabox_busy') == 'true') {
            alert('Currently handling the previous request, please wait.');
            return;
        } else {
            $('#dhlpwc-label').attr('metabox_busy', 'true');
        }

        $.post(ajaxurl, data, function(response) {

            try {
                view =  response.data.view;
            } catch(error) {
                alert('Error');
                return;
            }

            $('div.dhlpwc-order-metabox-content').html(view);
            $('#dhlpwc-label').attr('metabox_busy', 'false');

        }, 'json');

    }).on('click', '.dhlpwc_action_delete', function(e) {
        e.preventDefault();

        var data = {
            'action': 'dhlpwc_label_delete',
            post_id: $(this).data('post-id'),
            label_id: $(this).attr('label-id')
        };

        // Click-2-much prevention
        if ($('#dhlpwc-label').attr('metabox_busy') == 'true') {
            alert('Currently handling the previous request, please wait.');
            return;
        } else {
            $('#dhlpwc-label').attr('metabox_busy', 'true');
        }

        $.post(ajaxurl, data, function(response) {

            try {
                view = response.data.view;
            } catch(error) {
                alert('Error');
                return;
            }

            $('div.dhlpwc-order-metabox-content').html(view);
            $('#dhlpwc-label').attr('metabox_busy', 'false');

        }, 'json');

    }).on('click', '.dhlpwc_action_refresh', function(e) {
        e.preventDefault();

        var label_options = [];
        $("input[name='dhlpwc-label-create-option[]']:checked").each(function() {
            label_options.push($(this).val().toString());
        });

        var to_business = $("input[name='dhlpwc-label-create-to-business']").is(':checked') ?  'yes' : 'no';

        var data = {
            'action': 'dhlpwc_label_refresh',
            post_id: $(this).data('post-id'),
            label_options: label_options,
            to_business: to_business
        };

        // Click-2-much prevention
        if ($('#dhlpwc-label').attr('metabox_busy') == 'true') {
            alert('Currently handling the previous request, please wait.');
            return;
        } else {
            $('#dhlpwc-label').attr('metabox_busy', 'true');
        }

        $.post(ajaxurl, data, function(response) {

            try {
                view = response.data.view;
            } catch(error) {
                alert('Error');
                return;
            }

            $('div.dhlpwc-order-metabox-form-parceltypes > .dhlpwc-form-content').html(view);
            $('#dhlpwc-label').attr('metabox_busy', 'false');

        }, 'json');
    });


});
