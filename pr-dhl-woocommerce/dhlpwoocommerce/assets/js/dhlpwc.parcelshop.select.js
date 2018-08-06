jQuery(document).ready(function($) {

    var dhlpwc_parcelshop_selection_modal_loading_busy = false;
    var dhlpwc_parcelshop_selection_modal_loaded = false;
    if ($('[id^=shipping_method_][id$=_dhlpwc-parcelshop]').length > 0) {
        var dhlpwc_shipping_input_name = $('[id^=shipping_method_][id$=_dhlpwc-parcelshop]').attr('name').replace(/(:|\.|\[|\])/g,'\\$1');
    }

    $(document.body).on('change', 'input[type=radio][name='+dhlpwc_shipping_input_name+']', function() {
        if ($('[id^=shipping_method_][id$=_dhlpwc-parcelshop]').is(':checked')) {
            $(document.body).trigger('dhlpwc:load_parcelshop_selection_modal');
        }

    }).on('dhlpwc:load_parcelshop_selection_modal', function() {
        if (dhlpwc_parcelshop_selection_modal_loaded === true) {
            return;
        }

        if (dhlpwc_parcelshop_selection_modal_loading_busy === true) {
            return;
        }

        dhlpwc_parcelshop_selection_modal_loading_busy = true;

        var data = {
            'action': 'dhlpwc_load_parcelshop_selection'
        };

        $.post(dhlpwc_frontend_select.ajax_url, data, function (response) {
            try {
                view = response.data.view;
            } catch (error) {
                alert('Error');
                return;
            }

            $(document.body).append(view);
            /* Add Search button to postcode check */
            $('input#dhlpwc_parcelshop_postcode').after('<button type="button" id="dhlpwc_parcelshop_postcode_search_button">' + dhlpwc_frontend_select.search_default_text + '</button>');
            $('p#dhlpwc_parcelshop_select_field').after('<button type="button" id="dhlpwc_parcelshop_postcode_confirm_button">' + dhlpwc_frontend_select.confirm_default_text + '</button>');
            /* Set background image dynamically */
            $('.dhlpwc-modal-content').css('background-image', 'url(' + dhlpwc_frontend_select.modal_background + ')');

            dhlpwc_parcelshop_selection_modal_loaded = true;
            dhlpwc_parcelshop_selection_modal_loading_busy = false;

            $(document.body).trigger('dhlpwc:init_parcelshop_map');
        }, 'json');

    }).on('dhlpwc:start_searching_parcelshop', function(e) {
        $('#dhlpwc_parcelshop_postcode_search_button').addClass('dhlpwc-searching');
        $('#dhlpwc_parcelshop_postcode_search_button').html('<img src="' + dhlpwc_frontend_select.search_loader_image + '"/>');

    }).on('dhlpwc:stop_searching_parcelshop', function(e) {
        $('#dhlpwc_parcelshop_postcode_search_button').removeClass('dhlpwc-searching');
        $('#dhlpwc_parcelshop_postcode_search_button').html(dhlpwc_frontend_select.search_default_text);

    }).on('dhlpwc:show_parcelshop_selection_modal', function(e) {
        $('div.dhlpwc-modal').show();
        $(document.body).trigger('dhlpwc:show_parcelshop_map_section');

    }).on('click', 'span.dhlpwc-modal-close', function(e) {
        e.preventDefault();
        $(document.body).trigger('dhlpwc:hide_parcelshop_selection_modal');

    }).on('click', '#dhlpwc_parcelshop_postcode_confirm_button', function(e) {
        e.preventDefault();
        $(document.body).trigger('dhlpwc:hide_parcelshop_selection_modal');

    }).on('dhlpwc:hide_parcelshop_selection_modal', function(e) {
        $('div.dhlpwc-modal').hide();

    }).on('dhlpwc:select_parcelshop', function(e, parcelshop_id) {
        var country = $('#dhlpwc-parcelshop-option-country-select').val();
        $(document.body).trigger("dhlpwc:parcelshop_passive_sync", [parcelshop_id, country]);

    }).on('dhlpwc:parcelshop_passive_sync', function(e, parcelshop_id, country) {
        // Due to the cart page not having an actual form, we will temporarily remember the selection as a shadow selection.
        // The actual checkout form will always have priority, this is just backup logic.
        var data = {
            'action': 'dhlpwc_parcelshop_passive_sync',
            'parcelshop_id': parcelshop_id,
            'country': country
        };

        $.post(dhlpwc_frontend_select.ajax_url, data, function (response) {
            $(document.body).trigger('dhlpwc:display_parcelshop', [parcelshop_id]);
            /* Cart page */
            $(document.body).trigger("wc_update_cart");
            /* Checkout page */
            $(document.body).trigger("update_checkout");
        });

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

    }).on('dhlpwc:show_parcelshops_select', function(e, geo_locations) {
        $('.dhlpwc-checkout-subsection-sub-side').css('visibility', 'visible');
        $('.dhlpwc-checkout-subsection-sub-side').show();

    }).on('dhlpwc:clear_parcelshop_select', function() {
        var select = $('select#dhlpwc_parcelshop_select');
        select.find('option:not(:first)').remove();

    }).on('dhlpwc:hide_parcelshop_select', function() {
        $('.dhlpwc-checkout-subsection-sub-side').css('visibility', 'hidden');

    }).on('click', '.dhlpwc-parcelshop-option-change', function(e) {
        e.preventDefault();
        if ($('[id^=shipping_method_][id$=_dhlpwc-parcelshop]').is(':checked')) {
            $(document.body).trigger('dhlpwc:show_parcelshop_selection_modal');
        }

    }).on('dhlpwc:add_parcelshop_select_observer', function(e) {
        var dhlpwc_parcelshop_select2_observer_target = document.querySelector('div.cart-collaterals') || document.querySelector('div#order_review');
        if (dhlpwc_parcelshop_select2_observer_target == null) {
            return;
        }

        var dhlpwc_parcelshop_select2_observer_config = {attributes: true, childList: true, characterData: true};
        var dhlpwc_parcelshop_select2_observer = new MutationObserver(function (mutations) {
            $('#dhlpwc-parcelshop-option-country-select').each(function (i, obj) {
                if (!$(obj).data('select2')) {
                    $('#dhlpwc-parcelshop-option-country-select').select2();
                    return false;
                }
            });
        });

        dhlpwc_parcelshop_select2_observer.observe(
            dhlpwc_parcelshop_select2_observer_target,
            dhlpwc_parcelshop_select2_observer_config
        );

    }).on('dhlpwc:remove_parcelshop_select_observer', function(e) {
        dhlpwc_parcelshop_select2_observer.destroy();
        $(document.body).trigger('dhlpwc:add_parcelshop_select_observer');

    }).on('change', '#dhlpwc-parcelshop-option-country-select', function(e) {
        $(document.body).trigger('dhlpwc:reset_parcelshop_select');

    }).on('dhlpwc:reset_parcelshop_select', function(e) {
        $('input#dhlpwc_parcelshop_postcode').val('');
        $(document.body).trigger('dhlpwc:hide_parcelshop_select');
        $(document.body).trigger('dhlpwc:hide_parcelshop_map_section');

    });

    // Preload modal, since it's loaded dynamically (hidden DOM defaults)
    $(document.body).trigger('dhlpwc:load_parcelshop_selection_modal');

    // Prettify select2 is selectWoo is active
    if (dhlpwc_frontend_select.select_woo_active == 'true') {
        $('#dhlpwc-parcelshop-option-country-select').select2();
        // Also add an observer to auto-set select2 if it's no longer a select2 element.
        $(document.body).trigger('dhlpwc:add_parcelshop_select_observer');
    }

});
