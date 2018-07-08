jQuery( function( $ ) {

	var wc_shipment_dhl_label_items = {
	
		// init Class
		init: function() {
			$( '#woocommerce-shipment-dhl-label' )
				.on( 'click', '.dhl-label-button', this.save_dhl_label );

			$( '#woocommerce-shipment-dhl-label' )
				.on( 'click', 'a.dhl_delete_label', this.delete_dhl_label );

			$( '#woocommerce-shipment-dhl-label-express' )
				.on( 'click', '.dhl-label-button', this.save_dhl_label );

			$( '#woocommerce-shipment-dhl-label-express' )
				.on( 'click', 'a.dhl_delete_label', this.delete_dhl_label );

			$( '#woocommerce-shipment-dhl-invoice-upload' )
				.on( 'click', 'button.upload-invoice-button', this.upload_invoice );
		},

		// Client-side invoice upload handler
		upload_invoice: function() {
			var button = $(this);
			var invoice_field = button.siblings('.pr_dhl_invoice_field').find('input#pr_dhl_invoice');

			if (invoice_field.length) {
				var file_data = invoice_field.prop('files')[0],
					error_message;

				if ('undefined' !== typeof file_data) {
					var form_data = new FormData();
					form_data.append('file', file_data);
					form_data.append('action', 'wc_shipment_dhl_upload_invoice');
					form_data.append('order_id', woocommerce_admin_meta_boxes.post_id);

					// Here, we're using WP default spinner. Since calling jquery "show" is not enough because
					// the "visibility" attribute is defaulted to hidden, therefore, we're forcing it to show
					// by changing the said attribute to either 'visible' or 'hidden' whenever applicable.
					$('.dhl-invoice-upload-spinner-container > .spinner').css('visibility', 'visible').show();
					$('.dhl-invoice-upload-spinner-container > .dhl-invoice-upload-message').hide();
					button.attr('disabled', 'disabled');

					$.ajax({
						url: woocommerce_admin_meta_boxes.ajax_url,
						dataType: 'json',
						mimeType: 'multipart/form-data',
						cache: false,
						contentType: false,
						processData: false,
						data: form_data,                         
						type: 'post',
						success: function(response, status, jqXHR) {
							if ('undefined' !== typeof response.code) {
								if ('upload_complete' === response.code) {
									invoice_field.val('').attr('type', '').attr('type', 'file');
									$('.dhl-invoice-upload-spinner-container > .dhl-invoice-upload-message > a').attr('href', response.upload_url).parent().show();
								} else {
									error_message = response.error_message;
								}
							} else {
								error_message = 'Sorry! we are unable to recognize the response coming from the server.';
							}
						},
						error: function(jqXHR, status, error) {
							console.log('Error details follows:');
							console.log(error);
						},
						complete: function(jqXHR, status) {
							$('.dhl-invoice-upload-spinner-container > .spinner').css('visibility', 'hidden').hide();
							button.removeAttr('disabled');

							if ('undefined' !== typeof error_message && error_message) {
								alert(error_message);
							}
						}
					});

				} else {
					alert('Please kindly select an invoice file before you click the upload button.');
				}
			}
			
			return false;
		},
	
		save_dhl_label: function () {
			// console.log(dhl_label_data);
			// Remove any errors from last attempt to create label
			var form_id;
			var action_id;
			if( $(this).closest("#woocommerce-shipment-dhl-label-express").length ) {
				form_id = '#woocommerce-shipment-dhl-label-express';
				action_id = 'wc_shipment_dhl_gen_label_express';
			} else {
				if( $(this).closest("#woocommerce-shipment-dhl-label").length ) {
					form_id = '#woocommerce-shipment-dhl-label';
					action_id = 'wc_shipment_dhl_gen_label';
				}
			}

			// console.log(form_id);
			$( form_id + ' .shipment-dhl-label-form .wc_dhl_error' ).remove();

			$( form_id + ' .shipment-dhl-label-form' ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			} );
			
			// loop through inputs within id 'shipment-dhl-label-form'
			
			var data = {
				action:                   action_id,
				order_id:                 woocommerce_admin_meta_boxes.post_id,
			};
			
			// var data = new Array();
			$(function(){ 
				$(form_id + ' .shipment-dhl-label-form').each(function(i, div) {

				    $(div).find('input').each(function(j, element){
				        // $(element).attr('disabled','disabled');
				        // console.log( $(element).attr('name') );
				        if( $(element).attr('type') == 'checkbox' ) {
				        	// console.log($(element).prop('checked'));
				        	if ( $(element).prop('checked') ) {
					        	data[ $(element).attr('name') ] = 'yes';
				        	} else {
					        	data[ $(element).attr('name') ] = 'no';
				        	}
				        } else {
				        	data[ $(element).attr('name') ] = $(element).val();
				        }
				    });

				    $(div).find('select').each(function(j, element){
				        // $(element).attr('disabled','disabled');
				        // console.log( $(element).attr('name') );
			        	data[ $(element).attr('name') ] = $(element).val();
				    });

				    $(div).find('textarea').each(function(j, element){
				        // $(element).attr('disabled','disabled');
				        // console.log( $(element).attr('name') );
			        	data[ $(element).attr('name') ] = $(element).val();
				    });
		    	});
		    });
			
			console.log(data);
			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
				$( form_id + ' .shipment-dhl-label-form' ).unblock();
				if ( response.error ) {
					$( form_id + ' .shipment-dhl-label-form' ).append('<p class="wc_dhl_error">' + response.error + '</p>');
				} else {
					// Disable all form items
					$(function(){ 
						$(form_id + ' .shipment-dhl-label-form').each(function(i, div) {

						    $(div).find('input').each(function(j, element){
						       $(element).prop('disabled', 'disabled');
						    });

						    $(div).find('select').each(function(j, element){
						        $(element).prop('disabled', 'disabled');
						    });

						    $(div).find('textarea').each(function(j, element){
						        $(element).prop('disabled','disabled');
						    });
				    	});
				    });

					$( form_id + ' .dhl-label-button').remove();
					$( form_id + ' .shipment-dhl-label-form' ).append(dhl_label_data.print_button);
					$( form_id + ' .dhl-label-print').attr("href", response.label_url ); // update new url
					$( form_id + ' .shipment-dhl-label-form' ).append(dhl_label_data.delete_label);

					if( response.tracking_note ) {

						$( '#woocommerce-order-notes' ).block({
							message: null,
							overlayCSS: {
								background: '#fff',
								opacity: 0.6
							}
						});
						
						var data = {
							action:                   'woocommerce_add_order_note',
							post_id:                  woocommerce_admin_meta_boxes.post_id,
							note_type: 				  'customer',
							note:					  response.tracking_note,
							security:                 woocommerce_admin_meta_boxes.add_order_note_nonce
						};

						$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response_note ) {
							// alert(response_note);
							$( 'ul.order_notes' ).prepend( response_note );
							$( '#woocommerce-order-notes' ).unblock();
							$( '#add_order_note' ).val( '' );
						});							
					}

				}
			});				

			return false;
		},

		delete_dhl_label: function () {

			var form_id;
			if( $(this).closest("#woocommerce-shipment-dhl-label-express").length ) {
				form_id = '#woocommerce-shipment-dhl-label-express';
				action_id = 'wc_shipment_dhl_delete_label_express';
			} else {
				if( $(this).closest("#woocommerce-shipment-dhl-label").length ) {
					form_id = '#woocommerce-shipment-dhl-label';
					action_id = 'wc_shipment_dhl_delete_label';
				}
			}

			$( form_id + ' .shipment-dhl-label-form .wc_dhl_error' ).remove();

			$( form_id + ' .shipment-dhl-label-form' ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			} );
			
			var data = {
				action:                   action_id,
				order_id:                 woocommerce_admin_meta_boxes.post_id,
				pr_dhl_label_nonce:       $( '#pr_dhl_label_nonce' ).val()
			};
			
			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
				$( form_id + ' .shipment-dhl-label-form' ).unblock();
				if ( response.error ) {
					$( form_id + ' .shipment-dhl-label-form' ).append('<p class="wc_dhl_error">Error: ' + response.error + '</p>');
				} else {

					$(  form_id + ' .shipment-dhl-label-form .wc_dhl_delete' ).remove();
					// Enable all form items
					$(function(){ 
						$(form_id + ' .shipment-dhl-label-form').each(function(i, div) {

						    $(div).find('input').each(function(j, element){
						       $(element).removeAttr('disabled');
						    });

						    $(div).find('select').each(function(j, element){
						        $(element).removeAttr('disabled');
						    });

						    $(div).find('textarea').each(function(j, element){
						        $(element).removeAttr('disabled');
						    });

				    	});
				    });
					
					$( form_id + ' .dhl-label-print').remove();
					$( form_id + ' .shipment-dhl-label-form' ).append(dhl_label_data.main_button);

					if( response.dhl_tracking_num ) {
						// alert(response.dhl_tracking_num);
						var tracking_note;
						$('ul.order_notes li').each(function(i) {
						   tracking_note = $(this);
						   tracking_note_html = $(this).html()
						   if (tracking_note_html.indexOf(response.dhl_tracking_num) >= 0) {
							
								// var tracking_note = $( this ).closest( 'li.tracking_note' ); 
								$( tracking_note ).block({
									message: null,
									overlayCSS: {
										background: '#fff',
										opacity: 0.6
									}
								});

								var data_note = {
									action:   'woocommerce_delete_order_note',
									note_id:  $( tracking_note ).attr( 'rel' ),
									security: woocommerce_admin_meta_boxes.delete_order_note_nonce
								};

								$.post( woocommerce_admin_meta_boxes.ajax_url, data_note, function() {
									$( tracking_note ).remove();
								});
								
							   	return false;					   	
							}
						});
					}
				}
			});

			return false;
		}
	}
	
	wc_shipment_dhl_label_items.init();

} );
