( function( $ ) {
	var posts_filter = jQuery( '#posts-filter, #wc-orders-filter' );

	var wc_dhl_paket_order_bulk = {
		// init Class
		init: function() {
			posts_filter.on( 'change', '#bulk-action-selector-top', this.toggle_paket_pickup_modal );
			posts_filter.on( 'change', '#bulk-action-selector-bottom', this.toggle_paket_pickup_modal );
			posts_filter.on( 'click', '.button.action', this.disable_submit_button );

			$( '#dhl-paket-action-request-pickup' )
				.on( 'change', '[name=pr_dhl_request_pickup_modal]', this.show_hide_request_pickup_date_asap );
			this.show_hide_request_pickup_date_asap();

			$( '#dhl-paket-action-request-pickup')
					.on( 'click', '#pr_dhl_pickup_proceed', this.submit_paket_pickup_modal );

		},

		disable_submit_button: function( evt ) {
			var bulkactions = jQuery( this ).closest( '.bulkactions' );
			var bulkdropdown = bulkactions.find( 'select[name=action]' );

			if ( 'pr_dhl_create_labels' === bulkdropdown.val() || 'pr_dhl_delete_labels' === bulkdropdown.val() ) {
				jQuery( this ).prop( 'disabled', true );
				posts_filter.submit();
			}
		},

		toggle_paket_pickup_modal: function( evt ){
			evt.preventDefault();

			var value 		= jQuery( this ).val();
			var title 		= jQuery(':selected', this ).text();

			if( 'pr_dhl_request_pickup' == value ){

				// Show thickbox modal.
				tb_show( "", '/?TB_inline=true&width=320&height=290&inlineId=dhl-paket-pickup-modal' );
				jQuery("#TB_window #TB_ajaxWindowTitle").text(title); // Set title

			}else{
				jQuery('#TB_closeWindowButton').click();

			}

		},

		show_hide_request_pickup_date_asap: function () {
			var pickup_checked = $( '#dhl-paket-action-request-pickup [name=pr_dhl_request_pickup_modal]:checked' ).val();

			//console.log('pickup_checked: '+ pickup_checked );

			if ( pickup_checked == 'date' ) {
				$( '#dhl-paket-action-request-pickup #pr_dhl_request_pickup_date_modal').prop('disabled', false);
				$( '#dhl-paket-action-request-pickup .pr_dhl_request_pickup_date_field').show();
			} else {
				$( '#dhl-paket-action-request-pickup #pr_dhl_request_pickup_date_modal').prop('disabled', 'disabled');
				$( '#dhl-paket-action-request-pickup .pr_dhl_request_pickup_date_field').hide();
			}
		},

		submit_paket_pickup_modal: function( evt ){
			evt.preventDefault();

			var elemModal = jQuery('#dhl-paket-action-request-pickup');

			var pickup_type 		= elemModal.find( 'input[name=pr_dhl_request_pickup_modal]:checked' ).val();
			var pickup_date 		= elemModal.find( 'input[name=pr_dhl_request_pickup_date_modal]' ).val();

			jQuery('#posts-filter [name=dhlpickup], #wc-orders-filter [name=dhlpickup]').val(pickup_type);
			jQuery('#posts-filter [name=dhlpickup_d],#wc-orders-filter [name=dhlpickup_d]').val(pickup_date);

			//Submit bulk action
			jQuery('#posts-filter #doaction, #wc-orders-filter #doaction').first().click();

		}
	};

	wc_dhl_paket_order_bulk.init();

} )( jQuery );
