jQuery(document).ready(function($) {

    // Create custom input select
    $(document.body).on('dhlpwc:refresh_parcelshops_stylishselect', function() {

        $('#dhlpwc_parcelshop_select').each(function () {
            // Cache the number of options
            var select_element = $(this);
            var numberOfOptions = $(this).children('option').length;

            // Hides the select element
            $(this).addClass('dhlpwc-stylishselect-hidden-select');

            // Wrap the select element in a div
            if ($(this).parent().attr('class') != 'dhlpwc-stylishselect-select') {
                $(this).wrap('<div class="dhlpwc-stylishselect-select"></div>');
            } else {
                // Remove existing stylishselect
                $(this).siblings('.dhlpwc-stylishselect').eq(0).remove();
                $(this).siblings('.dhlpwc-stylishselect-options').eq(0).remove();
            }

            // Insert a styled div to sit over the top of the hidden select element
            $(this).after('<div class="dhlpwc-stylishselect"></div>');

            // Cache the styled div
            var stylishselect = $(this).next('div.dhlpwc-stylishselect');

            // Show the first select option in the styled div
            //$styledSelect.text($this.children('option').eq(0).text());
            stylishselect.text($.trim($(this).children('option').eq(0).text().split('|')[0]));

            // Insert an unordered list after the styled div and also cache the list
            var list = $('<ul />', {
                'class': 'dhlpwc-stylishselect-options'
            }).insertAfter(stylishselect);

            // Insert a list item into the unordered list for each select option
            for (var i = 0; i < numberOfOptions; i++) {
                var formattedString = '';
                var stringArray = $(this).children('option').eq(i).text().split('|');
                stringArray[0] = $.trim(stringArray[0]).bold();
                stringArray.forEach(function(stringPart) {
                    formattedString += $.trim(stringPart) + "<br/>\n";
                });
                $('<li />', {
                    html: formattedString,
                    rel: $(this).children('option').eq(i).val()
                }).appendTo(list);
            }

            // Cache the list items
            var listItems = list.children('li');

            // Show the unordered list when the styled div is clicked (also hides it if the div is clicked again)
            stylishselect.click(function (e) {
                e.stopPropagation();
                $('div.dhlpwc-stylishselect.active').each(function() {
                    $(this).removeClass('active').next('ul.dhlpwc-stylishselect-options').hide();
                });
                $(this).toggleClass('active').next('ul.dhlpwc-stylishselect-options').toggle();
            });

            // Hides the unordered list when a list item is clicked and updates the styled div to show the selected list item
            // Updates the select element to have the value of the equivalent option
            listItems.click(function (e) {
                e.stopPropagation();

                select_element.val($(this).attr('rel'));
                select_element.trigger('change');
                list.hide();

                $(document.body).trigger('dhlpwc:select_parcelshop', [select_element.val()]);
            });

            // Hides the unordered list when clicking outside of it
            $(document.body).click(function () {
                stylishselect.removeClass('active');
                list.hide();
            });

        });

    });

    $(document.body).on('dhlpwc:display_parcelshop_stylishselect', function(e, parcelshop_id) {
        me = $(dhlpwc_stylishselect.identifier);
        stylishselect = me.next('div.dhlpwc-stylishselect');

        // Search parcelshop_id text
        var parcelshop_info = $('ul.dhlpwc-stylishselect-options').find("li[rel='" + parcelshop_id + "']");
        stylishselect.text(parcelshop_info[0].innerText.split('\n')[0]).removeClass('active');
    });

});