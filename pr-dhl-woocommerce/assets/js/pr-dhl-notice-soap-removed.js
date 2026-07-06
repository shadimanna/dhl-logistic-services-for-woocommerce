/**
 * Persist dismissal of the "SOAP API removed" migration notice.
 *
 * When the admin clicks the core dismiss (X) on the notice, record it server-side so
 * the notice does not come back on the next page load.
 */
jQuery( function ( $ ) {
	$( document ).on( 'click', '.pr-dhl-soap-removed-notice .notice-dismiss', function () {
		if ( typeof pr_dhl_soap_removed_notice === 'undefined' ) {
			return;
		}

		$.post( pr_dhl_soap_removed_notice.ajax_url, {
			action: 'pr_dhl_dismiss_soap_removed_notice',
			nonce: pr_dhl_soap_removed_notice.nonce,
		} );
	} );
} );
