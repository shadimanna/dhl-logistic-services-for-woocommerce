jQuery(document).ready(function ($) {
    var dhlpwc_parcelshop_selector_timeout = null;

    var dhlpwc_shipping_input_name = '_unavailable_option_';
    if ($('[id^=shipping_method_][id$=_dhlpwc-parcelshop]').length > 0) {
        dhlpwc_shipping_input_name = $('[id^=shipping_method_][id$=_dhlpwc-parcelshop]').attr('name')
                                                                                        .replace(/(:|\.|\[|\])/g, '\\$1');
    }

    $(document.body).on('click', '.dhlpwc-parcelshop-option-change', function (e) {
        e.preventDefault();
        if ($('[id^=shipping_method_][id$=_dhlpwc-parcelshop]').is(':checked')) {
            var container = $('.dhlpwc-parcelshop-option-list');
            $(document).mouseup(function (e) {
                if (!container.is(e.target) && container.has(e.target).length === 0) {
                    container.hide();
                }
            });
            container.toggle();
            $(document.body).trigger('dhlpwc:show_parcelshop_selector_refresh');
        }

    }).on('click', '.dhlpwc-parcelshop-option-list-item', function (e) {
        e.preventDefault();
        if ($('[id^=shipping_method_][id$=_dhlpwc-parcelshop]').is(':checked')) {
            var dhlpwc_selected_parcelshop_id = $(this).attr('data-parcelshop-id');
            var parcelshop_type = $(this).attr('data-parcelshop-type');
            var country_code = $('.dhlpwc-shipping-method-parcelshop-option').data('country-code');

            if (typeof parcelshop_type !== 'undefined' && parcelshop_type === 'packStation' && country_code === 'DE') {
                var dhlpwc_additional_parcelshop_id = prompt("Add your 'postnumber' for delivery at a DHL Packstation:");
                if (dhlpwc_additional_parcelshop_id != null && dhlpwc_additional_parcelshop_id != '') {
                    dhlpwc_selected_parcelshop_id += '|' + dhlpwc_additional_parcelshop_id;
                    $(document.body).trigger("dhlpwc:parcelshop_selection_sync", [dhlpwc_selected_parcelshop_id, country_code]);
                    $('.dhlpwc-parcelshop-option-list').hide();
                }else{
                    alert("'postnumber' is required");
                }
            } else {
                $(document.body).trigger('dhlpwc:parcelshop_selection_sync', [dhlpwc_selected_parcelshop_id, country_code]);
                $('.dhlpwc-parcelshop-option-list').hide();
            }
        }
        
    }).on('keyup keypress', '.dhlpwc-parcelshop-option-list-search input', function (e) {
        // Prevent ENTER key from doing anything
        var keyCode = e.keyCode || e.which;
        if (keyCode === 13) { 
            e.preventDefault();
            return false;
        }
        
    }).on('keyup', '.dhlpwc-parcelshop-option-list-search input', function (e) {
        if ($('[id^=shipping_method_][id$=_dhlpwc-parcelshop]').is(':checked')) {
            clearTimeout(dhlpwc_parcelshop_selector_timeout);
            dhlpwc_parcelshop_selector_timeout = setTimeout(function () {
                $(document.body).trigger('dhlpwc:show_parcelshop_selector_refresh');
            }, 500)
        }

    }).on('dhlpwc:show_parcelshop_selector_refresh', function () {
        var url = dhlpwc_parcelshop_selector.servicepoint_url + $('.dhlpwc-shipping-method-parcelshop-option')
                .data('country-code') +
            '?limit=' + dhlpwc_parcelshop_selector.limit + '&fuzzy=' + encodeURI($('.dhlpwc-parcelshop-option-list-search input')
                .val())

        $.get(url, function (data) {
            // Remove existing elements from list before appending
            $('.dhlpwc-parcelshop-option-list-item.parcelshop-option').remove();
            data.forEach(function (parcelShop) {
                var parcelShopLocation = (parcelShop.address.countryCode === 'FR' ? parcelShop.address.number + ' ' + parcelShop.address.street : parcelShop.address.street + ' ' + parcelShop.address.number) +
                    ', ' + parcelShop.address.zipCode + ', ' + parcelShop.address.city;
                $('.dhlpwc-parcelshop-option-list')
                    .append('<div class="dhlpwc-parcelshop-option-list-item parcelshop-option" data-parcelshop-type="' + parcelShop.shopType + '" data-parcelshop-id="' + parcelShop.id + '">' +
                        '<strong>' + parcelShop.name + '</strong><br>' +
                        '<span>' + parcelShopLocation + '</span></div>')
            });
        }, 'json')
    }).on('dhlpwc:parcelshop_selection_sync', function (e, parcelshop_id, country_code) {
        // Due to the cart page not having an actual form, we will temporarily remember the selection as a shadow selection.
        // The actual checkout form will always have priority, this is just backup logic.
        var data = {
            'action': 'dhlpwc_parcelshop_selection_sync',
            'parcelshop_id': parcelshop_id,
            'country_code': country_code
        };

        $.post(dhlpwc_parcelshop_selector.ajax_url, data, function (response) {
            /* Cart page */
            $(document.body).trigger("wc_update_cart");
            /* Checkout page */
            $(document.body).trigger("update_checkout");

        });

    })

});
