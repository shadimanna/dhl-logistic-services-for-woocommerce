/**
 * Ajax call to sync orders!
 */
function dhlTestConnection(btn_id) {
  var $ = jQuery;
  var btn = $(btn_id);

  // Remove elements after button
  btn.nextAll().remove();

  btn.attr('disabled', true);
  btn.text('Testing Connection...');

  var loaderContainer = $('<span/>', {
    'class': 'loader-image-container'
  }).insertAfter(btn);

  var loader = $('<img/>', {
    src: dhl_test_con_obj.loader_image,
    'class': 'loader-image'
  }).appendTo(loaderContainer);

  var data = {
    'action': 'test_dhl_connection',
    'test_con_nonce': dhl_test_con_obj.test_con_nonce
  };
  
  // We can also pass the url value separately from ajaxurl for front end AJAX implementations
  $.post(dhl_test_con_obj.ajax_url, data, function(response) {
    btn.attr('disabled', false);
    btn.text(response.button_txt);
    loaderContainer.remove();

    var success = response.connection_success;
    var test_connection_class = 'dhl_connection_' + (success ? 'succeeded' : 'error');
    var test_connection_text = success ? response.connection_success : response.connection_error;

    loaderContainer = $('<span/>', {
      'class': test_connection_class
    }).insertAfter(btn);

    loaderContainer.append(test_connection_text);
  });
}
