jQuery(document).ready(function($) {

    var dhlpwc_settings_menu_collection = [];
    var dhlpwc_test_connection_button = $("input#woocommerce_dhlpwc_test_connection");

    $(document.body).on('click', 'input#woocommerce_dhlpwc_test_connection', function(e) {
        e.preventDefault();

        var user_id = $("#woocommerce_dhlpwc_user_id").val();
        var key = $("#woocommerce_dhlpwc_key").val();

        var data = $.extend(true, $(this).data(), {
            action: 'dhlpwc_test_connection',
            // security: $( '#dhlpwc-ajax-nonce' ).val(),
            user_id: user_id,
            key: key
        });

        dhlpwc_test_connection_button.val(dhlpwc_settings_object.test_connection_loading_message);
        $(document.body).trigger('dhlpwc:disable_test_connection_button');

        $.post(ajaxurl, data, function (response) {

            try {
                var success = response.data.success;
                var message = response.data.message;
                var info = response.data.info;
            } catch (error) {
                alert('Error');
                return;
            }

            $(document.body).trigger('dhlpwc:enable_test_connection_button');

            if (success === 'true') {
                dhlpwc_test_connection_button.val(message);
                dhlpwc_test_connection_button.addClass('dhlpwc_button_success');

                var dhlpwc_account_area = $('input#woocommerce_dhlpwc_account_id').closest('fieldset').parent();
                dhlpwc_account_area.children('div.dhlpwc_settings_suggestion_info').remove();
                dhlpwc_account_area.children('div.dhlpwc_settings_suggestion_accounts').remove();

                if (!$.isEmptyObject(info.accounts)) {
                    dhlpwc_account_area.append('<div class="dhlpwc_settings_suggestion_info">' + dhlpwc_settings_object.accounts_found_message + '</div>');
                    $.each(info.accounts, function (index, value) {
                        dhlpwc_account_area.append('<div class="dhlpwc_settings_suggestion_accounts" data-account-id="' + value.toString() + '">' + value.toString() + '</div>');
                    });

                    // Autofill account if empty
                    if ($('input#woocommerce_dhlpwc_account_id').val().length === 0) {
                        var value = info.accounts[0];
                        $('input#woocommerce_dhlpwc_account_id').val(value);
                    }
                }

                var dhlpwc_organization_area = $('input#woocommerce_dhlpwc_organization_id').closest('fieldset').parent();
                dhlpwc_organization_area.children('div.dhlpwc_settings_suggestion_info').remove();
                dhlpwc_organization_area.children('div.dhlpwc_settings_suggestion_organization').remove();

                if (info.organization_id !== undefined) {
                    var value = info.organization_id;
                    dhlpwc_organization_area.append('<div class="dhlpwc_settings_suggestion_info">' + dhlpwc_settings_object.organization_found_message + '</div>');
                    dhlpwc_organization_area.append('<div class="dhlpwc_settings_suggestion_organization" data-organization-id="' + value.toString() + '">' + value.toString() + '</div>');

                    // Autofill organization if empty
                    if ($('input#woocommerce_dhlpwc_organization_id').val().length === 0) {
                        $('input#woocommerce_dhlpwc_organization_id').val(value);
                    }
                }
                
            } else {
                dhlpwc_test_connection_button.val(message);
                dhlpwc_test_connection_button.addClass('dhlpwc_button_fail');
            }

        }, 'json');

    }).on('click', '.dhlpwc_settings_suggestion_accounts', function(e) {
        var account_id = $(this).data('account-id');
        $('input#woocommerce_dhlpwc_account_id').val(account_id);

    }).on('click', '.dhlpwc_settings_suggestion_organization', function(e) {
        var organization_id = $(this).data('organization-id');
        $('input#woocommerce_dhlpwc_organization_id').val(organization_id);


    }).on('dhlpwc:init_test_connection_button', function(e) {
        if (dhlpwc_test_connection_button === undefined) { return; }
        dhlpwc_test_connection_button.val(dhlpwc_settings_object.test_connection_message);
        dhlpwc_test_connection_button.prop("disabled", false);

    }).on('dhlpwc:enable_test_connection_button', function(e) {
        if (dhlpwc_test_connection_button === undefined) { return; }
        dhlpwc_test_connection_button.prop("disabled", false);

    }).on('dhlpwc:disable_test_connection_button', function(e) {
        if (dhlpwc_test_connection_button === undefined) { return; }
        dhlpwc_test_connection_button.removeClass('dhlpwc_button_success');
        dhlpwc_test_connection_button.removeClass('dhlpwc_button_fail');
        dhlpwc_test_connection_button.prop("disabled", true);

    }).on('dhlpwc:init_settings_menu', function(e) {
        $('#dhlpwc_shipping_method_settings').find('h3').each(function(e) {
            var dhlpwc_settings = {
                title: $(this),
                description: $(this).nextAll('p:first'),
                settings: $(this).nextAll('table:first')
            };

            dhlpwc_settings.title.attr('data-index', e);
            dhlpwc_settings.description.attr('data-index', e);
            dhlpwc_settings.settings.attr('data-index', e);

            // Add to collection
            dhlpwc_settings_menu_collection[e] = dhlpwc_settings;

            // Add triggers
            dhlpwc_settings.title.hover(function() {
                $(document.body).trigger('dhlpwc:highlight_menu', [$(this).data('index')]);
            }, function() {
                $(document.body).trigger('dhlpwc:unhighlight_menu', [$(this).data('index')]);
            });

            dhlpwc_settings.description.hover(function() {
                $(document.body).trigger('dhlpwc:highlight_menu', [$(this).data('index')]);
            }, function() {
                $(document.body).trigger('dhlpwc:unhighlight_menu', [$(this).data('index')]);
            });

            dhlpwc_settings.title.on('click', function() {
                $(document.body).trigger('dhlpwc:deselect_settings');
                $(document.body).trigger('dhlpwc:select_settings', [$(this).data('index')]);
            });

            dhlpwc_settings.description.on('click', function() {
                $(document.body).trigger('dhlpwc:deselect_settings');
                $(document.body).trigger('dhlpwc:select_settings', [$(this).data('index')]);
            });

            // Select first setting
            $(document.body).trigger('dhlpwc:deselect_settings');
            $(document.body).trigger('dhlpwc:select_settings', [0]);
        });

    }).on('dhlpwc:highlight_menu', function(e, index) {
        dhlpwc_settings_menu_collection[index].title.addClass('dhlpwc-highlight');
        dhlpwc_settings_menu_collection[index].description.addClass('dhlpwc-highlight');

    }).on('dhlpwc:unhighlight_menu', function(e, index) {
        dhlpwc_settings_menu_collection[index].title.removeClass('dhlpwc-highlight');
        dhlpwc_settings_menu_collection[index].description.removeClass('dhlpwc-highlight');

    }).on('dhlpwc:select_settings', function(e, index) {
        dhlpwc_settings_menu_collection[index].title.addClass('dhlpwc-active');
        dhlpwc_settings_menu_collection[index].description.addClass('dhlpwc-active');
        dhlpwc_settings_menu_collection[index].settings.css('display', 'inline-block');

    }).on('dhlpwc:deselect_settings', function() {
        $.each(dhlpwc_settings_menu_collection, function(e, dhlpwc_settings) {
            dhlpwc_settings.title.removeClass('dhlpwc-active');
            dhlpwc_settings.description.removeClass('dhlpwc-active');
            dhlpwc_settings.settings.css('display', 'none');
        });
    }).on('dhlpwc:check_global_shipping_settings', function() {
        var use_shipping_zones = $('input#woocommerce_dhlpwc_use_shipping_zones').is(':checked') ?  'yes' : 'no';
        if (use_shipping_zones === 'yes') {
            $('.dhlpwc-global-shipping-setting').each(function(e) {
                $(this).prop('disabled', true);
                $(this).closest('tr').addClass('dhlpwc-disable-global-shipping-setting');
            });
        } else {
            $('.dhlpwc-global-shipping-setting').each(function(e) {
                $(this).prop('disabled', false);
                $(this).closest('tr').removeClass('dhlpwc-disable-global-shipping-setting');
            });
            // After removing all disabled checks, recheck
            $(document.body).trigger('dhlpwc:check_all_option_settings');
        }
    }).on('change', 'input#woocommerce_dhlpwc_use_shipping_zones', function(e) {
        $(document.body).trigger('dhlpwc:check_global_shipping_settings');

    }).on('dhlpwc:init_options_grid', function() {
        // Don't load if the fillable options grid cannot be found
        if ($('.dhlpwc-options-grid table').length < 1) {
            return;
        }
        // Don't load if the grid has already been filled (in case this event is called multiple times)
        if ($('.dhlpwc-options-grid table').find('td').length > 0) {
            return;
        }

        var dhlpwc_option_collection = [];

        $('.dhlpwc-grouped-option').each(function (e) {
            if ($.inArray($(this).data('option-group'), dhlpwc_option_collection) === -1) {
                dhlpwc_option_collection.push($(this).data('option-group'));
            }
            $(this).closest('tr').addClass('dhlpwc-original-grouped-option');
        });

        $.each(dhlpwc_option_collection, function (i, option_identifier) {

            $('.dhlpwc-options-grid table')
                .find('tbody')
                .append($('<tr id="dhlpwc-option-group-mirror-' + option_identifier + '">'));

            $('.dhlpwc-grouped-option[data-option-group="' + option_identifier + '"]').each(function (e) {
                // Create a label assuming the 'enable option' is first and has a label
                if ($(this).attr('id').indexOf('_dhlpwc_enable_option_') > -1) {
                    $(this).closest('tr').find('th').first().find('label').clone()
                        .removeAttr('id for class')
                        .appendTo('#dhlpwc-option-group-mirror-' + option_identifier)
                        .wrap("<td></td>");
                }

                // Create a clone that mirrors the original input box
                $(this).clone()
                    .prop('id', $(this).attr('id') + '-mirror')
                    .bind('change blur', function () {
                        if ($(this).attr('type') === 'checkbox') {
                            $(document.body).trigger('dhlpwc:check_option_setting', [option_identifier]);
                            $('#' + $(this).attr('id').slice(0, -7)).attr('checked', $(this).attr('checked') === 'checked');
                        } else {
                            $('#' + $(this).attr('id').slice(0, -7)).val($(this).val());
                        }
                    })
                    .appendTo('#dhlpwc-option-group-mirror-' + option_identifier)
                    .wrap("<td></td>");
            });
        });

    }).on('dhlpwc:check_option_setting', function(e, code) {
        var checked = $("input[id$='_dhlpwc_enable_option_" + code + "-mirror']").attr('checked') === 'checked';

        if (checked === true) {
            $('.dhlpwc-option-grid\\[\\\'' + code + '\\\'\\]').each(function (e) {
                if ($(this).attr('id').indexOf('_dhlpwc_enable_option_') === -1) { // Skip if it's the enable checkbox
                    if ($(this).attr('id').indexOf('_dhlpwc_free_price_option_') > -1) {
                        if ($("input[id$='_dhlpwc_enable_free_option_" + code + "-mirror']").attr('checked') === 'checked') {
                            $(this).prop('disabled', false);
                        } else {
                            $(this).prop('disabled', true);
                        }
                    } else {
                        $(this).prop('disabled', false);
                        $(this).closest('tr').removeClass('dhlpwc-disable-shipping-option');
                    }
                }
            });
        } else if (checked === false) {
            $('.dhlpwc-option-grid\\[\\\'' + code + '\\\'\\]').each(function (e) {
                if ($(this).attr('id').indexOf('_dhlpwc_enable_option_') === -1) { // Skip if it's the enable checkbox
                    $(this).prop('disabled', true);
                    $(this).closest('tr').addClass('dhlpwc-disable-shipping-option');
                }
            });
        }

    }).on('dhlpwc:check_all_option_settings', function(e) {
        // Don't load if the fillable options grid cannot be found
        if ($('.dhlpwc-options-grid table').length < 1) {
            return;
        }

        // Skip if a field 'use shipping zones' is found and checked
        if ($('input#woocommerce_dhlpwc_use_shipping_zones').is(':checked') ==='yes') {
            return;
        }

        var dhlpwc_option_collection = [];

        $('.dhlpwc-grouped-option').each(function (e) {
            if ($.inArray($(this).data('option-group'), dhlpwc_option_collection) === -1) {
                dhlpwc_option_collection.push($(this).data('option-group'));
            }
        });

        $.each(dhlpwc_option_collection, function (i, option_identifier) {
            $(document.body).trigger('dhlpwc:check_option_setting', [option_identifier]);
        });

    }).on('init_tooltips', function(e) {
        // Hook to a default WooCommerce event.
        // Unfortunately this event is called more often than wanted, so init_options_grid has been
        // modified to make sure it only runs when certain divs can be found
        //
        // This js has also been limited to only 2 specific pages (dhlpwc settings page and shipping zone edit page)
        $(document.body).trigger('dhlpwc:init_options_grid');
        $(document.body).trigger('dhlpwc:check_all_option_settings');

    }).on('change', 'input#woocommerce_dhlpwc_enable_alternate_return_address', function(e) {
        $(document.body).trigger('dhlpwc:check_return_address');

    }).on('dhlpwc:check_return_address', function() {
        var use_alternate_return_address = $('input#woocommerce_dhlpwc_enable_alternate_return_address').is(':checked') ?  'yes' : 'no';
        if (use_alternate_return_address === 'yes') {
            $('.dhlpwc-return-address-setting').each(function(e) {
                $(this).prop('disabled', false);
                $(this).closest('tr').removeClass('dhlpwc-hide-return-address-setting');
            });
        } else {
            $('.dhlpwc-return-address-setting').each(function (e) {
                $(this).prop('disabled', true);
                $(this).closest('tr').addClass('dhlpwc-hide-return-address-setting');
            });
        }

    }).on('change', 'input#woocommerce_dhlpwc_default_hide_sender_address', function(e) {
        $(document.body).trigger('dhlpwc:check_hide_sender_address');

    }).on('dhlpwc:check_hide_sender_address', function() {
        var default_hide_sender_address = $('input#woocommerce_dhlpwc_default_hide_sender_address').is(':checked') ?  'yes' : 'no';
        if (default_hide_sender_address === 'yes') {
            $('.dhlpwc-hide-sender-address-setting').each(function(e) {
                $(this).prop('disabled', false);
                $(this).closest('tr').removeClass('dhlpwc-hide-hide-sender-address-setting');
            });
        } else {
            $('.dhlpwc-hide-sender-address-setting').each(function (e) {
                $(this).prop('disabled', true);
                $(this).closest('tr').addClass('dhlpwc-hide-hide-sender-address-setting');
            });
        }

    }).on('change', 'input#woocommerce_dhlpwc_bulk_label_printing', function(e) {
        var dhlpwc_bulk_printing_area = $('input#woocommerce_dhlpwc_bulk_label_printing').closest('fieldset').parent();
        dhlpwc_bulk_printing_area.children('div.dhlpwc_settings_description_warning').remove();

        if ($(this).attr('checked') !== 'checked') {
            return;
        }

        // Do a check for compatibility
        var data = $.extend(true, $(this).data(), {
            action: 'dhlpwc_test_bulk_printing'
        });

        $.post(ajaxurl, data, function (response) {
            try {
                var success = response.data.success;
                var message = response.data.message;
            } catch (error) {
                dhlpwc_bulk_printing_area.append('<div class="dhlpwc_settings_description_warning">An error has occured</div>');
                return;
            }

            if (success === 'false') {
                dhlpwc_bulk_printing_area.append('<div class="dhlpwc_settings_description_warning">' + message + '</div>');
            }
        }, 'json');

    }).on('dhlpwc:init_delivery_times_grid', function() {
        // Don't load if the delivery times grid cannot be found
        if ($('.dhlpwc-delivery-times-grid table').length < 1) {
            return;
        }
        // Don't load if the grid has already been filled (in case this event is called multiple times)
        if ($('.dhlpwc-delivery-times table').find('td').length > 0) {
            return;
        }

        var dhlpwc_delivery_times_collection = [];

        $('.dhlpwc-delivery-times-option').each(function (e) {
            if ($.inArray($(this).data('delivery-times-group'), dhlpwc_delivery_times_collection) === -1) {
                dhlpwc_delivery_times_collection.push($(this).data('delivery-times-group'));
            }
            $(this).closest('tr').addClass('dhlpwc-original-delivery-times-option');
        });

        $.each(dhlpwc_delivery_times_collection, function (i, option_identifier) {
            $('.dhlpwc-delivery-times-grid table')
                .find('tbody')
                .append($('<tr id="dhlpwc-delivery-times-group-mirror-' + option_identifier + '">'));

            $('.dhlpwc-delivery-times-option[data-delivery-times-group="' + option_identifier + '"]').each(function (e) {

                // Create a label assuming the 'cut off time' is last and has a label
                if ($(this).attr('id').indexOf('_dhlpwc_delivery_time_cut_off_') > -1) {

                    // For same_day, there is no day input. Expanded colspan
                    if (option_identifier == 'same_day') {
                        var dhlpwc_wrap = '<td colspan="2"></td>';
                    } else {
                        var dhlpwc_wrap = '<td></td>';
                    }


                    $(this).closest('tr').find('th').first().find('label').clone()
                        .removeAttr('id for class')
                        .appendTo('#dhlpwc-delivery-times-group-mirror-' + option_identifier)
                        .wrap(dhlpwc_wrap);
                }

                // Create a clone that mirrors the original input box
                $(this).clone()
                    .prop('id', $(this).attr('id') + '-mirror')
                    .bind('change blur', function () {
                        if ($(this).attr('type') === 'checkbox') {
                            $('#' + $(this).attr('id').slice(0, -7)).attr('checked', $(this).attr('checked') === 'checked');
                        } else {
                            $('#' + $(this).attr('id').slice(0, -7)).val($(this).val());
                        }
                    })
                    .appendTo('#dhlpwc-delivery-times-group-mirror-' + option_identifier)
                    .wrap("<td></td>");
            });
        });

    });

    $(document.body).trigger('dhlpwc:init_test_connection_button');
    $(document.body).trigger('dhlpwc:init_settings_menu');
    $(document.body).trigger('dhlpwc:init_options_grid');
    $(document.body).trigger('dhlpwc:check_global_shipping_settings');
    $(document.body).trigger('dhlpwc:check_return_address');
    $(document.body).trigger('dhlpwc:check_hide_sender_address');
    $(document.body).trigger('dhlpwc:init_delivery_times_grid');

    $('.dhlpwc-price-input').each(function(e) {
        var currency_symbol = $(this).data('dhlpwc-currency-symbol');
        var currency_pos = 'dhlpwc-currency-pos-' + $(this).data('dhlpwc-currency-pos');
        $(this).wrapAll('<div class="dhlpwc-currency-wrap ' + currency_pos + '"></div>').parent().prepend('<i>' + currency_symbol + '</i>');
    });

});
