/**
 * Internetmarke "Test Account Connection" button.
 *
 * Reads the Internetmarke credential fields, saves them and tests the
 * connection to Deutsche Post in a single AJAX call. Mirrors the response
 * shape (connection_success / connection_error) used by the Paket test
 * connection button so the existing CSS classes apply.
 */
function dhlInternetmarkeTestConnection( btn_id ) {
  var $   = jQuery;
  var btn = $( btn_id );

  // Remove any result from a previous attempt.
  btn.nextAll().remove();

  var original = btn.text();
  btn.attr( 'disabled', true );
  btn.text( dhl_im_test_con_obj.testing_txt );

  var loaderContainer = $( '<span/>', { 'class': 'loader-image-container' } ).insertAfter( btn );
  $( '<img/>', { src: dhl_im_test_con_obj.loader_image, 'class': 'loader-image' } ).appendTo( loaderContainer );

  var data = {
    action:            'test_dhl_internetmarke_connection',
    im_test_con_nonce: dhl_im_test_con_obj.nonce,
    username:          $( '#woocommerce_pr_dhl_paket_internetmarke_api_user' ).val(),
    password:          $( '#woocommerce_pr_dhl_paket_internetmarke_api_password' ).val(),
    portokasse_id:     $( '#woocommerce_pr_dhl_paket_internetmarke_portokasse_id' ).val()
  };

  var genericError = dhl_im_test_con_obj.error_txt || 'Connection test failed. Please try again.';

  $.post( dhl_im_test_con_obj.ajax_url, data, function ( response ) {
    btn.attr( 'disabled', false );
    btn.text( ( response && response.button_txt ) || original );
    loaderContainer.remove();

    var success = response && response.connection_success;
    var cssClass = 'dhl_connection_' + ( success ? 'succeeded' : 'error' );
    // On a -1/0 body (nonce failure / not logged in) neither field is set, so fall
    // back to a generic message instead of rendering an empty error span.
    var text = success
      ? response.connection_success
      : ( ( response && response.connection_error ) || genericError );

    $( '<span/>', { 'class': cssClass, text: text } ).insertAfter( btn );
  } ).fail( function () {
    // On a timeout or HTTP error the success callback never runs. Without this the
    // button stays disabled on the spinner forever, even though the credentials were
    // already saved server-side.
    btn.attr( 'disabled', false );
    btn.text( original );
    loaderContainer.remove();
    $( '<span/>', { 'class': 'dhl_connection_error', text: genericError } ).insertAfter( btn );
  } );
}
