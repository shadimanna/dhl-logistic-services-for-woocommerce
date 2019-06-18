jQuery(document).ready(function($) {

    var dhlpwc_settings_menu_collection = [];
    var dhlpwc_test_connection_button = $("input#woocommerce_dhlpwc_test_connection");
    var dhlpwc_search_printers_button = $("input#woocommerce_dhlpwc_search_printers");

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
                
            } else {
                dhlpwc_test_connection_button.val(message);
                dhlpwc_test_connection_button.addClass('dhlpwc_button_fail');
            }

        }, 'json');

    }).on('click', '.dhlpwc_settings_suggestion_accounts', function(e) {
        var account_id = $(this).data('account-id');
        $('input#woocommerce_dhlpwc_account_id').val(account_id);

    }).on('dhlpwc:init_test_connection_button', function(e) {
        if (dhlpwc_test_connection_button === undefined) { return; }
        dhlpwc_test_connection_button.val(dhlpwc_settings_object.test_connection_message);
        dhlpwc_test_connection_button.prop("disabled", false);

    }).on('dhlpwc:enable_test_connection_button', function(e) {
        if (dhlpwc_test_connection_button === undefined) { return; }
        dhlpwc_test_connection_button.prop("disabled", false);

    }).on('dhlpwc:disable_test_connection_button', function(e) {
        if (dhlpwc_test_connection_button === undefined) {
            return;
        }
        dhlpwc_test_connection_button.removeClass('dhlpwc_button_success');
        dhlpwc_test_connection_button.removeClass('dhlpwc_button_fail');
        dhlpwc_test_connection_button.prop("disabled", true);

    }).on('click', 'input#woocommerce_dhlpwc_search_printers', function(e) {
        e.preventDefault();

        var data = $.extend(true, $(this).data(), {
            action: 'dhlpwc_search_printers'
        });

        dhlpwc_search_printers_button.val(dhlpwc_settings_object.search_printers_loading_message);
        $(document.body).trigger('dhlpwc:disable_search_printers_button');

        $.post(ajaxurl, data, function (response) {

            try {
                var success = response.data.success;
                var message = response.data.message;
                var info = response.data.info;
            } catch (error) {
                alert('Error');
                return;
            }

            $(document.body).trigger('dhlpwc:enable_search_printers_button');

            if (success === 'true') {
                dhlpwc_search_printers_button.val(message);
                dhlpwc_search_printers_button.addClass('dhlpwc_button_success');

                var dhlpwc_printer_area = $('input#woocommerce_dhlpwc_printer_id').closest('fieldset').parent();
                dhlpwc_printer_area.children('div.dhlpwc_settings_suggestion_info').remove();
                dhlpwc_printer_area.children('div.dhlpwc_settings_suggestion_printers').remove();

                if (!$.isEmptyObject(info.printers)) {
                    dhlpwc_printer_area.append('<div class="dhlpwc_settings_suggestion_info">' + dhlpwc_settings_object.printers_found_message + '</div>');
                    $.each(info.printers, function (index, value) {
                        dhlpwc_printer_area.append('<div class="dhlpwc_settings_suggestion_printers" data-printer-id="' + value.id.toString() + '">' + value.name.toString() + ': ' + value.id.toString() + '</div>');
                    });

                    // Autofill printer if empty
                    if ($('input#woocommerce_dhlpwc_printer_id').val().length === 0) {
                        var value = info.printers[0].id;
                        $('input#woocommerce_dhlpwc_printer_id').val(value);
                    }
                }

            } else {
                dhlpwc_search_printers_button.val(message);
                dhlpwc_search_printers_button.addClass('dhlpwc_button_fail');
            }

        }, 'json');

    }).on('click', '.dhlpwc_settings_suggestion_printers', function(e) {
        var printer_id = $(this).data('printer-id');
        $('input#woocommerce_dhlpwc_printer_id').val(printer_id);

    }).on('dhlpwc:init_search_printers_button', function(e) {
        if (dhlpwc_search_printers_button === undefined) { return; }
        dhlpwc_search_printers_button.val(dhlpwc_settings_object.search_printers_message);
        dhlpwc_search_printers_button.prop("disabled", false);

    }).on('dhlpwc:enable_search_printers_button', function(e) {
        if (dhlpwc_search_printers_button === undefined) { return; }
        dhlpwc_search_printers_button.prop("disabled", false);

    }).on('dhlpwc:disable_search_printers_button', function(e) {
        if (dhlpwc_search_printers_button === undefined) { return; }
        dhlpwc_search_printers_button.removeClass('dhlpwc_button_success');
        dhlpwc_search_printers_button.removeClass('dhlpwc_button_fail');
        dhlpwc_search_printers_button.prop("disabled", true);

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
        // Sending out event for other scripts
        $(document.body).trigger('dhlpwc:settings_clicked', [dhlpwc_settings_menu_collection[index].title.attr('id')]);

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

            $(".dhlpwc-options-grid table:not('.dhlpwc-condition-table')")
                .find("tbody:not('.dhlpwc-condition-tbody')")
                .append($('<tr id="dhlpwc-option-group-mirror-' + option_identifier + '">'))
                .append($('<tr id="dhlpwc-option-condition-' + option_identifier + '">'));

            // Add condition
            $(document.body).trigger('dhlpwc:init_conditions', [option_identifier]);

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

    }).on('dhlpwc:init_conditions', function(e, code) {
        // Hide original input
        $("textarea[id$='_dhlpwc_option_condition_" + code + "']").closest('tr').hide();

        $('#dhlpwc-option-condition-' + code).append('<td colspan="7">');
        $('#dhlpwc-option-condition-' + code + ' td').empty();
        $('#dhlpwc-option-condition-' + code + ' td').html(dhlpwc_settings_object.condition_templates.table);
        $('#dhlpwc-option-condition-' + code + ' td table.dhlpwc-condition-table').attr('id', 'dhlpwc-condition-table-' + code);
        $('#dhlpwc-condition-table-' + code).data('condition', code);

        $('#dhlpwc-condition-table-' + code + ' tbody').append(dhlpwc_settings_object.condition_templates.add_button);

        $('#dhlpwc-condition-table-' + code + ' tbody').sortable({
            update: function () {
                $(document.body).trigger('dhlpwc:save_conditions', [code]);
            },
            items: 'tr.dhlpwc-condition-rule',
            handle: '.dhlpwc-condition-rule-handle'
        });

        $(document.body).trigger('dhlpwc:build_conditions', [code]);

    }).on('dhlpwc:add_condition_row', function(e, code, condition_object) {
        // Validate input
        if (typeof(condition_object.input_type) === 'undefined') { return; }
        if (typeof(condition_object.input_data) === 'undefined') { return; }
        if (typeof(condition_object.input_action) === 'undefined') { return; }
        if (typeof(condition_object.input_action_data) === 'undefined') { return; }

        // Add row
        $(dhlpwc_settings_object.condition_templates.row).insertBefore('#dhlpwc-condition-table-' + code + ' .dhlpwc-condition-add');
        var current_row = $('#dhlpwc-condition-table-' + code + ' tbody tr.dhlpwc-condition-rule:last');

        // Set first drop down selection
        current_row.find('.dhlpwc-condition-input-type option[value=' + condition_object.input_type + ']').attr('selected', 'selected');

        // Update input based on drop down selection
        if (condition_object.input_type === 'weight') {
            current_row.find('.dhlpwc-condition-input-data').addClass('dhlpwc-weight-input');
        } else if (condition_object.input_type === 'cart_total') {
            current_row.find('.dhlpwc-condition-input-data').addClass('dhlpwc-price-input');
        }

        // Fill input value
        current_row.find('.dhlpwc-condition-input-data').val(condition_object.input_data);

        // Set second drop down selection
        current_row.find('.dhlpwc-condition-input-action option[value=' + condition_object.input_action + ']').attr('selected', 'selected');

        // Update input based on drop down selection
        if (condition_object.input_action === 'disable') {
            current_row.find('.dhlpwc-condition-input-action-data').hide();
        } else if (condition_object.input_action === 'change_price' || condition_object.input_action === 'add_fee' || condition_object.input_action === 'add_fee_repeat') {
            current_row.find('.dhlpwc-condition-input-action-data').addClass('dhlpwc-price-input');
        }

        // Set second drop down selection
        current_row.find('.dhlpwc-condition-input-action-data').val(condition_object.input_action_data);

    }).on('dhlpwc:save_conditions', function(e, code) {
        // Save the data to the field
        var condition_rules = $('#dhlpwc-condition-table-' + code + ' tr.dhlpwc-condition-rule');

        condition_objects = [];
        condition_rules.each(function (index, condition_rule) {
            var condition_object = {
                input_type: $(condition_rule).find('.dhlpwc-condition-input-type').val(),
                input_data: $(condition_rule).find('.dhlpwc-condition-input-data').val(),
                input_action: $(condition_rule).find('.dhlpwc-condition-input-action').val(),
                input_action_data: $(condition_rule).find('.dhlpwc-condition-input-action-data').val()
            };

            condition_objects.push(condition_object);
        });

        $("textarea[id$='_dhlpwc_option_condition_" + code + "']").text(JSON.stringify(condition_objects));

    }).on('dhlpwc:build_conditions', function(e, code) {
        // Read input
        var condition_input = $("textarea[id$='_dhlpwc_option_condition_" + code + "']").val();
        try {
            var condition_objects = JSON.parse(condition_input);
        } catch(e) {
            // Not a valid JSON object, skip building
            return;
        }

        // Parse input
        $.each(condition_objects, function (index, condition_object) {
            $(document.body).trigger('dhlpwc:add_condition_row', [code, condition_object]);
        });

        $(document.body).trigger('dhlpwc:update_price_fields');
        $(document.body).trigger('dhlpwc:update_weight_fields');

    }).on('dhlpwc:clear_conditions', function(e, code) {
        $('#dhlpwc-condition-table-' + code + ' tbody').find('tr.dhlpwc-condition-rule').remove();

    }).on('change', '.dhlpwc-condition-field', function(e) {
        var code = $(this).closest('.dhlpwc-condition-table').data('condition');

        $(document.body).trigger('dhlpwc:save_conditions', [code]);

        if ($(this).is("select")) {
            $(document.body).trigger('dhlpwc:clear_conditions', [code]);
            $(document.body).trigger('dhlpwc:build_conditions', [code]);
        }

    }).on('click', '.dhlpwc-condition-add-button', function(e) {
        e.preventDefault();
        var code = $(this).closest('.dhlpwc-condition-table').data('condition');

        var condition_object = {
            input_type: 'weight',
            input_data: '',
            input_action: 'change_price',
            input_action_data: ''
        };

        $(document.body).trigger('dhlpwc:add_condition_row', [code, condition_object]);

        $(document.body).trigger('dhlpwc:update_price_fields');
        $(document.body).trigger('dhlpwc:update_weight_fields');

        $(document.body).trigger('dhlpwc:save_conditions', [code]);

    }).on('click', '.dhlpwc-condition-remove-button', function(e) {
        e.preventDefault();
        var code = $(this).closest('.dhlpwc-condition-table').data('condition');
        $(this).closest('tr.dhlpwc-condition-rule').remove();
        $(document.body).trigger('dhlpwc:save_conditions', [code]);

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

        $(document.body).trigger('dhlpwc:update_price_fields');
        $(document.body).trigger('dhlpwc:update_weight_fields');

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

    }).on('change', 'input#woocommerce_dhlpwc_bulk_label_download', function(e) {
        var dhlpwc_bulk_download_area = $('input#woocommerce_dhlpwc_bulk_label_download').closest('fieldset').parent();
        dhlpwc_bulk_download_area.children('div.dhlpwc_settings_description_warning').remove();

        if ($(this).attr('checked') !== 'checked') {
            return;
        }

        // Do a check for compatibility
        var data = $.extend(true, $(this).data(), {
            action: 'dhlpwc_test_bulk_download'
        });

        $.post(ajaxurl, data, function (response) {
            try {
                var success = response.data.success;
                var message = response.data.message;
            } catch (error) {
                dhlpwc_bulk_download_area.append('<div class="dhlpwc_settings_description_warning">An error has occured</div>');
                return;
            }

            if (success === 'false') {
                dhlpwc_bulk_download_area.append('<div class="dhlpwc_settings_description_warning">' + message + '</div>');
            }
        }, 'json');

    }).on('dhlpwc:init_delivery_times_grid', function() {
        // Don't load if the delivery times grid cannot be found
        if ($('.dhlpwc-delivery-times-grid table').length < 1) {
            return;
        }
        // Don't load if the grid has already been filled (in case this event is called multiple times)
        if ($('.dhlpwc-delivery-times-grid table').find('td').length > 0) {
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
                    if (option_identifier == 'same_day' || option_identifier == 'no_neighbour_same_day') {
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

    }).on('dhlpwc:init_bulk_grid', function() {
        // Don't load if the bulk grid cannot be found
        if ($('.dhlpwc-bulk-grid table').length < 1) {
            return;
        }
        // Don't load if the grid has already been filled (in case this event is called multiple times)
        if ($('.dhlpwc-bulk-grid table').find('td').length > 0) {
            return;
        }

        var dhlpwc_bulk_collection = [];

        $('.dhlpwc-bulk-option').each(function (e) {
            if ($.inArray($(this).data('bulk-group'), dhlpwc_bulk_collection) === -1) {
                dhlpwc_bulk_collection.push($(this).data('bulk-group'));
            }
            $(this).closest('tr').addClass('dhlpwc-original-bulk-option');
        });

        $.each(dhlpwc_bulk_collection, function (i, option_identifier) {
            $('.dhlpwc-bulk-grid table')
                .find('tbody')
                .append($('<tr id="dhlpwc-bulk-group-mirror-' + option_identifier + '">'));

            $('.dhlpwc-bulk-option[data-bulk-group="' + option_identifier + '"]').each(function (e) {
                // Create a label assuming the 'enable option' is first and has a label
                if ($(this).attr('id').indexOf('_dhlpwc_enable_bulk_option_') > -1) {
                    $(this).closest('tr').find('th').first().find('label').clone()
                        .removeAttr('id for class')
                        .appendTo('#dhlpwc-bulk-group-mirror-' + option_identifier)
                        .wrap("<td></td>");
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
                    .appendTo('#dhlpwc-bulk-group-mirror-' + option_identifier)
                    .wrap("<td></td>");
            });
        });

    }).on('dhlpwc:update_price_fields', function() {
        var currency_symbol = dhlpwc_settings_object.currency_symbol;
        var currency_pos = 'dhlpwc-currency-pos-' + dhlpwc_settings_object.currency_pos;

        $('.dhlpwc-price-input').not('.dhlpwc-currency-wrap .dhlpwc-price-input').each(function(e) {
            $(this).wrapAll('<div class="dhlpwc-currency-wrap ' + currency_pos + '"></div>').parent().prepend('<i>' + currency_symbol + '</i>');
        });
    }).on('dhlpwc:update_weight_fields', function() {
        var weight_unit = dhlpwc_settings_object.weight_unit;

        $('.dhlpwc-weight-input').not('.dhlpwc-weight-unit-wrap .dhlpwc-weight-input').each(function(e) {
            $(this).wrapAll('<div class="dhlpwc-weight-unit-wrap"></div>').parent().prepend('<i>' + weight_unit + '</i>');
        });

    });

    $(document.body).trigger('dhlpwc:init_test_connection_button');
    $(document.body).trigger('dhlpwc:init_search_printers_button');
    $(document.body).trigger('dhlpwc:init_settings_menu');
    $(document.body).trigger('dhlpwc:init_options_grid');
    $(document.body).trigger('dhlpwc:check_global_shipping_settings');
    $(document.body).trigger('dhlpwc:check_return_address');
    $(document.body).trigger('dhlpwc:check_hide_sender_address');
    $(document.body).trigger('dhlpwc:init_delivery_times_grid');
    $(document.body).trigger('dhlpwc:init_bulk_grid');
    $(document.body).trigger('dhlpwc:update_price_fields');
    $(document.body).trigger('dhlpwc:update_weight_fields');

});
