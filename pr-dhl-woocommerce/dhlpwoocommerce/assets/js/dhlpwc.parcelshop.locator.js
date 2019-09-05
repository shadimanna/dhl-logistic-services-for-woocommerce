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

        if (typeof  window.dhlpwc_reset_servicepoint === "function") {
            var options = {
                host: dhlpwc_parcelshop_locator.gateway,
                apiKey: dhlpwc_parcelshop_locator.google_map_key,
                query: $('.dhlpwc-shipping-method-parcelshop-option').data('search-value'),
                countryCode: $('.dhlpwc-shipping-method-parcelshop-option').data('country-code'),
                limit: dhlpwc_parcelshop_locator.limit,
                tr: function (i) {
                    return dhlpwc_parcelshop_locator.translations[i.toLowerCase()];
                }
            };

            // Use the generated function provided by the component to load the ServicePoints
            window.dhlpwc_reset_servicepoint(options);

            $('div.dhlpwc-modal').show();
        } else {
            console.log('An unexpected error occured. ServicePoint functions were not loaded.');
        }

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

                if (typeof event.shopType !== 'undefined' && event.shopType === 'packStation' && event.address.countryCode === 'DE') {
                    var dhlpwc_additional_parcelshop_id = prompt("Add your 'postnumber' for delivery at a DHL Packstation:");
                    if (dhlpwc_additional_parcelshop_id != null && dhlpwc_additional_parcelshop_id != '') {
                        dhlpwc_selected_parcelshop_id = dhlpwc_selected_parcelshop_id + '|' + dhlpwc_additional_parcelshop_id;
                        $(document.body).trigger("dhlpwc:add_parcelshop_component_confirm_button");

                        event.name = event.keyword + ' ' + dhlpwc_additional_parcelshop_id;
                        $(document.body).trigger("dhlpwc:parcelshop_selection_sync", [dhlpwc_selected_parcelshop_id, event.address.countryCode]);
                    } else {
                        $(document.body).trigger("dhlpwc:parcelshop_selection_sync", [null, null]);
                        $(document.body).trigger('dhlpwc:hide_parcelshop_selection_modal');
                    }
                } else {
                    $(document.body).trigger("dhlpwc:add_parcelshop_component_confirm_button");
                    $(document.body).trigger("dhlpwc:parcelshop_selection_sync", [dhlpwc_selected_parcelshop_id, event.address.countryCode]);
                }
            };

            // Disable getScript from adding a custom timestamp
            $.ajaxSetup({cache: true});
            $.getScript("https://servicepoint-locator.dhlparcel.nl/servicepoint-locator.js").done(function() {
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

        $.post(dhlpwc_parcelshop_locator.ajax_url, data, function (response) {
            // $(document.body).trigger('dhlpwc:display_parcelshop', [parcelshop_id]);
            /* Cart page */
            $(document.body).trigger("wc_update_cart");
            /* Checkout page */
            $(document.body).trigger("update_checkout");
            /* Check if auto closing is needed */
            $(document.body).trigger("dhlpwc:check_autoclose_modal");

        });

    }).on('dhlpwc:check_autoclose_modal', function() {
        if ($('.dhl-parcelshop-locator .dhl-parcelshop-locator-desktop ul .dhlpwc-parcelshop-component-confirm-button').is(':hidden')) {
            /* There is no visible confirm button, auto close any open modals after selecting a parcelshop */
            $(document.body).trigger('dhlpwc:hide_parcelshop_selection_modal');
        }

    });

    // Preload modal, since it's loaded dynamically (hidden DOM defaults)
    $(document.body).trigger('dhlpwc:load_parcelshop_selection_modal');

});
