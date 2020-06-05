<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class PR_DHL_API_SOAP_Label extends PR_DHL_API_SOAP implements PR_DHL_API_Label {

	/**
	 * WSDL definitions
	 */
	const PR_DHL_WSDL_LINK = 'https://cig.dhl.de/cig-wsdls/com/dpdhl/wsdl/geschaeftskundenversand-api/3.0/geschaeftskundenversand-api-3.0.wsdl';
//	const PR_DHL_WSDL_LINK = 'https://cig.dhl.de/cig-wsdls/com/dpdhl/wsdl/geschaeftskundenversand-api/2.2/geschaeftskundenversand-api-2.2.wsdl';

	const DHL_MAX_ITEMS = '6';
	const DHL_RETURN_PRODUCT = '07';

	private $pos_ps = false;
	private $pos_rs = false;
	private $pos_po = false;

	public function __construct( ) {
		try {

			parent::__construct( self::PR_DHL_WSDL_LINK );

		} catch (Exception $e) {
			throw $e;
		}
	}

	public function dhl_test_connection( $client_id, $client_secret ) {
		return $this->get_access_token( $client_id, $client_secret );
	}

	public function dhl_validate_field( $key, $value ) {
		$this->validate_field( $key, $value );
	}

	protected function validate_field( $key, $value ) {

		try {

			switch ( $key ) {
				case 'weight':
					$this->validate( $value );
					break;
				case 'hs_code':
					$this->validate( $value, 'string', 4, 11 );
					break;
				default:
					parent::validate_field( $key, $value );
					break;
			}
			
		} catch (Exception $e) {
			throw $e;
		}
	}

	public function get_dhl_label( $args ) {
		$this->set_arguments( $args );
		$soap_request = $this->set_message();

		try {
			$soap_client = $this->get_access_token( $args['dhl_settings']['api_user'], $args['dhl_settings']['api_pwd'] );
			PR_DHL()->log_msg( '"createShipmentOrder" called with: ' . print_r( $soap_request, true ) );

			$response_body = $soap_client->createShipmentOrder($soap_request);

			PR_DHL()->log_msg( 'Response: Successful');

		} catch (Exception $e) {
			PR_DHL()->log_msg( 'Response Error: ' . $e->getMessage() );
			throw $e;
		}

		if( $response_body->Status->statusCode != 0 ) {
		    if( isset( $response_body->Status->statusMessage ) ) {
                $status_message = $response_body->Status->statusMessage;
            } elseif( isset( $response_body->CreationState->LabelData->Status->statusMessage[0] ) ) {
                $status_message = $response_body->CreationState->LabelData->Status->statusMessage[0];
            } elseif (isset( $response_body->Status->statusText )) {
            	$status_message = $response_body->Status->statusText;
            } else {
            	$status_message = __('Contact Support', 'pr-shipping-dhl');
            }

		    throw new Exception( sprintf( __('Could not create label - %s', 'pr-shipping-dhl'), $status_message ) );
		} else {
			// Give the server 1 second to create the PDF before downloading it
			sleep(1);

			$export_data = '';
			if ( isset( $response_body->CreationState->LabelData->exportLabelData) ) {
				$export_data = $response_body->CreationState->LabelData->exportLabelData;
			}

			$label_tracking_info = $this->save_data_files( $response_body->CreationState->sequenceNumber, $response_body->CreationState->LabelData->labelData, $export_data );

			$tracking_number = isset( $response_body->CreationState->shipmentNumber ) ? $response_body->CreationState->shipmentNumber : '';
			$label_tracking_info['tracking_number'] = $tracking_number;

			return $label_tracking_info;
		}
	}

	public function delete_dhl_label_call( $args ) {
		$soap_request =	array(
					'Version' =>
						array(
								'majorRelease' => '3',
								'minorRelease' => '0'
						),
					'shipmentNumber' => $args['tracking_number']
				);

		try {

			PR_DHL()->log_msg( '"deleteShipmentOrder" called with: ' . print_r( $soap_request, true ) );

			$soap_client = $this->get_access_token( $args['api_user'], $args['api_pwd'] );
			$response_body = $soap_client->deleteShipmentOrder( $soap_request );

			PR_DHL()->log_msg( 'Response Body: ' . print_r( $response_body, true ) );
		} catch (Exception $e) {
			throw $e;
		}

		if( $response_body->Status->statusCode != 0 ) {
			throw new Exception( sprintf( __('Could not delete label - %s', 'pr-shipping-dhl'), $response_body->Status->statusMessage ) );
		} 
	}

	public function delete_dhl_label( $args ) {
		// Delete the label remotely first
		try {
			$this->delete_dhl_label_call( $args );
		} catch (Exception $e) {
			throw $e;			
		}

		// Check if path exists (new way)...
		if ( isset($args['label_path'])) {
			$label_path = $args['label_path'];
		} elseif ( isset($args['label_url']) ) { //...otherwise check for URL and create path
			$upload_path = wp_upload_dir();
			$label_path = str_replace( $upload_path['url'], $upload_path['path'], $args['label_url'] );
		} else {
			return;
		}
		
		// Then delete file 
		if( file_exists( $label_path ) ) {
			$res = unlink( $label_path );
			
			if( ! $res ) {
				throw new Exception( __('DHL Label could not be deleted!', 'pr-shipping-dhl' ) );
			}
		}
	}

	protected function save_data_files( $order_id, $label_data, $export_data ) {

		$label_info = $this->save_data_file( 'label', $order_id, $label_data );

		if ( ! empty( $export_data ) ) {
			$export_info = $this->save_data_file( 'export', $order_id, $export_data );

			// Merge PDF files
			$loader = PR_DHL_Libraryloader::instance();
			$pdfMerger = $loader->get_pdf_merger();

			if( $pdfMerger ){

				$pdfMerger->addPDF( $label_info['data_path'], 'all' );
				$pdfMerger->addPDF( $export_info['data_path'], 'all' );

				$filename = 'dhl-label-export-' . $order_id . '.pdf';
				$label_url = PR_DHL()->get_dhl_label_folder_url() . $filename;
				$label_path = PR_DHL()->get_dhl_label_folder_dir() . $filename;
				$pdfMerger->merge( 'file',  $label_path );
			} else {
				$label_url = $label_info['data_url'];
				$label_path = $label_info['data_path'];
			}

		} else {
			$label_url = $label_info['data_url'];
			$label_path = $label_info['data_path'];
		}
		
		return array( 'label_url' => $label_url, 'label_path' => $label_path);
	}

	protected function save_data_file( $prefix, $order_id, $label_data ) {
		$data_name = 'dhl-' . $prefix . '-' . $order_id . '.pdf';
		$data_path = PR_DHL()->get_dhl_label_folder_dir() . $data_name;
		$data_url = PR_DHL()->get_dhl_label_folder_url() . $data_name;

		if( validate_file($data_path) > 0 ) {
			throw new Exception( __('Invalid file path!', 'pr-shipping-dhl' ) );
		}

        $label_data_decoded = base64_decode($label_data);
		$file_ret = file_put_contents( $data_path, $label_data_decoded );
		
		if( empty( $file_ret ) ) {
			throw new Exception( __('File cannot be saved!', 'pr-shipping-dhl' ) );
		}

		return array( 'data_url' => $data_url, 'data_path' => $data_path);
	}

	protected function set_arguments( $args ) {
		// Validate set args
		
		if ( empty( $args['dhl_settings']['api_user'] ) ) {
			throw new Exception( __('Please, provide the username in the DHL shipping settings', 'pr-shipping-dhl' ) );
		}

		if ( empty( $args['dhl_settings']['api_pwd'] )) {
			throw new Exception( __('Please, provide the password for the username in the DHL shipping settings', 'pr-shipping-dhl') );
		}

		// Validate order details
		if ( empty( $args['dhl_settings']['account_num'] ) ) {
			throw new Exception( __('Please, provide an account in the DHL shipping settings', 'pr-shipping-dhl' ) );
		}

		if ( empty( $args['dhl_settings']['participation'] )) {
			throw new Exception( __('Please, provide a participation number for the shipping method in the DHL shipping settings', 'pr-shipping-dhl') );
		}

		if ( empty( $args['dhl_settings']['shipper_name'] )) {
			throw new Exception( __('Please, provide a shipper name in the DHL shipping settings', 'pr-shipping-dhl') );
		}

		if ( empty( $args['dhl_settings']['shipper_address'] )) {
			throw new Exception( __('Please, provide a shipper address in the DHL shipping settings', 'pr-shipping-dhl') );
		}

		if ( empty( $args['dhl_settings']['shipper_address_no'] )) {
			throw new Exception( __('Please, provide a shipper address number in the DHL shipping settings', 'pr-shipping-dhl') );
		}

		if ( empty( $args['dhl_settings']['shipper_address_city'] )) {
			throw new Exception( __('Please, provide a shipper city in the DHL shipping settings', 'pr-shipping-dhl') );
		}

		if ( empty( $args['dhl_settings']['shipper_address_zip'] )) {
			throw new Exception( __('Please, provide a shipper postcode in the DHL shipping settings', 'pr-shipping-dhl') );
		}

		// Order details
		if ( empty( $args['order_details']['dhl_product'] )) {
			throw new Exception( __('DHL "Product" is empty!', 'pr-shipping-dhl') );
		}

		// return receiver
		if ( isset( $args['order_details']['return_address'] ) && ( $args['order_details']['return_address'] == 'yes' ) ) {

			if ( ( $args['order_details']['dhl_product'] != 'V01PAK' ) && ( $args['order_details']['dhl_product'] != 'V01PRIO' ) && ( $args['order_details']['dhl_product'] != 'V86PARCEL' ) && ( $args['order_details']['dhl_product'] != 'V55PAK' ) ){
				
				throw new Exception( __('Returns are not supported by this DHL Service.', 'pr-shipping-dhl') );
			}
			
			if ( empty( $args['dhl_settings']['return_name'] )) {
				throw new Exception( __('Please, provide a return name in the DHL shipping settings', 'pr-shipping-dhl') );
			}

			if ( empty( $args['dhl_settings']['return_address'] )) {
				throw new Exception( __('Please, provide a return address in the DHL shipping settings', 'pr-shipping-dhl') );
			}

			if ( empty( $args['dhl_settings']['return_address_no'] )) {
				throw new Exception( __('Please, provide a return address number in the DHL shipping settings', 'pr-shipping-dhl') );
			}

			if ( empty( $args['dhl_settings']['return_address_city'] )) {
				throw new Exception( __('Please, provide a return city in the DHL shipping settings', 'pr-shipping-dhl') );
			}

			if ( empty( $args['dhl_settings']['return_address_zip'] )) {
				throw new Exception( __('Please, provide a return postcode in the DHL shipping settings', 'pr-shipping-dhl') );
			}	
		}

		if ( empty( $args['order_details']['order_id'] )) {
			throw new Exception( __('Shop "Order ID" is empty!', 'pr-shipping-dhl') );
		}

		if ( empty( $args['order_details']['weightUom'] )) {
			throw new Exception( __('Shop "Weight Units of Measure" is empty!', 'pr-shipping-dhl') );
		}

		if ( empty( $args['order_details']['weight'] )) {
			throw new Exception( __('Order "Weight" is empty!', 'pr-shipping-dhl') );
		}

		if ( isset( $args['order_details']['identcheck'] ) && ( $args['order_details']['identcheck'] == 'yes' ) ) {
			if ( empty( $args['shipping_address']['first_name'] ) || empty( $args['shipping_address']['last_name'] ) ) {
				throw new Exception( __('First name and last name must be passed for "Identity Check".', 'pr-shipping-dhl') );
			}

			if ( empty( $args['order_details']['identcheck_dob'] ) && empty( $args['order_details']['identcheck_age'] ) ) {
				throw new Exception( __('Either a "Date of Birth" or "Minimum Age" must be eneted for "Ident-Check".', 'pr-shipping-dhl') );
			}
		}

		// Validate weight
		try {
			$this->validate_field( 'weight', $args['order_details']['weight'] );
		} catch (Exception $e) {
			throw new Exception( 'Weight - ' . $e->getMessage() );
		}

		// if ( empty( $args['order_details']['duties'] )) {
		// 	throw new Exception( __('DHL "Duties" is empty!', 'pr-shipping-dhl') );
		// }

		if ( empty( $args['order_details']['currency'] )) {
			throw new Exception( __('Shop "Currency" is empty!', 'pr-shipping-dhl') );
		}

		if ( empty( $args['shipping_address']['city'] )) {
			throw new Exception( __('Shipping "City" is empty!', 'pr-shipping-dhl') );
		}

		if ( empty( $args['shipping_address']['country'] )) {
			throw new Exception( __('Shipping "Country" is empty!', 'pr-shipping-dhl') );
		}
		
		// Validate shipping address
		if ( empty( $args['shipping_address']['address_1'] )) {
			throw new Exception( __('Shipping "Address 1" is empty!', 'pr-shipping-dhl') );
		}


		$this->pos_ps = PR_DHL()->is_packstation( $args['shipping_address']['address_1'] );
		$this->pos_rs = PR_DHL()->is_parcelshop( $args['shipping_address']['address_1'] );
		$this->pos_po = PR_DHL()->is_post_office( $args['shipping_address']['address_1'] );

		// If Packstation, post number is mandatory
		if ( $this->pos_ps && empty( $args['shipping_address']['dhl_postnum'] ) ) {
			throw new Exception( __('Post Number is missing, it is mandatory for "Packstation" delivery.', 'pr-shipping-dhl') );
		}
		
		// Check address 2 if no parcel shop is being selected
		if ( ! $this->pos_ps && ! $this->pos_rs && ! $this->pos_po ) {
			// If address 2 missing, set last piece of an address to be address 2
			if ( empty( $args['shipping_address']['address_2'] )) {
			    $set_key = false;
				// Break address into pieces by spaces
				$address_exploded = explode(' ', $args['shipping_address']['address_1']);

				// If no spaces found
                if( count($address_exploded) == 1 ) {
                    // Break address into pieces by '.'
                    $address_exploded = explode('.', $args['shipping_address']['address_1']);

                    if( count($address_exploded) == 1 ) {
                        throw new Exception(__('Shipping "Address 2" is empty!', 'pr-shipping-dhl'));
                    }
                }

				// Loop through address and set number value only...
				// ...last found number will be 'address_2'
				foreach ($address_exploded as $address_key => $address_value) {
					if (is_numeric($address_value)) {
						// Set last index as street number
						$set_key = $address_key;
					}
				}

				// If no number was found, then take last part of address no matter what it is
				if( $set_key === false ) {
					$set_key = $address_key;
				}

				// Set "address_2" first
				$args['shipping_address']['address_2'] = implode( ' ', array_slice( $address_exploded, $set_key ) );
				// Remove "address_2" from "address_1"
				$args['shipping_address']['address_1'] = implode( ' ', array_slice( $address_exploded, 0 , $set_key ) );

			}
		}

		// Add default values for required fields that might not be passed e.g. phone
		$default_args = array( 'shipping_address' => 
									array( 'name' => '',
											'company' => '',
											'address_2' => '',
											'email' => '',
											'postcode' => '',
											'state' => '',
											'phone' => ' '
											),
						);

		$args['shipping_address'] = wp_parse_args( $args['shipping_address'], $default_args['shipping_address'] );

		$default_args_item = array( 
									'item_description' => '',
									'sku' => '',
									'line_total' => 0,
									'country_origin' => '',
									'hs_code' => '',
									'qty' => 1
									);

		foreach ($args['items'] as $key => $item) {
			
			if ( ! empty( $item['hs_code'] ) ) {
				try {
					$this->validate_field( 'hs_code', $item['hs_code'] );
				} catch (Exception $e) {
					throw new Exception( 'HS Code - ' . $e->getMessage() );
				}
			}

			$args['items'][$key] = wp_parse_args( $item, $default_args_item );			
		}

		$this->args = $args;
	}

	protected function set_message() {

		if( ! empty( $this->args ) ) {
			// Set date related functions to German time
			// date_default_timezone_set('Europe/Berlin');

			// SERVICES DATA
			$services_map = array(
								'age_visual' => array(
													'name' => 'VisualCheckOfAge',
													'type' => 'type'),
								'preferred_location' => array(
													'name' => 'PreferredLocation' ,
													'type' => 'details'),
								'preferred_neighbor' => array(
													'name' => 'PreferredNeighbour' ,
													'type' => 'details'),
								'preferred_day' => array(
													'name' => 'PreferredDay' ,
													'type' => 'details'),
								'personally' => array(
													'name' => 'Personally'
													),
								'no_neighbor' => array(
													'name' => 'NoNeighbourDelivery'
													),
								'named_person' => array(
													'name' => 'NamedPersonOnly' ,
													),
								'premium' => array(
													'name' => 'Premium'
													),
								'additional_insurance' => array(
													'name' => 'AdditionalInsurance' 
													),
								'bulky_goods' => array(
													'name' => 'BulkyGoods'
													),
								'identcheck' => array(
													'name' => 'IdentCheck'
													),
                                'routing'   => array(
                                                    'name' => 'ParcelOutletRouting'
                                )
								);

			$services = array();
			foreach ($services_map as $key => $value) {

				if ( ! empty( $this->args['order_details'][ $key ] ) ) {

				    // If checkbox not checked
					if ( empty( $this->args['order_details'][ $key ] ) || ($this->args['order_details'][ $key ] == 'no' ) ) {
						continue;
					}

					// If a checkbox is checked, check specific structure
					if ( $this->args['order_details'][ $key ] == 'yes' ) {

						$services[ $value['name'] ] = array(
							'active' => 1
						);

						switch ( $key ) {
							case 'additional_insurance':
								$services[ $value['name'] ]['insuranceAmount'] = $this->args['order_details']['total_value'];
								break;	
							case 'identcheck':
								$services[ $value['name'] ]['Ident']['surname'] = isset( $this->args['shipping_address']['first_name'] ) ? $this->args['shipping_address']['first_name'] : '';
								$services[ $value['name'] ]['Ident']['givenName'] = isset( $this->args['shipping_address']['last_name'] ) ? $this->args['shipping_address']['last_name'] : '';
								$services[ $value['name'] ]['Ident']['dateOfBirth'] = isset( $this->args['order_details']['identcheck_dob'] ) ? $this->args['order_details']['identcheck_dob'] : '';
								$services[ $value['name'] ]['Ident']['minimumAge'] = isset( $this->args['order_details']['identcheck_age'] ) ? $this->args['order_details']['identcheck_age'] : '';
								break;
							case 'routing':
								$services[ $value['name'] ]['details'] = isset( $this->args['order_details']['routing_email'] ) ? $this->args['order_details']['routing_email'] : '';
								break;
						}

					} else {
						$services[ $value['name'] ] = array(
							'active' => 1,
							$value['type'] => $this->args['order_details'][ $key ]
						);
					}
				}				
			}

			// EMAIL NOTIFCATION
			$notification_email = array();
			if ( isset( $this->args['order_details'][ 'email_notification' ] ) && ( $this->args['order_details'][ 'email_notification' ] == 'yes' || $this->args['order_details'][ 'email_notification' ] == '1' ) ) {
				$notification_email['recipientEmailAddress'] = $this->args['shipping_address']['email'];
			}

			// COD DATA
			$bank_data = array();

			if( ! empty( $this->args['order_details']['cod_value'] ) ) {

				$services[ 'CashOnDelivery' ] = array(
							'active' => 1
						);

				// If the fee was added to the customer i.e. 'cod_fee' == 'yes', then do not add to merchange i.e. 'addFee' = 0
				// if( isset( $this->args['dhl_settings']['cod_fee'] ) && $this->args['dhl_settings']['cod_fee'] == 'yes' ) {
				// 	$services['CashOnDelivery']['addFee'] = 0;
				// } else {
				// 	$services['CashOnDelivery']['addFee'] = 1;
				// }

				$services[ 'CashOnDelivery']['codAmount'] = $this->args['order_details']['cod_value']; 	

				$bank_data_map = array(
									'bank_holder' => 'accountOwner',
									'bank_name' => 'bankName',
									'bank_iban' => 'iban',
									'bank_ref' => 'note1',
									'bank_ref_2' => 'note2',
									'bank_bic' => 'bic'
									);

				foreach ($bank_data_map as $key => $value) {
					
					if( isset( $this->args['dhl_settings'][ $key ] ) ) {
						$bank_data[ $value ] = $this->args['dhl_settings'][ $key ];
					}
				}

				// $bank_data['note1'] = $this->args['order_details']['order_id'];
				// $bank_data['note2'] = $this->args['shipping_address']['email'];
			}

			// create account number
			$product_number = preg_match('!\d+!', $this->args['order_details']['dhl_product'], $matches );

			if( $product_number ) {
				$account_number = $this->args['dhl_settings']['account_num'] . $matches[0] . $this->args['dhl_settings']['participation'];
			} else {
				throw new Exception( __('Could not create account number - no product number.', 'pr-shipping-dhl') );				
			}

			$this->args['order_details']['weight'] = $this->maybe_convert_weight( $this->args['order_details']['weight'], $this->args['order_details']['weightUom'] );

			// Ensure company name is first for shipper address
			if( ! empty( $this->args['dhl_settings']['shipper_company'] ) ) {
				$shipper_name1 = $this->args['dhl_settings']['shipper_company'];
				$shipper_name2 = $this->args['dhl_settings']['shipper_name'];
			} else {
				$shipper_name1 = $this->args['dhl_settings']['shipper_name'];
				$shipper_name2 = '';
			}

			// Ensure company name is first for receiver address
			if( ! empty( $this->args['shipping_address']['company'] ) ) {
				$receiver_name1 = $this->args['shipping_address']['company'];
				$receiver_name2 = $this->args['shipping_address']['name'];
			} else {
				$receiver_name1 = $this->args['shipping_address']['name'];
				$receiver_name2 = '';
			}

			$dhl_label_body = 
				array(
					'Version' =>
						array(
								'majorRelease' => '3',
								'minorRelease' => '0'
						),
					'ShipmentOrder' => 
						array (
								'sequenceNumber' => $this->args['order_details']['order_id'],
								'Shipment' => 
									array( 
										'ShipmentDetails' => 
											array( 
												'product' => $this->args['order_details']['dhl_product'],
												'accountNumber' => $account_number,
												'accountNumber' => $account_number,
												'shipmentDate' => date('Y-m-d'),
												'ShipmentItem' => 
													array( 
														'weightInKG' => $this->args['order_details']['weight']
														),
												'Service' => $services,
												'Notification' => $notification_email,
												'BankData' => $bank_data,
												'customerReference' => $this->args['order_details']['order_id'],
												'returnShipmentReference' => $this->args['order_details']['order_id']
											),
										'Shipper' =>
											array(
												'Name' =>
													array(
														'name1' => $shipper_name1,
														'name2' => $shipper_name2,
														),
												'Address' =>
													array(
														'streetName' => $this->args['dhl_settings']['shipper_address'],
														'streetNumber' => $this->args['dhl_settings']['shipper_address_no'],
														'zip' => $this->args['dhl_settings']['shipper_address_zip'],
														'city' => $this->args['dhl_settings']['shipper_address_city'],
														'Origin' =>
															array(
																'countryISOCode' => $this->args['dhl_settings'][ 'shipper_country' ],
																'state' => $this->args['dhl_settings']['shipper_address_state'],
															)
														),
												'Communication' =>
													array(
														'phone' => $this->args['dhl_settings']['shipper_phone'],
														'email' => $this->args['dhl_settings']['shipper_email']
														)
											),
										'Receiver' =>
											array(
												'name1' => $receiver_name1,
												'Address' =>
													array(
														'name2' => $receiver_name2,
														'streetName' => $this->args['shipping_address']['address_1'],
														'streetNumber' => $this->args['shipping_address']['address_2'],
														// 'addressAddition' => $this->args['shipping_address']['address_2'],
														'zip' => $this->args['shipping_address']['postcode'],
														'city' => $this->args['shipping_address']['city'],
														'Origin' =>
															array(
																'countryISOCode' => $this->args['shipping_address']['country'],
																'state' => $this->args['shipping_address']['state']
															)
														),
												// 'Packstation' => array(),
												'Communication' =>
													array(
														'phone' => $this->args['shipping_address']['phone'],
														'email' => $this->args['shipping_address']['email']
														)
											)											
									),

						),
						'labelResponseType' => 'B64'
				);


			if ( $this->pos_ps || $this->pos_rs || $this->pos_po ) {

				// Address is NOT needed if using a parcel shop
				unset( $dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['Address'] );

				$parcel_shop = array('zip' => $this->args['shipping_address']['postcode'],
										'city' => $this->args['shipping_address']['city'],
										'Origin' =>
											array(
												'countryISOCode' => $this->args['shipping_address']['country'],
												'state' => $this->args['shipping_address']['state']
											)
										);

				$address_num = filter_var($this->args['shipping_address']['address_1'], FILTER_SANITIZE_NUMBER_INT);

				if ( $this->pos_ps ) {
					$parcel_shop['postNumber'] = $this->args['shipping_address']['dhl_postnum'];
					$parcel_shop['packstationNumber'] = $address_num;

					
					$dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['Packstation'] = $parcel_shop;
				}
				/*
				if ( $this->pos_rs ) {
					$parcel_shop['postNumber'] = $this->args['shipping_address']['dhl_postnum'];
					$parcel_shop['parcelShopNumber'] = $address_num;

					
					$dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['ParcelShop'] = $parcel_shop;
				}*/

				// ONLY POSTAFILIALE HERE?  NO 'PARCELSHOP'?
				if ( $this->pos_rs || $this->pos_po ) {
					
					if( ! empty( $this->args['shipping_address']['dhl_postnum'] ) ) {
						$parcel_shop['postNumber'] = $this->args['shipping_address']['dhl_postnum'];
						// Only post number should be set, so unset email
						unset($dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['Communication']['email']);
					}

					$parcel_shop['postfilialNumber'] = $address_num;
					
					$dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['Postfiliale'] = $parcel_shop;
				}
			}

			if ( isset( $this->args['order_details']['return_address_enabled'] ) && ( $this->args['order_details']['return_address_enabled'] == 'yes' ) ) {

				$dhl_label_body['ShipmentOrder']['Shipment']['ShipmentDetails']['returnShipmentAccountNumber'] = $this->args['dhl_settings']['account_num'] . self::DHL_RETURN_PRODUCT . $this->args['dhl_settings']['participation_return'];

				// Ensure company name is first for return address
				if( ! empty( $this->args['order_details']['return_company'] ) ) {
					$return_name1 = $this->args['order_details']['return_company'];
					$return_name2 = $this->args['order_details']['return_name'];
				} else {
					$return_name1 = $this->args['order_details']['return_name'];
					$return_name2 = '';
				}

				$dhl_label_body['ShipmentOrder']['Shipment']['ReturnReceiver'] = array(
												'Name' =>
													array(
														'name1' => $return_name1,
														'name2' => $return_name2
														),
												'Address' =>
													array(
														'streetName' => $this->args['order_details']['return_address'],
														'streetNumber' => intval($this->args['order_details']['return_address_no']),
														'zip' => $this->args['order_details']['return_address_zip'],
														'city' => $this->args['order_details']['return_address_city'],
														'Origin' =>
															array(
																'countryISOCode' => $this->args['dhl_settings'][ 'return_country' ],
																'state' => $this->args['order_details']['return_address_state'],
															)
														),
												'Communication' =>
													array(
														'phone' => $this->args['order_details']['return_phone'],
														'email' => $this->args['order_details']['return_email']
														)
											);
			}

			// Is codeable set here since it's at a high level in the message
			if ( isset($this->args['order_details']['is_codeable']) && ($this->args['order_details']['is_codeable'] == 'yes') ) {
				$dhl_label_body['ShipmentOrder']['PrintOnlyIfCodeable'] = array( 'active' => 1 );
			}

			// Add customs info
			if( PR_DHL()->is_crossborder_shipment( $this->args['shipping_address']['country'] ) ) {

				if ( sizeof($this->args['items']) > self::DHL_MAX_ITEMS ) {
					throw new Exception( sprintf( __('Only %s ordered items can be processed, your order has %s', 'pr-shipping-dhl'), self::DHL_MAX_ITEMS, sizeof($this->args['items']) ) );
				}
				
				$customsDetails = array();

				$item_description = '';
				foreach ($this->args['items'] as $key => $item) {
					// weightInKG is in KG needs to be changed if 'g' or 'lbs' etc.
					$item['item_weight'] = $this->maybe_convert_weight( $item['item_weight'], $this->args['order_details']['weightUom'] );

					$item_description .= ! empty( $item_description ) ? ', ' : '';
					$item_description .= $item['item_description'];

					$json_item = array(
									'description' => substr( $item['item_description'], 0, 255 ),
									'countryCodeOrigin' => $item['country_origin'],
									'customsTariffNumber' => $item['hs_code'],
									'amount' => intval( $item['qty'] ),
									'netWeightInKG' => round( floatval( $item['item_weight'] ), 2 ),
									'customsValue' => round( floatval( $item['item_value'] ), 2 ),
								);
					// $customsDetails = $json_item;
					array_push($customsDetails, $json_item);
				}

				$item_description = substr( $item_description, 0, 255 );

				$dhl_label_body['ShipmentOrder']['Shipment']['ExportDocument'] = 
					array(
						'invoiceNumber' => $this->args['order_details']['order_id'],
						'exportType' => 'OTHER',
						'exportTypeDescription' => $item_description,
						'termsOfTrade' => $this->args['order_details']['duties'],
						'placeOfCommital' => $this->args['shipping_address']['country'],
						'ExportDocPosition' => $customsDetails
					);
			}

			// Unset/remove any items that are empty strings or 0, even if required!
			$this->body_request = $this->walk_recursive_remove( $dhl_label_body );

			if( !isset( $this->body_request['Version']['minorRelease'] ) ) {
                $this->body_request['Version']['minorRelease'] = 0;
            }

			// Ensure Export Document is set before adding additional fee
			if( isset( $this->body_request['ShipmentOrder']['Shipment']['ExportDocument'] ) ) {
				// Additional fees, required and 0 so place after check
				$additional_fee 	= floatval( $this->args['order_details']['additional_fee'] );
				$shipping_fee 		= floatval( $this->args['order_details']['shipping_fee'] );
				$total_add_fee 		= $additional_fee + $shipping_fee;
				$this->body_request['ShipmentOrder']['Shipment']['ExportDocument']['additionalFee'] = $total_add_fee;
			}
			
			// If "Ident-Check" enabled, then ensure both fields are passed even if empty
			if ( isset( $this->args['order_details']['identcheck'] ) && ( $this->args['order_details']['identcheck'] == 'yes' ) ) {
				
				if( !isset( $this->body_request['ShipmentOrder']['Shipment']['ShipmentDetails']['Service']['IdentCheck']['Ident']['minimumAge'] ) ) {
					
					$this->body_request['ShipmentOrder']['Shipment']['ShipmentDetails']['Service']['IdentCheck']['Ident']['minimumAge'] = '';
				}

				if( !isset( $this->body_request['ShipmentOrder']['Shipment']['ShipmentDetails']['Service']['IdentCheck']['Ident']['dateOfBirth'] ) ) {
					
					$this->body_request['ShipmentOrder']['Shipment']['ShipmentDetails']['Service']['IdentCheck']['Ident']['dateOfBirth'] = '';
				}
			}

			// Ensure 'postNumber' is passed with 'Postfiliale' even if empty
			if( ! isset( $this->body_request['ShipmentOrder']['Shipment']['Receiver']['Postfiliale']['postNumber'] ) ) {
				// Additional fees, required and 0 so place after check
				$this->body_request['ShipmentOrder']['Shipment']['Receiver']['Postfiliale']['postNumber'] = '';
			}
			
			return $this->body_request;
			// $this->body_request = json_encode($dhl_label_body, JSON_PRETTY_PRINT);
		}
		
	}
}
