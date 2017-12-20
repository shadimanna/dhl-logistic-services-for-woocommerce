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
                $(this).siblings('.dhlpwc-stylishselect-options').eq(0).remove();
            }

            // Insert an unordered list after the styled div and also cache the list
            var list = $('<ul />', {
                'class': 'dhlpwc-stylishselect-options'
            }).insertAfter($(this));

            // Insert a list item into the unordered list for each select option
            for (var i = 0; i < numberOfOptions; i++) {
                var formattedString = '';
                var stringArray = $(this).children('option').eq(i).text().split('|');
                stringArray[0] = '<h3>'  + $.trim(stringArray[0]) + '</h3>';
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

            // Hides the unordered list when a list item is clicked and updates the styled div to show the selected list item
            // Updates the select element to have the value of the equivalent option
            listItems.click(function (e) {
                e.stopPropagation();

                select_element.val($(this).attr('rel'));
                select_element.trigger('change');

                $(document.body).trigger('dhlpwc:select_parcelshop', [select_element.val()]);
            });

        });

    }).on('dhlpwc:select_parcelshop', function(e, parcelshop_id) {
        var listItems = $('.dhlpwc-stylishselect-options').children('li');
        listItems.each(function() {
           $(this).removeClass('dhlpwc-active');
        });

        var chosenItem = $('.dhlpwc-stylishselect-options').find('li[rel=' + parcelshop_id + ']');
        chosenItem.addClass('dhlpwc-active');
        $('.dhlpwc-stylishselect-options').animate({
            scrollTop: chosenItem.position().top - $('.dhlpwc-stylishselect-options li:first').position().top
        });

    }).on('dhlpwc:display_parcelshop_stylishselect', function(e, parcelshop_id) {
        me = $(dhlpwc_stylishselect.identifier);
        stylishselect = me.next('div.dhlpwc-stylishselect');

        // Search parcelshop_id text
        var parcelshop_info = $('ul.dhlpwc-stylishselect-options').find("li[rel='" + parcelshop_id + "']");
        stylishselect.text(parcelshop_info[0].innerText.split('\n')[0]).removeClass('active');

    });

});
