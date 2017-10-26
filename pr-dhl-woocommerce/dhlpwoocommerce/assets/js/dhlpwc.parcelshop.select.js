jQuery(document).ready(function($) {

    var dhlpwc_shipping_input_name = $('[id^=shipping_method_][id$=_dhlpwc-parcelshop]').attr('name').replace(/(:|\.|\[|\])/g,'\\$1');

    $(document.body).on('dhlpwc:select_parcelshop', function(e, parcelshop_id) {
        console.log( parcelshop_id );

        // Update hidden input box
        var input = $('select#dhlpwc_parcelshop_select');
        input.val(parcelshop_id);
        input.trigger('change');
        input.trigger('dhlpwc:display_parcelshop', [parcelshop_id]);
        $(document.body).trigger('update_checkout');

    }).on('dhlpwc:display_parcelshop', function(e, parcelshop_id) {
        // Display custom input selection
        $(document.body).trigger('dhlpwc:display_parcelshop_stylishselect', [parcelshop_id]);
        // Display on map
        $(document.body).trigger('dhlpwc:display_parcelshop_map', [parcelshop_id]);
        // Display info
        $(document.body).trigger('dhlpwc:display_parcelshop_info', [parcelshop_id]);

    }).on('dhlpwc:add_parcelshops_select', function(e, geo_locations) {
        var select = $('select#dhlpwc_parcelshop_select');
        $.each(geo_locations, function(key, geo_location) {
            select.append($("<option/>").val(key).text(geo_location.description));
        });

    }).on('dhlpwc:clear_parcelshop_select', function() {
        var select = $('select#dhlpwc_parcelshop_select');
        select.find('option:not(:first)').remove();

    }).on('dhlpwc:display_parcelshop_info', function(e, parcelshop_id) {
        var data = {
            'action': 'dhlpwc_parcelshop_info',
            parcelshop_id: parcelshop_id
        };

        $('#dhlpwc-parcelshop-info').animate({opacity: 0});

        $.post(dhlpwc_frontend_select.ajax_url, data, function(response) {
            try {
                view =  response.data.view;
            } catch(error) {
                alert('Error');
                return;
            }

            $('#dhlpwc-parcelshop-info').html(view);
            $('#dhlpwc-parcelshop-info').animate({opacity: 1});
        }, 'json');

    }).on('change', 'input[type=radio][name='+dhlpwc_shipping_input_name+']', function() {
        if ($('[id^=shipping_method_][id$=_dhlpwc-parcelshop]').is(':checked')) {
            if ($('#dhlpwc_parcelshop_section_check').is(':checked') == false) {
                $('#dhlpwc_parcelshop_section_check').attr('checked', true);
                $('#dhlpwc_parcelshop_section_check').trigger('change');
            }
        } else {
            if ($('#dhlpwc_parcelshop_section_check').is(':checked')) {
                $('#dhlpwc_parcelshop_section_check').attr('checked', false);
                $('#dhlpwc_parcelshop_section_check').trigger('change');
            }
        }

    }).on('change', '#dhlpwc_parcelshop_section_check', function() {
        // Open subsection (ignore the smaller subsection) if checked.
        $('div.dhlpwc-checkout-subsection').not('.dhlpwc-checkout-subsection-map').hide();
        if ($(this).is(':checked')) {
            $('div.dhlpwc-checkout-subsection').not('.dhlpwc-checkout-subsection-map').slideDown();
            if ($('[id^=shipping_method_][id$=_dhlpwc-parcelshop]').is(':checked') == false) {
                $('[id^=shipping_method_][id$=_dhlpwc-parcelshop]').attr('checked', true);
            }
        } else {
            $('select#dhlpwc_parcelshop_select').val('');
            $(document.body).trigger('dhlpwc:hide_parcelshop_map_section');

            if ($('[id^=shipping_method_][id$=_dhlpwc-parcelshop]').is(':checked')) {
                $('[id^=shipping_method_][id$=_dhlpwc-parcelshop]').attr('checked', false);
            }
        }

    });

    $('#dhlpwc_parcelshop_section_check').trigger('change');

    if ($('select#dhlpwc_parcelshop_select').val().length > 3) {
        $(document.body).trigger('dhlpwc:select_parcelshop', [$('select#dhlpwc_parcelshop_select').val()]);
    }

});

