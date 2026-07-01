/**
 * INTERNETMARKE order metabox JS.
 *
 * Follows the same structural pattern as pr-dhl.js (Paket metabox):
 * - Uses dhl_im_label_data for pre-translated button HTML (built in PHP).
 * - Error handling mirrors save_dhl_label / delete_dhl_label in pr-dhl.js.
 *
 * Handles:
 *  - Product change → show/hide service checkboxes
 *  - EINSCHREIBEN EINWURF / EINSCHREIBEN mutual exclusivity
 *  - RÜCKSCHEIN visibility (only when EINSCHREIBEN is checked)
 *  - Generate Label AJAX call
 *  - Delete Label AJAX call
 */
jQuery( function ( $ ) {

	var imOrder = {

		form: '#shipment-im-label-form',

		init: function () {
			var self = this;

			// Product dropdown change.
			$( self.form ).on( 'change', '#pr_dhl_im_product', function () {
				self.update_services();
			} );

			// EINSCHREIBEN EINWURF → uncheck EINSCHREIBEN, hide RÜCKSCHEIN.
			$( self.form ).on( 'change', '#pr_dhl_im_service_einschreiben_einwurf', function () {
				if ( $( this ).prop( 'checked' ) ) {
					$( '#pr_dhl_im_service_einschreiben' ).prop( 'checked', false );
					self.update_rueckschein_visibility();
				}
			} );

			// EINSCHREIBEN → uncheck EINSCHREIBEN EINWURF, toggle RÜCKSCHEIN.
			$( self.form ).on( 'change', '#pr_dhl_im_service_einschreiben', function () {
				if ( $( this ).prop( 'checked' ) ) {
					$( '#pr_dhl_im_service_einschreiben_einwurf' ).prop( 'checked', false );
				}
				self.update_rueckschein_visibility();
			} );

			// Generate Label button.
			$( self.form ).on( 'click', '#im-label-button', function ( e ) {
				e.preventDefault();
				self.generate_label();
			} );

			// Delete Label link — delegated so it also works on dynamically inserted links.
			$( self.form ).on( 'click', '#im-delete-label', function ( e ) {
				e.preventDefault();
				self.delete_label();
			} );

			// Set initial service visibility for the currently selected product.
			self.update_services();
		},

		/**
		 * Show or hide service checkbox rows based on the selected product.
		 * Re-runs RÜCKSCHEIN visibility afterwards.
		 */
		update_services: function () {
			var self        = this;
			var product     = $( '#pr_dhl_im_product' ).val();
			var servicesMap = ( typeof dhl_im_order_data !== 'undefined' ) ? dhl_im_order_data.services_map : {};
			var available   = servicesMap[ product ] || [];

			$( self.form + ' .im-service-row' ).each( function () {
				var service = $( this ).data( 'service' );
				if ( available.indexOf( service ) !== -1 ) {
					$( this ).show();
				} else {
					$( this ).hide();
					// Uncheck hidden services so they are not submitted.
					$( this ).find( 'input[type="checkbox"]' ).prop( 'checked', false );
				}
			} );

			self.update_rueckschein_visibility();
		},

		/**
		 * RÜCKSCHEIN is only valid when EINSCHREIBEN (not EINWURF) is checked.
		 * Availability is determined from the services map (not from CSS state)
		 * so this works correctly whether the row is currently shown or hidden.
		 */
		update_rueckschein_visibility: function () {
			var product     = $( '#pr_dhl_im_product' ).val();
			var servicesMap = ( typeof dhl_im_order_data !== 'undefined' ) ? dhl_im_order_data.services_map : {};
			var available   = servicesMap[ product ] || [];

			if ( available.indexOf( 'rueckschein' ) === -1 ) {
				return; // Not available for this product.
			}

			var $einschreiben = $( '#pr_dhl_im_service_einschreiben' );
			var $rueckschein  = $( '.im-service-row-rueckschein' );

			if ( $einschreiben.prop( 'checked' ) ) {
				$rueckschein.show();
			} else {
				$rueckschein.hide();
				$( '#pr_dhl_im_service_rueckschein' ).prop( 'checked', false );
			}
		},

		/**
		 * Collect form inputs and POST to the generate-label AJAX action.
		 * Mirrors save_dhl_label() in pr-dhl.js.
		 */
		generate_label: function () {
			var self = this;

			if ( typeof dhl_im_order_data === 'undefined' ) {
				return;
			}

			var $form = $( self.form );
			var data  = {
				action:                'wc_shipment_internetmarke_gen_label',
				order_id:              dhl_im_order_data.order_id,
				pr_dhl_im_label_nonce: dhl_im_order_data.nonce,
				pr_dhl_im_product:     $( '#pr_dhl_im_product' ).val(),
				pr_dhl_im_services:    [],
			};

			// Collect checked service checkboxes.
			$form.find( 'input[name="pr_dhl_im_services[]"]:checked' ).each( function () {
				data.pr_dhl_im_services.push( $( this ).val() );
			} );

			// Remove errors from any previous attempt — same as Paket.
			$form.find( '.wc_dhl_error' ).remove();

			$form.block( {
				message: null,
				overlayCSS: { background: '#fff', opacity: 0.6 },
			} );

			$.post( dhl_im_order_data.ajax_url, data, function ( response ) {
				$form.unblock();

				if ( response.error ) {
					$form.append( '<p class="wc_dhl_error">' + response.error + '</p>' );
					return;
				}

				// Disable all form inputs — same as Paket success flow.
				$form.find( 'input, select, textarea' ).prop( 'disabled', true );
				$( '#im-label-button' ).remove();

				// Inject pre-translated button HTML (built in PHP, same pattern as dhl_label_data).
				if ( typeof dhl_im_label_data !== 'undefined' ) {
					$form.append( dhl_im_label_data.print_button );
					$( '#im-label-print' ).attr( 'href', response.label_url || '#' );
					$form.append( dhl_im_label_data.delete_label );
				}

				$( document ).trigger( 'pr_dhl_im_saved_label' );
			} ).fail( function () {
				$form.unblock();
				if ( typeof dhl_im_order_data !== 'undefined' && dhl_im_order_data.ajax_error ) {
					$form.append( '<p class="wc_dhl_error">' + dhl_im_order_data.ajax_error + '</p>' );
				}
			} );
		},

		/**
		 * DELETE the stored label and revert the metabox.
		 * Mirrors delete_dhl_label() in pr-dhl.js.
		 */
		delete_label: function () {
			var self = this;

			if ( typeof dhl_im_order_data === 'undefined' ) {
				return;
			}

			var $form = $( self.form );
			$form.find( '.wc_dhl_error' ).remove();

			$form.block( {
				message: null,
				overlayCSS: { background: '#fff', opacity: 0.6 },
			} );

			var data = {
				action:                'wc_shipment_internetmarke_delete_label',
				order_id:              dhl_im_order_data.order_id,
				pr_dhl_im_label_nonce: dhl_im_order_data.nonce,
			};

			$.post( dhl_im_order_data.ajax_url, data, function ( response ) {
				$form.unblock();

				if ( response.error ) {
					$form.append( '<p class="wc_dhl_error">' + response.error + '</p>' );
					return;
				}

				// Reload so the metabox reverts to its initial state — same as Paket delete flow.
				window.location.reload();
			} ).fail( function () {
				$form.unblock();
				if ( typeof dhl_im_order_data !== 'undefined' && dhl_im_order_data.ajax_error ) {
					$form.append( '<p class="wc_dhl_error">' + dhl_im_order_data.ajax_error + '</p>' );
				}
			} );
		},
	};

	if ( $( '#shipment-im-label-form' ).length ) {
		imOrder.init();
	}

} );
