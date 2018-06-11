<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class PR_DHL_API_Model_SOAP_WSSE_Label extends PR_DHL_API_SOAP_WSSE implements PR_DHL_API_Label {

	private $args = array();

	// 'LI', 'CH', 'NO'
	protected $eu_iso2 = array( 'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'RO', 'SI', 'SK', 'ES', 'SE', 'GB');

	public function __construct( ) {
		try {

			parent::__construct( );

		} catch (Exception $e) {
			throw $e;
		}
	}

/*
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
	}*/

	public function get_dhl_label( $args ) {
		error_log('get_dhl_label');
		error_log(print_r($args,true));
		$this->set_arguments( $args );
		$soap_request = $this->set_message();

		try {
			$soap_client = $this->get_access_token( $args['dhl_settings']['api_user'], $args['dhl_settings']['api_pwd'] );
			PR_DHL()->log_msg( '"createShipmentOrder" called with: ' . print_r( $soap_request, true ) );

			$response_body = $soap_client->createShipmentRequest($soap_request);
			// error_log(print_r($soap_client->__getLastRequest(),true));
			error_log(print_r($response_body,true));

			PR_DHL()->log_msg( 'Response Body: ' . print_r( $response_body, true ) );
		
			if( ! empty( $response_body->Provider->Notification->code ) ) {
				throw new Exception( $response_body->Provider->Notification->code . ' - ' . $response_body->Provider->Notification->Message );
			}

			return $this->get_returned_rates( $response_body->Provider->Service );

		} catch (Exception $e) {
			// error_log('get dhl label Exception');
			// error_log(print_r($soap_client->__getLastRequest(),true));
			throw $e;
		}
	}

	protected function get_returned_rates( $returned_rates ) {
		$new_returned_rates = array();
		foreach ($returned_rates as $key => $value) {
			$new_returned_rates[ $value->type ]['name'] = $value->Charges->Charge[0]->ChargeType;
			$new_returned_rates[ $value->type ]['amount'] = $value->TotalNet->Amount;
			$new_returned_rates[ $value->type ]['delivery_time'] = $value->DeliveryTime;
		}

		return $new_returned_rates;
	}

	public function delete_dhl_label_call( $args ) {
		$soap_request =	array(
					'Version' =>
						array(
								'majorRelease' => '2',
								'minorRelease' => '2'
						),
					'DeleteRequest' => 
						array(
							'PickupDate' => date('Y-m-d'), // use for testing, but needs to be REQUESTED SHIP DATE!
							'PickupCountry' => 'US',
							'DispatchConfirmationNumber' => '1111',
							'RequestorName' => 'Shadi'
						)
				);

		try {

			$soap_client = $this->get_access_token( $args['api_user'], $args['api_pwd'] );
			error_log(print_r($soap_request,true));
			$response_body = $soap_client->deleteShipmentRequest( $soap_request );

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

		// Then delete file
		$upload_path = wp_upload_dir();
		$label_path = str_replace( $upload_path['url'], $upload_path['path'], $args['label_url'] );
		
		if( file_exists( $label_path ) ) {
			$res = unlink( $label_path );
			
			if( ! $res ) {
				throw new Exception( __('DHL Label could not be deleted!', 'pr-shipping-dhl' ) );
			}
		}

	}

	protected function save_label_file( $order_id, $format, $label_data ) {
		$label_name = 'dhl-label-' . $order_id . '.' . $format;
		$upload_path = wp_upload_dir();
		$label_path = $upload_path['path'] . '/'. $label_name;
		$label_url = $upload_path['url'] . '/'. $label_name;

		if( validate_file($label_path) > 0 ) {
			throw new Exception( __('Invalid file path!', 'pr-shipping-dhl' ) );
		}

		// $label_data_decoded = base64_decode($label_data);
		$label_data_decoded = file_get_contents( $label_data );

		$file_ret = file_put_contents( $label_path, $label_data_decoded );
		
		if( empty( $file_ret ) ) {
			throw new Exception( __('DHL Label file cannot be saved!', 'pr-shipping-dhl' ) );
		}

		return $label_url;
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


		if ( empty( $args['order_details']['weightUom'] )) {
			throw new Exception( __('Shop "Weight Units of Measure" is empty!', 'pr-shipping-dhl') );
		}

		if ( empty( $args['order_details']['weight'] )) {
			throw new Exception( __('Order "Weight" is empty!', 'pr-shipping-dhl') );
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

		// Validate shipping address
		if ( empty( $args['shipping_address']['address_1'] )) {
			throw new Exception( __('Shipping "Address 1" is empty!', 'pr-shipping-dhl') );
		}

		if ( empty( $args['shipping_address']['city'] )) {
			throw new Exception( __('Shipping "City" is empty!', 'pr-shipping-dhl') );
		}

		if ( empty( $args['shipping_address']['country'] )) {
			throw new Exception( __('Shipping "Country" is empty!', 'pr-shipping-dhl') );
		}

		// If address 2 missing, set last piece of an address to be address 2
		if ( empty( $args['shipping_address']['address_2'] )) {
			// Break address into pieces			
			$address_exploded = explode(' ', $args['shipping_address']['address_1']);
			// Get last piece, assuming it is street number 
			$last_index = sizeof($address_exploded);

			// Set last index as street number
			$args['shipping_address']['address_2'] = $address_exploded[ $last_index - 1 ];

			// Unset it in address 1
			unset( $address_exploded[ $last_index - 1 ] );

			// Set address 1 without street number
			$args['shipping_address']['address_1'] = implode(' ', $address_exploded );
		}

		// Add default values for required fields that might not be passed e.g. phone
		$default_args = array( 'shipping_address' => 
									array( 'name' => '',
											'company' => '',
											'address_2' => '',
											'email' => '',
											// 'idNumber' => '',
											// 'idType' => '',
											'postcode' => '',
											'state' => '',
											'phone' => ' '
											),
						);

		$args['shipping_address'] = wp_parse_args( $args['shipping_address'], $default_args['shipping_address'] );
		// $args['order_details'] = wp_parse_args( $args['order_details'], $default_args['order_details'] );

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

	protected function set_query_string() {
		$dhl_label_query_string = 
			array( 'format' => self::DHL_LABEL_FORMAT,
					'labelSize' => self::DHL_LABEL_SIZE,
					'pageSize' => self::DHL_PAGE_SIZE,
					'layout' => self::DHL_LAYOUT,
					'autoClose' => self::DHL_AUTO_CLOSE );
		
		$this->query_string = http_build_query($dhl_label_query_string);
	}

	protected function is_european_shipment() {
		
		// if ( ! empty( $this->args['dhl_settings'][ 'shipper_country' ] ) && ! empty( $this->args['shipping_address']['country'] ) && ( $this->args['dhl_settings'][ 'shipper_country' ] == $this->args['shipping_address']['country'] ) ) {
		if ( ! empty( $this->args['shipping_address']['country'] ) && in_array( $this->args['shipping_address']['country'], $this->eu_iso2 ) ) {
			return true;
		} else {
			return false;
		}
	}
	

	protected function set_message() {
		if( ! empty( $this->args ) ) {
			// Set date related functions to German time

			// $this->args['order_details']['weight'] = $this->maybe_convert_weight( $this->args['order_details']['weight'], $this->args['order_details']['weightUom'] );

			$dhl_label_body = 
				array(
					'Version' =>
						array(
								'majorRelease' => '2',
								'minorRelease' => '2'
						),
					'RequestedShipment' => 
						array (
							'ShipmentInfo' => 
								array (
										'DropOffType' => 'REGULAR_PICKUP',
										'ServiceType' => $this->args['order_details']['dhl_product'],
										'Account' => $this->args['dhl_settings']['account_num'],
										'Currency' => $this->args['order_details']['currency'],
										'UnitOfMeasurement' => 'SI',
										// 'Content' => 'NON_DOCUMENTS',
										'SpecialServices' => 
											array(
												'Service' =>
													array(
														'ServiceType' => 'II',
														'ServiceValue' => $this->args['order_details']['total_value'],
														'CurrencyCode' => $this->args['order_details']['currency'],
													),
											),
								),
							'ShipTimestamp' => date('Y-m-d\TH:i:s\G\M\TP', time() + 60*60*24), // 2018-03-05T15:33:16GMT+01:00
							'PaymentInfo' => 'DDP',
							'Ship' => 
								array( 
									'Shipper' =>
										array(
											'Contact' =>
												array(
														'PersonName' => 'Shadi Manna', //$this->args['dhl_settings']['shipper_name'],
														'CompanyName' => 'Progressus.io', //$this->args['dhl_settings']['shipper_company'],
														'PhoneNumber' => '34666666666', //$this->args['dhl_settings']['shipper_phone'],
														'EmailAddress' => $this->args['dhl_settings']['shipper_email'],
													),
											'Address' =>
												array(
														'StreetLines' => $this->args['dhl_settings']['shipper_address'],
														'StreetLines2' => $this->args['dhl_settings']['shipper_address2'],
														'City' => $this->args['dhl_settings']['shipper_address_city'],
														'StateOrProvinceCode' => $this->args['dhl_settings']['shipper_address_state'],
														'PostalCode' => $this->args['dhl_settings']['shipper_address_zip'],
														'CountryCode' => $this->args['dhl_settings']['shipper_country']
													),
											),
									'Recipient' =>
										array(
											'Contact' =>
												array(
														'PersonName' => $this->args['shipping_address']['name'],
														'CompanyName' => $this->args['shipping_address']['company'],
														'PhoneNumber' => $this->args['shipping_address']['phone'],
														'EmailAddress' => $this->args['shipping_address']['email'],
													),
											'Address' =>
												array(
														'StreetLines' => $this->args['shipping_address']['address_1'],
														'StreetLines2' => $this->args['shipping_address']['address_2'],
														'City' => $this->args['shipping_address']['city'],
														'StateOrProvinceCode' => $this->args['shipping_address']['state'],
														'PostalCode' => $this->args['shipping_address']['postcode'],
														'CountryCode' => $this->args['shipping_address']['country']
												),
										),						
								),
							'Packages' => 
								array(
									'RequestedPackages' =>
										array(
											'number' => 1,
											'Weight' => 1, // CONVERT TO KG ALWAYS!
											'Dimensions' =>
												array(
													'Length' =>  1, // CONVERT ALL TO CM ALWAYS!
													'Width' =>  1,
													'Height' =>  1,
												),
											'CustomerReferences' => 'IDK',
										),
								),
						),
					);

			// If shipper or receiver are outside of EU then add "cross-border" info 
			if( PR_DHL_WC()->is_crossborder_shipment( $this->args['shipping_address']['country'] ) ) {

				$item_description = '';
				foreach ($this->args['items'] as $key => $item) {
					$item_description .= ! empty( $item_description ) ? ', ' : '';
					$item_description .= $item['item_description'];
				}

				$item_description = substr( $item_description, 0, 255 );
				$number_pieces = sizeof($this->args['items']);

				$dhl_label_body['RequestedShipment']['InternationalDetail']['Commodities'] = 
					array(
						'NumberOfPieces' => $number_pieces,
						'Description' => $item_description,
						'CustomsValue' => $this->args['order_details']['total_value'],
					);
			}

			error_log(print_r($dhl_label_body,true));
			// Unset/remove any items that are empty strings or 0, even if required!
			$this->body_request = $this->walk_recursive_remove( $dhl_label_body );

			return $this->body_request;
		}
		
	}	
}
