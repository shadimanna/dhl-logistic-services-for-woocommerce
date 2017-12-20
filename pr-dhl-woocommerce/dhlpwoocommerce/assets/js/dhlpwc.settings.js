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

            if (success == 'true') {
                dhlpwc_test_connection_button.val(message);
                dhlpwc_test_connection_button.addClass('dhlpwc_button_success');

                console.log(info);
                var dhlpwc_account_area = $('input#woocommerce_dhlpwc_account_id').closest('fieldset').parent();
                dhlpwc_account_area.children('div.dhlpwc_settings_suggestion_info').remove();
                dhlpwc_account_area.children('div.dhlpwc_settings_suggestion_accounts').remove();

                if (!$.isEmptyObject(info.accounts)) {
                    dhlpwc_account_area.append('<div class="dhlpwc_settings_suggestion_info">' + dhlpwc_settings_object.accounts_found_message + '</div>');
                    $.each(info.accounts, function (index, value) {
                        dhlpwc_account_area.append('<div class="dhlpwc_settings_suggestion_accounts" data-account-id="' + value.toString() + '">' + value.toString() + '</div>');
                    });
                }

                var dhlpwc_organization_area = $('input#woocommerce_dhlpwc_organization_id').closest('fieldset').parent();
                dhlpwc_organization_area.children('div.dhlpwc_settings_suggestion_info').remove();
                dhlpwc_organization_area.children('div.dhlpwc_settings_suggestion_organization').remove();

                if (info.organization_id !== undefined) {
                    var value = info.organization_id;
                    dhlpwc_organization_area.append('<div class="dhlpwc_settings_suggestion_info">' + dhlpwc_settings_object.organization_found_message + '</div>');
                    dhlpwc_organization_area.append('<div class="dhlpwc_settings_suggestion_organization" data-organization-id="' + value.toString() + '">' + value.toString() + '</div>');
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
    });

    $(document.body).trigger('dhlpwc:init_test_connection_button');
    $(document.body).trigger('dhlpwc:init_settings_menu');

    $('.dhlpwc-price-input').each(function(e) {
        var currency_symbol = $(this).data('dhlpwc-currency-symbol');
        var currency_pos = 'dhlpwc-currency-pos-' + $(this).data('dhlpwc-currency-pos');
        $(this).wrapAll('<div class="dhlpwc-currency-wrap ' + currency_pos + '"></div>').parent().prepend('<i>' + currency_symbol + '</i>');
    })

});
