( function( $, PR_DHL_DP ) {

	var wc_dhl_dp_order_items = {
		// init Class
		init: function() {
			$( '#woocommerce-dhl-dp-order' )
				.on( 'click', '#pr_dhl_add_to_order', this.add_item_to_order );

			$( '#woocommerce-dhl-dp-order' )
				.on( 'click', '.pr_dhl_order_remove_item', this.remove_item_from_order );

			$( '#woocommerce-dhl-dp-order' )
				.on( 'click', '#pr_dhl_create_order', this.create_order );

			$( '#woocommerce-dhl-dp-order' )
				.on( 'click', '#pr_dhl_dp_download_awb_label', this.download_awb_label );

			$( '#woocommerce-dhl-dp-order' )
				.on( 'click', '#pr_dhl_dp_delete_label', this.reset_order );

			$( document ).on( 'pr_dhl_saved_label', this.get_order_items );
			$( document ).on( 'pr_dhl_deleted_label', this.get_order_items );

			this.update();
		},

		update: function ( response ) {
			// Re-enable the "remove item" links
			$( '.pr_dhl_order_remove_item').removeAttr( 'disabled' );

			if ( response && response.html ) {
				$( '#woocommerce-dhl-dp-order .inside' ).html( response.html );
			}

			if ( response && response.error ) {
				$( '#pr_dhl_dp_error' ).text( response.error ).show();
			} else {
				$( '#pr_dhl_dp_error' ).hide();
			}

			var create_order_button = $( '#pr_dhl_create_order' ),
				add_item_button = $( '#pr_dhl_add_to_order' ),
				gen_label_message = $( '#pr_dhl_order_gen_label_message' ),
				reset_order_button = $( '#pr_dhl_reset_order' );

			// Check if download label button is found on the page
			var has_label = $( '#dhl-label-print' ).length > 0;
			// Check if showing the shipments table
			var showing_shipments = $( '#pr_dhl_order_shipments_table' ).length > 0;

			// Enable the "add" button if the item has a label and not showing the shipments
			add_item_button.toggleClass( 'disabled', !(has_label && !showing_shipments) );
			// Show the generate label message if the item has no label and not showing the shipments
			gen_label_message.toggle( !has_label && !showing_shipments );
			// Hide the "create order" button if showing the shipments table
			create_order_button.toggle( !showing_shipments );
			// Show the reset order button if showing the shipments table
			reset_order_button.toggle( showing_shipments );

			// Check if there are items in the items table
			var has_items = $( '#pr_dhl_order_items_table tbody tr:not("#pr_dhl_no_items_msg")' ).length;
			// If there are items in the items table, enable the create order button. Otherwise disable it
			create_order_button.toggleClass( 'disabled', !has_items );
		},

		lock_order_controls: function () {
			// Disable the buttons
			$( '#pr_dhl_add_to_order, #pr_dhl_create_order' ).addClass( 'disabled' );
			// Disable the "remove item" links
			$( '.pr_dhl_order_remove_item').attr( 'disabled', 'disabled' );
		},

		get_order_items: function () {
			var data = {
				action:				'wc_shipment_dhl_get_order_items',
				order_id:			woocommerce_admin_meta_boxes.post_id,
				pr_dhl_order_nonce:	$( '#pr_dhl_order_nonce' ).val()
			};

			wc_dhl_dp_order_items.lock_order_controls();
			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
				wc_dhl_dp_order_items.update( response );
			} );
		},

		add_item_to_order: function () {
			var data = {
				action:				'wc_shipment_dhl_add_order_item',
				order_id:			woocommerce_admin_meta_boxes.post_id,
				pr_dhl_order_nonce:	$( '#pr_dhl_order_nonce' ).val()
			};

			wc_dhl_dp_order_items.lock_order_controls();
			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
				wc_dhl_dp_order_items.update( response );
			} );
		},

		remove_item_from_order: function ( event ) {
			var click_target = $( event.target );
			var item_row = click_target.closest('tr');
			var item_barcode = item_row.find('.pr_dhl_item_barcode').val();
			var data = {
				action:				'wc_shipment_dhl_remove_order_item',
				order_id:			woocommerce_admin_meta_boxes.post_id,
				item_barcode:		item_barcode,
				pr_dhl_order_nonce:	$( '#pr_dhl_order_nonce' ).val()
			};

			wc_dhl_dp_order_items.lock_order_controls();
			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
				wc_dhl_dp_order_items.update( response );
			} );
		},

		create_order: function () {
			if (!confirm(PR_DHL_DP.create_order_confirmation)) {
				return;
			}

			var data = {
				action:				'wc_shipment_dhl_create_order',
				order_id:			woocommerce_admin_meta_boxes.post_id,
				pr_dhl_order_nonce:	$( '#pr_dhl_order_nonce' ).val()
			};

			wc_dhl_dp_order_items.lock_order_controls();
			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
				wc_dhl_dp_order_items.update( response );

				if (response.tracking) {
					$('a#dhl_delete_label').parent().remove();
				}
			} );
		},

		reset_order: function () {
			var data = {
				action:				'wc_shipment_dhl_reset_order',
				order_id:			woocommerce_admin_meta_boxes.post_id,
				pr_dhl_order_nonce:	$( '#pr_dhl_order_nonce' ).val()
			};

			wc_dhl_dp_order_items.lock_order_controls();
			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
				wc_dhl_dp_order_items.update( response );
			} );
		},

		download_awb_label: function () {
			// Get the AWB from the sibling table cell's text
			var awb = $(this).closest('tr').find('.pr_dhl_shipment_awb').text();
			var data = {
				action:				'wc_shipment_dhl_download_awb_label',
				awb:				awb,
				pr_dhl_order_nonce:	$( '#pr_dhl_order_nonce' ).val()
			};

			wc_dhl_dp_order_items.lock_order_controls();
			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
				wc_dhl_dp_order_items.update( response );

				if ( response.label_url ) {
					window.location = response.label_url;
				}
			} );
		},

		add_wc_order_note: function (tracking_note, tracking_note_type) {
			$( '#woocommerce-order-notes' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			var data = {
				action:		'woocommerce_add_order_note',
				post_id:	woocommerce_admin_meta_boxes.post_id,
				note_type:	tracking_note_type,
				note:		tracking_note,
				security:	woocommerce_admin_meta_boxes.add_order_note_nonce
			};

			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response_note ) {
				// alert(response_note);
				$( 'ul.order_notes' ).prepend( response_note );
				$( '#woocommerce-order-notes' ).unblock();
				$( '#add_order_note' ).val( '' );
			});
		},
	};

	wc_dhl_dp_order_items.init();

} )(jQuery, PR_DHL_DP);
