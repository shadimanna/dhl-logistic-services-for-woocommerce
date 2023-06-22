( function( $ ) {

	var wc_dhl_dp_order_lists = {
		// init Class
		init: function() {
			$( '#posts-filter' )
				.on( 'change', '#bulk-action-selector-top', this.toggle_awb_copy_count );

			$( '#wc-orders-filter' )
				.on( 'change', '#bulk-action-selector-top', this.toggle_awb_copy_count );

			this.update();
		},

		update: function () {
			$( '.dhl-awb-filter-container' ).hide();
		},

		toggle_awb_copy_count: function( evt ){
			evt.preventDefault();

			var value 		= jQuery( this ).val();
			var post_form 	= jQuery( this ).parents();

			post_form.find('.tablenav.bottom .dhl-awb-filter-container').remove();
			
			if( 'pr_dhl_create_orders' == value ){
				post_form.find( '.dhl-awb-filter-container' ).show();
			}else{
				post_form.find( '.dhl-awb-filter-container' ).hide();
			}
			
		}
	};

	wc_dhl_dp_order_lists.init();

} )( jQuery );