jQuery( function( $ ) {

	var wc_shipment_dhl_label_items = {

		// init Class
		init: function() {
			$( '#woocommerce-shipment-dhl-label' )
				.on( 'click', '#dhl-label-button', this.save_dhl_label );

			$( '#woocommerce-shipment-dhl-label' )
				.on( 'click', 'a#dhl_delete_label', this.delete_dhl_label );

			$( '#woocommerce-shipment-dhl-label' )
				.on( 'change', '#pr_dhl_return_address_enabled', this.show_hide_return );
			wc_shipment_dhl_label_items.show_hide_return();

			$( '#woocommerce-shipment-dhl-label' )
				.on( 'change', '#pr_dhl_identcheck', this.show_hide_ident );
			wc_shipment_dhl_label_items.show_hide_ident();

			$( '#woocommerce-shipment-dhl-label' )
				.on( 'change', '#pr_dhl_routing', this.show_hide_routing);
			wc_shipment_dhl_label_items.show_hide_routing();

			$( '#woocommerce-shipment-dhl-label' )
				.on( 'change', '#pr_dhl_product', this.validate_product_return )
				.on( 'change', '#pr_dhl_product', this.enable_disable_paket_international_fields )
				.on( 'change', '#pr_dhl_product', this.enable_disable_signature_service )
	        	.on( 'change', '#pr_dhl_product', this.enable_disable_mrn );

			wc_shipment_dhl_label_items.enable_disable_paket_international_fields();
			wc_shipment_dhl_label_items.enable_disable_signature_service();

			$( '#woocommerce-shipment-dhl-label' )
				.on( 'change', 'select#pr_dhl_total_packages', this.process_package_action );

			$( '#woocommerce-shipment-dhl-label' )
				.on( 'change', '#pr_dhl_multi_packages_enabled', this.show_hide_packages );
			wc_shipment_dhl_label_items.show_hide_packages();

			$( '#woocommerce-shipment-dhl-label' )
				.on( 'change', '#pr_dhl_PDDP', this.enable_disable_duties );
			wc_shipment_dhl_label_items.enable_disable_duties();

			$( '#woocommerce-shipment-dhl-label' )
				.on( 'change', '#pr_dhl_duties', this.show_hide_incoterm_tax_id );
			wc_shipment_dhl_label_items.show_hide_incoterm_tax_id();

			$( '#woocommerce-shipment-dhl-label' )
				.on( 'change', '#pr_dhl_tax_id_type', this.show_hide_tax_id );
			wc_shipment_dhl_label_items.show_hide_tax_id();
			$( '#_shipping_country' ).on( 'change', this.enable_disable_mrn );
			wc_shipment_dhl_label_items.enable_disable_mrn();

		},

		// Extract the entries for the given package attribute
		get_package_array: function($form, attrib) {
			var $element = $form.find('input[name="pr_dhl_packages_'+attrib+'[]"]');
			var result = [];

			if ('undefined' !== typeof $element && $element) {
				result = $element.map(function() {
					return $(this).val();
				}).get();
			}

			return result;
		},

		// Extract all user inputted packages. Retrieving all available
		// package info or attributes.
		get_packages_for_saving: function($form, required) {
			var total = $form.find('select#pr_dhl_total_packages').val();
			var packages = [],
				error = false,
				invalid_number = false;

			var numbers = this.get_package_array($form, 'number');
			var weights = this.get_package_array($form, 'weight');
			var lengths = this.get_package_array($form, 'length');
			var widths = this.get_package_array($form, 'width');
			var heights = this.get_package_array($form, 'height');

			for (var i=0; i<parseInt(total); i++) {
				if (required) {
					if (!numbers[i].length || !weights[i].length || !lengths[i].length || !widths[i].length || !heights[i].length) {
						error = true;
						break;
					}
				}

				packages.push({
					number: numbers[i], weight: weights[i], length: lengths[i], width: widths[i], height: heights[i]
				});
			}

			if (invalid_number) {
				return 'invalid_number';
			}

			return (!error) ? packages : false;
		},

		// Process the cloning (adding) and removing of package entries based
		// on the total packages selected by the user.
		process_package_action: function() {
			var old_value = $(this).data('current');
			var value = $(this).val();
			var $container = $('.total_packages_container');

			if (parseInt(old_value) < parseInt(value)) {
				var new_value = parseInt(value) - parseInt(old_value);
				var $clone, $package_number, new_number;

				for (var i=0; i<new_value; i++) {
					$clone = $container.find('.package_item:last').clone();
					$package_number = parseInt($clone.find('.package_number > input').data('sequence'));
					new_number = parseInt($package_number)+1;

					// We'll update both the cache and DOM to make sure that we get
					// the expected behaviour when pulling the sequence number for processing.
					$clone.find('.package_number > input').attr('data-sequence', new_number); // this updates the DOM
					$clone.find('.package_number > input').data('sequence', new_number); // this updates the jquery cache

					$clone.find('.package_number > input').val(new_number);
					$clone.find('.package_item_field.clearable > input').val('');
				$container.append($clone);
				}
			} else {
				$container.find('.package_item').slice(value).remove();
			}

			$(this).data('current', value);
		},

		validate_product_return: function () {
			var selected_product = $( '#pr_dhl_product' ).val();

			if( selected_product != 'V01PAK' && selected_product != 'V01PRIO' ) {
				$('#pr_dhl_return_address_enabled').prop('checked', false).trigger('change');
				$('#pr_dhl_return_address_enabled').prop('disabled', 'disabled');
			} else {
				$('#pr_dhl_return_address_enabled').removeAttr('disabled');
			}

		},

		show_hide_return: function () {
			var is_checked = $( '#pr_dhl_return_address_enabled' ).prop('checked');

			$( '.shipment-dhl-row-return-addr' ).children().each( function () {

				// If class exists, and is not 'pr_dhl_return_address_enabled' but is 'pr_dhl_return_' field
			    if( ( $(this).attr("class") ) &&
			    	( $(this).attr("class").indexOf('pr_dhl_return_address_enabled') == -1 ) &&
			    	( $(this).attr("class").indexOf('pr_dhl_return') >= 0 )
			    ) {

			    	if ( is_checked ) {
			    		$(this).show();
			    	} else {
			    		$(this).hide();
			    	}
			    }
			});
		},

		show_hide_tax_id: function () {
			var tax_id_type = $('select#pr_dhl_tax_id_type :selected').val();

			// If type is IOSS (DHL), or none selected
			if ( tax_id_type == '4' || tax_id_type == 'none' || tax_id_type == '' ) {
				$('.pr_dhl_tax_id_field').hide();
				$('#pr_dhl_tax_id').val('');
			} else {
				$('.pr_dhl_tax_id_field').show();

				// Country specific check based on tax id type selected
				var data = {
					action:                   'wc_shipment_dhl_check_order_country_show_tax_id',
					order_id:                 woocommerce_admin_meta_boxes.post_id,
					tax_id_type: 		      tax_id_type
				};
				$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {

					if ( response.hide_tax_id == true ) {
						$('.pr_dhl_tax_id_field').hide();
						$('#pr_dhl_tax_id').val('');
					} else {
						$('.pr_dhl_tax_id_field').show();
					}

				});

			}
		},

		show_hide_incoterm_tax_id: function () {
			var incoterm_selected = $('select#pr_dhl_duties :selected').val();

			var data = {
				action:                   'wc_shipment_dhl_check_incoterm_tax_id',
				order_id:                 woocommerce_admin_meta_boxes.post_id,
				incoterm: 				  incoterm_selected
			};

			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {

				if ( response.hide_tax_id == true ) {
					$('.pr_dhl_tax_id_field').hide();
					$('#pr_dhl_tax_id').val('');
				} else {
					$('.pr_dhl_tax_id_field').show();
				}

				if ( response.hide_tax_type == true ) {
					$('.pr_dhl_tax_id_type_field').hide();
					$('#pr_dhl_tax_id_type').val('none');
				} else {
					$('.pr_dhl_tax_id_type_field').show();

				}
			});

		},

		show_hide_ident: function () {
			var is_checked = $( '#pr_dhl_identcheck' ).prop('checked');

			$( '.shipment-dhl-row-additional-services' ).children().each( function () {

				// If class exists, and is not 'pr_dhl_return_address_enabled' but is 'pr_dhl_return_' field
			    if( ( $(this).attr("class") ) &&
			    	( $(this).attr("class").indexOf('pr_dhl_identcheck_field ') == -1 ) &&
			    	( $(this).attr("class").indexOf('pr_dhl_identcheck') >= 0 )
			    ) {

			    	if ( is_checked ) {
			    		$(this).show();
			    	} else {
			    		$(this).hide();
			    	}
			    }
			});
		},

		show_hide_routing: function () {
			var is_checked = $( '#pr_dhl_routing' ).prop('checked');

			$( '.shipment-dhl-row-additional-services' ).children().each( function () {

				// If class exists, and is not 'pr_dhl_return_address_enabled' but is 'pr_dhl_return_' field
			    if( ( $(this).attr("class") ) &&
			    	( $(this).attr("class").indexOf('pr_dhl_routing_field ') == -1 ) &&
			    	( $(this).attr("class").indexOf('pr_dhl_routing') >= 0 )
			    ) {

			    	if ( is_checked ) {
			    		$(this).show();
			    	} else {
			    		$(this).hide();
			    	}
			    }
			});
		},

		show_hide_packages: function () {
			// Only relevant for Paket so check if exists
			if ( ! $( '#pr_dhl_multi_packages_enabled' ).length ) {
			    return;
			}

			var is_checked = $( '#pr_dhl_multi_packages_enabled' ).prop('checked');

			if ( is_checked ) {
	    		$('#pr_dhl_weight').prop('disabled', 'disabled');
			} else {
				$('#pr_dhl_weight').removeAttr('disabled');
	    	}

			$( '.shipment-dhl-row-packages' ).children().each( function () {
				// If class exists, and is not 'pr_dhl_multi_packages_enabled' but is 'pr_dhl_total_packages' or 'total_packages_container' fields
			    if( ( $(this).attr("class") ) &&
			    	( $(this).attr("class").indexOf('pr_dhl_multi_packages_enabled') == -1 ) &&
			    	( ( $(this).attr("class").indexOf('pr_dhl_total_packages') >= 0 ) ||
			    	( $(this).attr("class").indexOf('total_packages_container') >= 0 ) )
			    ) {

			    	if ( is_checked ) {
			    		$(this).show();
			    	} else {
			    		$(this).hide();
			    	}
			    }
			});
		},

		save_dhl_label: function () {

			// loop through inputs within id 'shipment-dhl-label-form'

			var data = {
				action:                   'wc_shipment_dhl_gen_label',
				order_id:                 woocommerce_admin_meta_boxes.post_id,
			};

			// In case an error has occured.
			var abort = false;
			var $form = $('#shipment-dhl-label-form');
			$form.each(function(i, div) {

			    $(div).find('input').each(function(j, element){
			        if( $(element).attr('type') == 'checkbox' ) {
			        	if ( $(element).prop('checked') ) {
				        	data[ $(element).attr('name') ] = 'yes';
			        	} else {
				        	data[ $(element).attr('name') ] = 'no';
			        	}
			        } else {
			        	var eName = $(element).attr('name');
			        	// Do NOT add array inputs here!
			        	if (eName.indexOf("[]") == -1) {
			        		data[ $(element).attr('name') ] = $(element).val();
			        	}
			        }
			    });

			    $(div).find('select').each(function(j, element){
		        	data[ $(element).attr('name') ] = $(element).val();
			    });

			    $(div).find('textarea').each(function(j, element){
		        	data[ $(element).attr('name') ] = $(element).val();
			    });
	    	});

	    	// Since, we're not posting the form directly, rather we're using jquery to pull
			// the data individually, therefore, we're implementing a personalize API to extract
			// our packages and add it to the "pr_dhl_packages" field for saving.
			if( $( '#pr_dhl_multi_packages_enabled' ).prop('checked') ) {

				var packages = wc_shipment_dhl_label_items.get_packages_for_saving($form, true);
				if (!packages) {
					alert('It appears that one or more of your packages contains empty information. Please make sure you fill the package number, weight, length, width and height of the package before submitting.');
					abort = true;
				} else {
					if (packages == 'invalid_number') {
						alert('One or more of your entries contains invalid values. Only numeric values are allowed in the package line items. Please kindly check your entries and try again.');
						abort = true;
					} else {

						if (packages.length) {
							data [ 'pr_dhl_packages_number' ] = wc_shipment_dhl_label_items.get_package_array($form, 'number');
							data [ 'pr_dhl_packages_weight' ] = wc_shipment_dhl_label_items.get_package_array($form, 'weight');
							data [ 'pr_dhl_packages_length' ]  = wc_shipment_dhl_label_items.get_package_array($form, 'length');
							data [ 'pr_dhl_packages_width' ]  = wc_shipment_dhl_label_items.get_package_array($form, 'width');
							data [ 'pr_dhl_packages_height' ]  = wc_shipment_dhl_label_items.get_package_array($form, 'height');
						}
					}
				}
			}

			if (!abort) {
				// Remove any errors from last attempt to create label
				$( '#shipment-dhl-label-form .wc_dhl_error' ).remove();

				$( '#shipment-dhl-label-form' ).block( {
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				} );

				$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
					$( '#shipment-dhl-label-form' ).unblock();
					if ( response.error ) {
						$( '#shipment-dhl-label-form' ).append('<p class="wc_dhl_error">' + response.error + '</p>');
					} else {
						// Disable all form items
						$(function(){
							$('#shipment-dhl-label-form').each(function(i, div) {

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

						$( '#dhl-label-button').remove();
						$( '#shipment-dhl-label-form' ).append(dhl_label_data.print_button);
						$( '#dhl-label-print').attr("href", response.label_url ); // update new url
						$( '#shipment-dhl-label-form' ).append(dhl_label_data.delete_label);

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
								note_type: 				  response.tracking_note_type,
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

						$( document ).trigger( 'pr_dhl_saved_label' );
					}
				});
			}

			return false;
		},

		delete_dhl_label: function () {

			$( '#shipment-dhl-label-form .wc_dhl_error' ).remove();

			$( '#shipment-dhl-label-form' ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			} );

			var data = {
				action:                   'wc_shipment_dhl_delete_label',
				order_id:                 woocommerce_admin_meta_boxes.post_id,
				pr_dhl_label_nonce:       $( '#pr_dhl_label_nonce' ).val()
			};

			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
				$( '#shipment-dhl-label-form' ).unblock();
				if ( response.error ) {
					$( '#shipment-dhl-label-form' ).append('<p class="wc_dhl_error">Error: ' + response.error + '</p>');
				} else {

					$( '#shipment-dhl-label-form .wc_dhl_delete' ).remove();
					// Enable all form items
					$(function(){
						$('#shipment-dhl-label-form').each(function(i, div) {

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

						//Check PDDP service
						wc_shipment_dhl_label_items.enable_disable_paket_international_fields();
						wc_shipment_dhl_label_items.enable_disable_duties();
					});

					$( '#dhl-label-print').remove();
					$( '#shipment-dhl-label-form' ).append(dhl_label_data.main_button);

					if( response.dhl_tracking_num ) {
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

					$( document ).trigger( 'pr_dhl_deleted_label' );
				}
			});

			return false;
		},

		enable_disable_paket_international_fields: function () {
			var selected_product = $( '#pr_dhl_product' ).val();

			if( 'V53WPAK' === selected_product ) {
				$('#pr_dhl_PDDP').removeAttr('disabled');
				$('#pr_dhl_endorsement').removeAttr('disabled');
			} else {
				$('#pr_dhl_PDDP').prop('disabled', 'disabled');
				$('#pr_dhl_PDDP').prop( "checked", false ).change();

				$('#pr_dhl_endorsement').prop('disabled', 'disabled').change();

				$('#pr_dhl_duties').removeAttr('disabled');
			}
		},

		enable_disable_duties: function () {
			// Only relevant for international so check if exists
			if ( ! $( '#pr_dhl_PDDP' ).length ) {
				return;
			}

			var is_checked = $( '#pr_dhl_PDDP' ).prop('checked');

			if ( is_checked ) {
				$('#pr_dhl_duties').prop('disabled', 'disabled');
			} else {
				$('#pr_dhl_duties').removeAttr('disabled');
			}
		},

		enable_disable_signature_service: function () {
			// Only relevant for international so check if exists
			if ( ! $( '#pr_dhl_signature_service' ).length ) {
				return;
			}

			var selected_product = $( '#pr_dhl_product' ).val();

			if ( 'V01PAK' !== selected_product ) {
				$('#pr_dhl_signature_service').prop( 'checked', false ).change();
				$('#pr_dhl_signature_service').prop('disabled', 'disabled');
			} else {
				$('#pr_dhl_signature_service').removeAttr('disabled');
			}
		},
		enable_disable_mrn: function () {
			const mrn_field = $( '#pr_dhl_mrn' );
			const selected_product = $( '#pr_dhl_product' ).val();
			const shipping_country = $( '#_shipping_country' ).val().toUpperCase();
			const is_mrn_required = 'V54EPAK' === selected_product || (
				'CH' === shipping_country && 'V53WPAK' === selected_product
			);

			if ( is_mrn_required ) {
				mrn_field.removeAttr( 'disabled' );
			} else {
				mrn_field.val( '' )
				mrn_field.prop( 'disabled', 'disabled' );
			}
		},
	};

	wc_shipment_dhl_label_items.init();

} );
