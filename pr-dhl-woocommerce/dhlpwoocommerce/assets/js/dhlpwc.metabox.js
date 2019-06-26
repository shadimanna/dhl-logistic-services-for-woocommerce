var dhlpwc_metabox_timeout_options = null;
var dhlpwc_metabox_timeout_options_search = null;
var dhlpwc_metabox_timeout = null;
var dhlpwc_metabox_timeout_search = null;

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
        var label_option_data = {};
        $("input[name='dhlpwc-label-create-option[]']:checked, input[name='dhlpwc-label-create-delivery-option[]']:checked").each(function() {
            var label_option = $(this).val().toString();
            label_options.push(label_option);

            $('.dhlpwc-metabox-delivery-input, .dhlpwc-metabox-service-input').filter('[data-option-input="'+label_option+'"]').find('input.dhlpwc-option-data').each(function() {
                label_option_data[label_option] = $(this).val().toString();
            });
        });

        var to_business = $("input[name='dhlpwc-label-create-to-business']").is(':checked') ?  'yes' : 'no';

        var data = $.extend(true, $(this).data(), {
            action: 'dhlpwc_label_create',
            security: $( '#dhlpwc-ajax-nonce' ).val(),
            post_id: dhlpwc_metabox_object.post_id,
            label_size: label_size,
            label_options: label_options,
            label_option_data: label_option_data,
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
            $(document.body).trigger('dhlpwc:disable_delivery_option_exclusions');
            $(document.body).trigger('dhlpwc:select_default_size');
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
            $(document.body).trigger('dhlpwc:disable_delivery_option_exclusions');
            $(document.body).trigger('dhlpwc:select_default_size');
            $('#dhlpwc-label').attr('metabox_busy', 'false');
        }, 'json');

    }).on('click', '.dhlpwc_action_print', function(e) {
        e.preventDefault();

        var data = {
            'action': 'dhlpwc_label_print',
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
            $(document.body).trigger('dhlpwc:disable_delivery_option_exclusions');
            $(document.body).trigger('dhlpwc:select_default_size');
            $('#dhlpwc-label').attr('metabox_busy', 'false');
        }, 'json');

    }).on('change', '.dhlpwc-meta-to-business input.dhlpwc-label-create-option', function(e) {
        // Cancel regular options loading
        if (dhlpwc_metabox_timeout) {
            clearTimeout(dhlpwc_metabox_timeout);
        }
        // Delay metabox refresh due to rapid multiple checkbox changes
        if (dhlpwc_metabox_timeout_options) {
            clearTimeout(dhlpwc_metabox_timeout_options);
        }

        dhlpwc_metabox_timeout_options = setTimeout(function () {
            $(document.body).trigger('dhlpwc:meta_load_options');
        }, 50);

    }).on('change', '.dhlpwc-order-metabox-form-deliverymethods input.dhlpwc-label-create-delivery-option, .dhlpwc-order-metabox-form-services input.dhlpwc-label-create-option', function(e) {
        // Delay metabox refresh due to rapid multiple checkbox changes
        if (dhlpwc_metabox_timeout) {
            clearTimeout(dhlpwc_metabox_timeout);
        }

        $(document.body).trigger('dhlpwc:disable_delivery_option_exclusions');

        dhlpwc_metabox_timeout = setTimeout(function () {
            //$(document.body).trigger('dhlpwc:update_option_exclusions');
            $(document.body).trigger('dhlpwc:meta_load_sizes');
        }, 1100);

    }).on('dhlpwc:disable_delivery_option_exclusions', function() {
        // Reset
        $(".dhlpwc-label-create-service-option-container input[name='dhlpwc-label-create-option[]']").each(function() {
            $(this).attr('disabled', false);
        });

        // // Hide all delivery and service input
        $('.dhlpwc-metabox-delivery-input').hide();

        // Show input if available
        if ($("input[name='dhlpwc-label-create-delivery-option[]']:checked:first").length > 0) {
            $('.dhlpwc-metabox-delivery-input').filter('[data-option-input="' +
                $("input[name='dhlpwc-label-create-delivery-option[]']:checked:first").val().toString() +
                '"]').show();
        }

        var disable_options = $("input[name='dhlpwc-label-create-delivery-option[]']:checked:first").data('exclusions');
        $.each(disable_options, function (index, value) {
            $(".dhlpwc-label-create-service-option-container input[name='dhlpwc-label-create-option[]'][value='" + value + "']:checked").attr('checked', false);
            $(".dhlpwc-label-create-service-option-container input[name='dhlpwc-label-create-option[]'][value='" + value + "']:enabled").attr('disabled', true);
        });

        $(document.body).trigger('dhlpwc:disable_service_option_exclusions');

        // Then check all service options
    }).on('dhlpwc:disable_service_option_exclusions', function() {
        // Hide all service input
        $('.dhlpwc-metabox-service-input').hide();

        var disable_options_collection = [];
        $(".dhlpwc-label-create-service-option-container input[name='dhlpwc-label-create-option[]']:checked").each(function() {
            disable_options = $(this).data('exclusions');
            $.each(disable_options, function (index, value) {
                disable_options_collection.push(value.toString());
            });
            // Show input if available
            $('.dhlpwc-metabox-service-input').filter('[data-option-input="'+$(this).val().toString()+'"]').show();
        });

        // Sync address check if needed
        $(document.body).trigger('dhlpwc:metabox_address_sync');

        $.each(disable_options_collection, function (index, value) {
            $(".dhlpwc-label-create-service-option-container input[name='dhlpwc-label-create-option[]'][value='" + value + "']:checked").attr('checked', false);
            $(".dhlpwc-label-create-service-option-container input[name='dhlpwc-label-create-option[]'][value='" + value + "']:enabled").attr('disabled', true);
        });

    }).on('dhlpwc:delay_meta_load_options', function() {
        // Send a future request to reload, but only if it's no longer busy (otherwise we just assume it's stuck
        dhlpwc_metabox_timeout_options = setTimeout(function () {
            $(document.body).trigger('dhlpwc:meta_load_options');
        }, 1100);

    }).on('dhlpwc:meta_load_options', function() {
        var label_options = [];
        $("input[name='dhlpwc-label-create-option[]']:checked, input[name='dhlpwc-label-create-delivery-option[]']:checked").each(function () {
            label_options.push($(this).val().toString());
        });
        var to_business = $("input[name='dhlpwc-label-create-to-business']").is(':checked') ? 'yes' : 'no';

        var data = {
            'action': 'dhlpwc_load_options',
            post_id: dhlpwc_metabox_object.post_id,
            label_options: label_options,
            to_business: to_business
        };

        // Click-2-much prevention
        if ($('#dhlpwc-label').attr('metabox_busy') == 'true') {
            $(document.body).trigger('dhlpwc:delay_meta_load_sizes');
            return;
        }

        dhlpwc_metabox_timeout_options_search = Math.random();
        var dhlpwc_metabox_timeout_options_ghost = dhlpwc_metabox_timeout_options_search;

        $.post(ajaxurl, data, function (response) {
            if (dhlpwc_metabox_timeout_options_ghost != dhlpwc_metabox_timeout_options_search) {
                // Input is already old, don't show
                return;
            }

            try {
                view = response.data.view;
            } catch (error) {
                alert('Error');
                return;
            }

            $('div.dhlpwc-order-metabox-form-options > .dhlpwc-form-content').html(view);

            // Select a delivery method if nothing is selected
            if ($("input[name='dhlpwc-label-create-delivery-option[]']:checked").length == 0) {
                $("input[name='dhlpwc-label-create-delivery-option[]']:first")
                    .attr('checked', true)
                    .trigger('change');
            } else {
                $(document.body).trigger('dhlpwc:disable_delivery_option_exclusions');
                $(document.body).trigger('dhlpwc:meta_load_sizes');
            }
        }, 'json');

    }).on('dhlpwc:delay_meta_load_sizes', function() {
        // Send a future request to reload, but only if it's no longer busy (otherwise we just assume it's stuck
        dhlpwc_metabox_timeout = setTimeout(function () {
            $(document.body).trigger('dhlpwc:meta_load_sizes');
        }, 1100);

    }).on('dhlpwc:meta_load_sizes', function() {
        var label_options = [];

        $("input[name='dhlpwc-label-create-option[]']:checked, input[name='dhlpwc-label-create-delivery-option[]']:checked").each(function () {
            label_options.push($(this).val().toString());
        });

        var to_business = $("input[name='dhlpwc-label-create-to-business']").is(':checked') ? 'yes' : 'no';

        var data = {
            'action': 'dhlpwc_load_sizes',
            post_id: dhlpwc_metabox_object.post_id,
            label_options: label_options,
            to_business: to_business
        };

        // Click-2-much prevention
        if ($('#dhlpwc-label').attr('metabox_busy') == 'true') {
            $(document.body).trigger('dhlpwc:delay_meta_load_sizes');
            return;
        }

        dhlpwc_metabox_timeout_search = Math.random();
        var dhlpwc_metabox_timeout_ghost = dhlpwc_metabox_timeout_search;

        $.post(ajaxurl, data, function (response) {
            if (dhlpwc_metabox_timeout_ghost != dhlpwc_metabox_timeout_search) {
                // Input is already old, don't show
                return;
            }

            try {
                view = response.data.view;
            } catch (error) {
                alert('Error');
                return;
            }

            $('div.dhlpwc-order-metabox-form-parceltypes > .dhlpwc-form-content').html(view);
            $(document.body).trigger('dhlpwc:select_default_size');
        }, 'json');

    }).on('dhlpwc:select_default_size', function() {
        if ($('input:radio[name=dhlpwc-label-create-size]:not(:disabled)').not(':checked')) {
            $('input:radio[name=dhlpwc-label-create-size]:not(:disabled):first').attr('checked', true);
        }

    }).on('change', '.dhlpwc-metabox-address-input', function(e) {
            $(document.body).trigger('dhlpwc:metabox_address_sync');

    }).on('dhlpwc:metabox_address_sync', function(e) {
        // Continue if hidden input can be found
        if ($('#dhlpwc-metabox-address-hidden-input').length > 0) {

            var values = {
                first_name: $('#dhlpwc-metabox-address-first_name').val(),
                last_name: $('#dhlpwc-metabox-address-last_name').val(),
                company: $('#dhlpwc-metabox-address-company').val(),
                postcode: $('#dhlpwc-metabox-address-postcode').val(),
                city: $('#dhlpwc-metabox-address-city').val(),
                street: $('#dhlpwc-metabox-address-street').val(),
                number: $('#dhlpwc-metabox-address-number').val(),
                addition: $('#dhlpwc-metabox-address-addition').val(),
                email: $('#dhlpwc-metabox-address-email').val(),
                phone: $('#dhlpwc-metabox-address-phone').val()
            };

            var json_string = JSON.stringify(values);

            $('#dhlpwc-metabox-address-hidden-input').val(json_string);
        }

    });

    $(document.body).trigger('dhlpwc:disable_delivery_option_exclusions');
    $(document.body).trigger('dhlpwc:select_default_size');

});
