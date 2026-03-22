function dhlInternetmarkeAction(btn_id, action) {
  var $ = jQuery;
  var btn = $(btn_id);
  var originalText = btn.text();

  btn.nextAll().remove();
  btn.attr('disabled', true);
  btn.text(originalText + '...');

  var loaderContainer = $('<span/>', {
    'class': 'loader-image-container'
  }).insertAfter(btn);

  $('<img/>', {
    src: dhl_internetmarke_obj.loader_image,
    'class': 'loader-image'
  }).appendTo(loaderContainer);

  $.post(dhl_internetmarke_obj.ajax_url, {
    action: 'pr_dhl_internetmarke_action',
    nonce: dhl_internetmarke_obj.nonce,
    internetmarke_action: action,
    button_label: dhl_internetmarke_obj.labels[action] || originalText
  }, function(response) {
    btn.attr('disabled', false);
    btn.text(dhl_internetmarke_obj.labels[action] || originalText);
    loaderContainer.remove();

    var success = response.connection_success;
    var cssClass = 'dhl_connection_' + (success ? 'succeeded' : 'error');
    var text = success ? response.connection_success : response.connection_error;

    $('<span/>', {
      'class': cssClass,
      text: text
    }).insertAfter(btn);
  });
}
