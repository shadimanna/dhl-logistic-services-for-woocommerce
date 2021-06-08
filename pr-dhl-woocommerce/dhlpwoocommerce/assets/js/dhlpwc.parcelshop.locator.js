jQuery(document).ready(function($) {
    var dhlpwc_parcelshop_selection_modal_loading_busy = false;
    var dhlpwc_parcelshop_selection_modal_loaded = false;

    if ($('[id^=shipping_method_][id$=_dhlpwc-parcelshop]').length > 0) {
        var dhlpwc_shipping_input_name = $('[id^=shipping_method_][id$=_dhlpwc-parcelshop]').attr('name').replace(/(:|\.|\[|\])/g,'\\$1');
    } else {
        var dhlpwc_shipping_input_name = '_unavailable_option_';
    }

    $(document.body).on('change', 'input[type=radio][name='+dhlpwc_shipping_input_name+']', function() {
        if ($('[id^=shipping_method_][id$=_dhlpwc-parcelshop]').is(':checked')) {
            $(document.body).trigger('dhlpwc:load_parcelshop_selection_modal');
        }

    }).on('dhlpwc:show_parcelshop_selection_modal', function(e) {
        // Do nothing if the base modal hasn't been loaded yet.
        if (dhlpwc_parcelshop_selection_modal_loaded === false) {
            return;
        }

        var searchQuery = $('.dhlpwc-shipping-method-parcelshop-option').data('search-value');
        var country = $('.dhlpwc-shipping-method-parcelshop-option').data('country-code');

        if (typeof window.dhlparcel_shipping_servicepoint_locator !== 'undefined') {
            window.dhlparcel_shipping_servicepoint_locator.setCountry(country)
            if (searchQuery != '') {
                window.dhlparcel_shipping_servicepoint_locator.setQuery(searchQuery)
            }
        }

        $('div.dhlpwc-modal').show();

    }).on('dhlpwc:add_parcelshop_component_confirm_button', function() {
        if ($('.dhl-parcelshop-locator .dhl-parcelshop-locator-desktop ul .dhlpwc-parcelshop-component-confirm-button').length === 0) {
            $('.dhl-parcelshop-locator .dhl-parcelshop-locator-desktop ul').prepend(dhlpwc_parcelshop_locator.confirm_button);
        }

    }).on('click', '.dhlpwc-parcelshop-component-confirm-button', function(e) {
        e.preventDefault();
        $(document.body).trigger('dhlpwc:hide_parcelshop_selection_modal');

    }).on('dhlpwc:load_parcelshop_selection_modal', function() {
        if (dhlpwc_parcelshop_selection_modal_loaded === true) {
            return;
        }

        if (dhlpwc_parcelshop_selection_modal_loading_busy === true) {
            return;
        }

        dhlpwc_parcelshop_selection_modal_loading_busy = true;
        $('.dhlpwc-parcelshop-option-change').addClass('dhlpwc-still-loading');

        var data = {
            'action': 'dhlpwc_load_parcelshop_selection'
        };

        $.post(dhlpwc_parcelshop_locator.ajax_url, data, function (response) {
            try {
                view = response.data.view;
            } catch (error) {
                alert('Error');
                return;
            }

            $(document.body).append(view);

            /* Set background image dynamically */
            $('.dhlpwc-modal-content').css('background-image', 'url(' + dhlpwc_parcelshop_locator.modal_background + ')');

            // Create selection function
            window.dhlpwc_select_servicepoint = function(event)
            {
                var dhlpwc_selected_parcelshop_id = event.id;

                $(document.body).trigger("dhlpwc:parcelshop_selection_sync", [dhlpwc_selected_parcelshop_id, event.address.countryCode]);
                $(document.body).trigger('dhlpwc:hide_parcelshop_selection_modal');
            };

            // Disable getScript from adding a custom timestamp
            $.ajaxSetup({cache: true});
            $.getScript("https://static.dhlparcel.nl/components/servicepoint-locator-component@latest/servicepoint-locator-component.js").done(function() {
                var dhlpwc_parcelshop_locator_language = 'en'
                if (typeof dhlpwc_parcelshop_locator.language !== 'undefined' && dhlpwc_parcelshop_locator.language.length > 0) {
                    dhlpwc_parcelshop_locator_language = dhlpwc_parcelshop_locator.language
                }
                var options = {
                    language: dhlpwc_parcelshop_locator_language,
                    country: '',
                    limit: dhlpwc_parcelshop_locator.limit,
                    header: false,
                    resizable: true,
                    onSelect: window.dhlpwc_select_servicepoint
                };

                if (typeof dhlpwc_parcelshop_locator.google_map_key !== 'undefined' && dhlpwc_parcelshop_locator.google_map_key !== null) {
                    options.googleMapsApiKey = dhlpwc_parcelshop_locator.google_map_key;
                }

                window.dhlparcel_shipping_servicepoint_locator = new dhl.servicepoint.Locator(document.getElementById("dhl-servicepoint-locator-component"), options);

                dhlpwc_parcelshop_selection_modal_loaded = true;
                dhlpwc_parcelshop_selection_modal_loading_busy = false;
                $('.dhlpwc-parcelshop-option-change').removeClass('dhlpwc-still-loading');
            });
        }, 'json');

    }).on('click', '.dhlpwc-parcelshop-option-change', function(e) {
        e.preventDefault();
        if ($('[id^=shipping_method_][id$=_dhlpwc-parcelshop]').is(':checked')) {
            $(document.body).trigger('dhlpwc:show_parcelshop_selection_modal');
        }

    }).on('click', 'span.dhlpwc-modal-close', function(e) {
        e.preventDefault();
        $(document.body).trigger('dhlpwc:hide_parcelshop_selection_modal');

    }).on('dhlpwc:hide_parcelshop_selection_modal', function(e) {
        $('div.dhlpwc-modal').hide();

    }).on('dhlpwc:parcelshop_selection_sync', function(e, parcelshop_id, country_code) {
        // Due to the cart page not having an actual form, we will temporarily remember the selection as a shadow selection.
        // The actual checkout form will always have priority, this is just backup logic.
        var data = {
            'action': 'dhlpwc_parcelshop_selection_sync',
            'parcelshop_id': parcelshop_id,
            'country_code': country_code
        };

        if (country_code === '' || parcelshop_id === '') {
            return;
        }

        $.post(dhlpwc_parcelshop_locator.ajax_url, data, function (response) {
            // $(document.body).trigger('dhlpwc:display_parcelshop', [parcelshop_id]);
            /* Cart page */
            $(document.body).trigger("wc_update_cart");
            /* Checkout page */
            $(document.body).trigger("update_checkout");

        });

    });

    // Preload modal, since it's loaded dynamically (hidden DOM defaults)
    $(document.body).trigger('dhlpwc:load_parcelshop_selection_modal');

});
