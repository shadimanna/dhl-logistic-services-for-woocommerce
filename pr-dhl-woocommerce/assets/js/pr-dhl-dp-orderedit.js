( function( $ ) {

	hide_show_packet_return = function( value ){
		var packet_return 	= jQuery( '.pr_dhl_packet_return_field' );
		
		packet_return.hide();

		for( var i=0; i<pr_dhl_dp_obj.packet_return_product.length; i++ ){
			
			if( value == pr_dhl_dp_obj.packet_return_product[i] ){
				packet_return.show();
			}

		}
	}

	var wc_dhl_dp_order_edit = {
		// init Class
		init: function() {
			$( '#pr_dhl_product' )
				.on( 'change', this.toggle_packet_return );

			this.update();
		},

		update: function () {
			
			var value = jQuery( '#pr_dhl_product' ).val();
			hide_show_packet_return( value );
		},

		toggle_packet_return: function( evt ){
			evt.preventDefault();

			var value = jQuery( this ).val();
			hide_show_packet_return( value );
			
		}
	};

	wc_dhl_dp_order_edit.init();

} )( jQuery );