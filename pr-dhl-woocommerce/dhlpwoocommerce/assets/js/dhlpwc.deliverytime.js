jQuery(document).ready(function($) {

    $(document.body).on('change', 'div.dhlpwc-shipping-method-delivery-times-option select', function(e) {
        var selected_option = $(this).find('option:selected');
        $(document.body).trigger('dhlpwc:delivery_time_selection_sync', [selected_option.val().toString(), selected_option.data('date'), selected_option.data('start-time'), selected_option.data('end-time'), selected_option.data('frontend-id')]);

    }).on('dhlpwc:delivery_time_check_sync', function(e) {
        var selected_option = $('div.dhlpwc-shipping-method-delivery-times-option select option:selected');
        if (selected_option.length !== 0) {
            $(document.body).trigger('dhlpwc:delivery_time_selection_sync', [selected_option.val().toString(), selected_option.data('date'), selected_option.data('start-time'), selected_option.data('end-time'), selected_option.data('frontend-id')]);
        }

    }).on('dhlpwc:delivery_time_selection_sync', function(e, selected, date, start_time, end_time, frontend_id) {
        // Due to the cart page not having an actual form, we will temporarily remember the selection as a shadow selection.
        // The actual checkout form will always have priority, this is just backup logic.
        var data = {
            'action': 'dhlpwc_delivery_time_selection_sync',
            'selected': selected,
            'date': date,
            'start_time': start_time,
            'end_time': end_time
        };

        $.post(dhlpwc_delivery_time_object.ajax_url, data, function (response) {
            // Select matching shipping method
            $('[id^=shipping_method_][id$=_dhlpwc-'+frontend_id+']').attr('selected', 'selected').trigger("click");
        });

    }).on('dhlpwc:update_delivery_time_visibility', function() {
        // Show / hide shipping methods based on time and selected shipping method
        if ($('[id^=shipping_method_][id$=_dhlpwc-home]').is(':checked')) {

            $('[id^=shipping_method_][id$=_dhlpwc-home]').closest('li').show();
            $('[id^=shipping_method_][id$=_dhlpwc-home-evening]').closest('li').hide();
            $('[id^=shipping_method_][id$=_dhlpwc-home-same-day]').closest('li').hide();

            $('[id^=shipping_method_][id$=_dhlpwc-home-o-neighbour]').closest('li').show();
            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour-evening]').closest('li').hide();
            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour-same-day]').closest('li').hide();

        } else if ($('[id^=shipping_method_][id$=_dhlpwc-home-evening]').is(':checked')) {

            $('[id^=shipping_method_][id$=_dhlpwc-home]').closest('li').hide();
            $('[id^=shipping_method_][id$=_dhlpwc-home-evening]').closest('li').show();
            $('[id^=shipping_method_][id$=_dhlpwc-home-same-day]').closest('li').hide();

            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour]').closest('li').show();
            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour-evening]').closest('li').hide();
            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour-same-day]').closest('li').hide();

        } else if ($('[id^=shipping_method_][id$=_dhlpwc-home-same-day]').is(':checked')) {
            $('[id^=shipping_method_][id$=_dhlpwc-home]').closest('li').hide();

            $('[id^=shipping_method_][id$=_dhlpwc-home-evening]').closest('li').hide();
            $('[id^=shipping_method_][id$=_dhlpwc-home-same-day]').closest('li').show();

            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour]').closest('li').show();
            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour-evening]').closest('li').hide();
            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour-same-day]').closest('li').hide();

        } else if ($('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour]').is(':checked')) {

            $('[id^=shipping_method_][id$=_dhlpwc-home]').closest('li').show();
            $('[id^=shipping_method_][id$=_dhlpwc-home-evening]').closest('li').hide();
            $('[id^=shipping_method_][id$=_dhlpwc-home-same-day]').closest('li').hide();

            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour]').closest('li').show();
            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour-evening]').closest('li').hide();
            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour-same-day]').closest('li').hide();

        } else if ($('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour-evening]').is(':checked')) {

            $('[id^=shipping_method_][id$=_dhlpwc-home]').closest('li').show();
            $('[id^=shipping_method_][id$=_dhlpwc-home-evening]').closest('li').hide();
            $('[id^=shipping_method_][id$=_dhlpwc-home-same-day]').closest('li').hide();

            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour]').closest('li').hide();
            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour-evening]').closest('li').show();
            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour-same-day]').closest('li').hide();

        } else if ($('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour-same-day]').is(':checked')) {

            $('[id^=shipping_method_][id$=_dhlpwc-home]').closest('li').show();
            $('[id^=shipping_method_][id$=_dhlpwc-home-evening]').closest('li').hide();
            $('[id^=shipping_method_][id$=_dhlpwc-home-same-day]').closest('li').hide();

            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour]').closest('li').hide();
            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour-evening]').closest('li').hide();
            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour-same-day]').closest('li').show();

        } else {

            $('[id^=shipping_method_][id$=_dhlpwc-home]').closest('li').show();
            $('[id^=shipping_method_][id$=_dhlpwc-home-evening]').closest('li').hide();
            $('[id^=shipping_method_][id$=_dhlpwc-home-same-day]').closest('li').hide();

            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour]').closest('li').show();
            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour-evening]').closest('li').hide();
            $('[id^=shipping_method_][id$=_dhlpwc-home-no-neighbour-same-day]').closest('li').hide();
        }

    }).on('dhlpwc:update_delivery_times_style', function() {
        // Prettify select2 is selectWoo is active
        if (dhlpwc_delivery_time_object.select_woo_active !== 'true') {
            return;
        }

        $('.dhlpwc-shipping-method-delivery-times-option select').each(function (i, obj) {
            if (!$(obj).data('select2')) {
                $('.dhlpwc-shipping-method-delivery-times-option select').select2({
                    width: '90%',
                    minimumResultsForSearch: Infinity
                });
                return false;
            }
        });

    }).on('updated_cart_totals', function() {
        $(document.body).trigger('dhlpwc:delivery_time_check_sync');
        $(document.body).trigger('dhlpwc:update_delivery_time_visibility');
        $(document.body).trigger('dhlpwc:update_delivery_times_style');

    }).on('updated_checkout', function() {
        $(document.body).trigger('dhlpwc:delivery_time_check_sync');
        $(document.body).trigger('dhlpwc:update_delivery_time_visibility');
        $(document.body).trigger('dhlpwc:update_delivery_times_style');

    });

    $(document.body).trigger('dhlpwc:delivery_time_check_sync');
    $(document.body).trigger('dhlpwc:update_delivery_time_visibility');
    $(document.body).trigger('dhlpwc:update_delivery_times_style');

});
