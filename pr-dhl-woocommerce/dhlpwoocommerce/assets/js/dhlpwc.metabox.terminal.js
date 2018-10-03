var dhlpwc_metabox_terminal_timeout = null;
var dhlpwc_metabox_terminal_timeout_search = null;

jQuery(document).ready(function($) {

    $(document.body).on('click', '#dhlpwc-metabox-terminal-display', function(e) {
        e.preventDefault();
        $('#dhlpwc-metabox-terminal-preview-box').hide();
        $('#dhlpwc-metabox-terminal-select-box').show();

        $(document.body).trigger('dhlpwc:metabox_terminal_trigger');

    }).on('keyup', '#dhlpwc-metabox-terminal-search-field', function(e) {
        e.preventDefault();

        $(document.body).trigger('dhlpwc:metabox_terminal_trigger');

    }).on('dhlpwc:metabox_terminal_trigger', function(e) {
        clearTimeout(dhlpwc_metabox_terminal_timeout);

        dhlpwc_metabox_terminal_timeout = setTimeout(function () {
            $(document.body).trigger('dhlpwc:metabox_terminal_search');
        }, 500);

    }).on('dhlpwc:metabox_terminal_search', function(e) {
        dhlpwc_metabox_terminal_timeout_search = $('#dhlpwc-metabox-terminal-search-field').val().toString();

        if ($('#dhlpwc-metabox-terminal-search-field').val().toString() === '') {
            return;
        }

        // Make AJAX call to get the view for div
        var data = {
            'action': 'dhlpwc_metabox_terminal_search',
            'post_id': dhlpwc_metabox_terminal_object.post_id,
            'search': dhlpwc_metabox_terminal_timeout_search
        };

        $.post(ajaxurl, data, function (response) {
            if ($('#dhlpwc-metabox-terminal-search-field').val().toString() !== dhlpwc_metabox_terminal_timeout_search) {
                // Input is already old, don't show
                return;
            }

            try {
                view = response.data.view;
            } catch (error) {
                alert('Error');
                return;
            }

            $('#dhlpwc-metabox-terminal-select-list').html(view);
        }, 'json');

    }).on('click', 'div.dhlpwc-metabox-terminal-location', function(e) {
        e.preventDefault();

        var terminal_name = $(this).find('strong').html();
        var terminal_description = $(this).find('span').html();

        $('#dhlpwc-metabox-terminal-hidden-input').val($(this).data('terminal-id'));
        $('#dhlpwc-metabox-terminal-select-list').html('');
        $('#dhlpwc-metabox-terminal-select-box').hide();
        $('#dhlpwc-metabox-terminal-preview-text').html(terminal_name);
        $('#dhlpwc-metabox-terminal-preview-description').html(terminal_description);
        $('#dhlpwc-metabox-terminal-preview-box').show();
    });

});
