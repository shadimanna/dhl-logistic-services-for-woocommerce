<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class PR_DHL_API_SOAP_Label extends PR_DHL_API_SOAP implements PR_DHL_API_Label {

	/**
	 * WSDL definitions
	 */
	// const PR_DHL_WSDL_LINK = 'https://cig.dhl.de/cig-wsdls/com/dpdhl/wsdl/geschaeftskundenversand-api/3.1/geschaeftskundenversand-api-3.1.wsdl';

	const PR_DHL_WSDL_LINK = PR_DHL_PLUGIN_DIR_PATH . '/includes/pr-dhl-api/wsdl/3.5.0/geschaeftskundenversand-api-3.5.0.wsdl';

	const DHL_RETURN_PRODUCT = '07';

	private $pos_ps = false;
	private $pos_rs = false;
	private $pos_po = false;

	public function __construct() {
		try {

			parent::__construct( self::PR_DHL_WSDL_LINK );

		} catch ( Exception $e ) {
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
					$this->validate( wc_format_decimal( $value ) );
					break;
				case 'hs_code':
					$this->validate( $value, 'string', 4, 11 );
					break;
				default:
					parent::validate_field( $key, $value );
					break;
			}
		} catch ( Exception $e ) {
			throw $e;
		}
	}

	public function get_dhl_label( $args ) {
		// error_log(print_r($args,true));
		$this->set_arguments( $args );
		$soap_request = $this->set_message();

		try {
			$soap_client = $this->get_access_token( $args['dhl_settings']['api_user'], $args['dhl_settings']['api_pwd'] );
			PR_DHL()->log_msg( '"createShipmentOrder" called with: ' . print_r( $soap_request, true ) );

			$response_body = $soap_client->createShipmentOrder( $soap_request );
			// error_log(print_r($response_body,true));
			// error_log(print_r( $soap_client->__getLastRequest(), true ));
			PR_DHL()->log_msg( 'Response: Successful' );
			// PR_DHL()->log_msg( 'createShipmentOrder response: '. print_r( $response_body, true ));
			return $this->process_label_response( $response_body, $args['order_details']['order_id'] );

		} catch ( Exception $e ) {
			PR_DHL()->log_msg( 'Response Error: ' . $e->getMessage() );
			throw $e;
		}
	}

	protected function process_label_response( $response_body, $order_id ) {

		if ( $response_body->Status->statusCode != 0 ) {
			if ( isset( $response_body->Status->statusMessage ) ) {
				$status_message = $response_body->Status->statusMessage;
			} elseif ( isset( $response_body->CreationState->LabelData->Status->statusMessage[0] ) ) {
				$status_message = $response_body->CreationState->LabelData->Status->statusMessage[0];
			} elseif ( isset( $response_body->Status->statusText ) ) {
				$status_message = $response_body->Status->statusText;
			} else {
				$status_message = esc_html__( 'Contact Support', 'dhl-for-woocommerce' );
			}

			/* translators: %s is the status message describing the error */
			throw new Exception( sprintf( esc_html__( 'Could not create label - %s', 'dhl-for-woocommerce' ), esc_html( $status_message ) ) );
		} else {
			// Give the server 1 second to create the PDF before downloading it
			sleep( 1 );

			if ( isset( $response_body->CreationState ) && is_array( $response_body->CreationState ) ) {

				$multi_label_info    = array();
				$multi_tracking_info = array();

				foreach ( $response_body->CreationState as $creation_key => $creation_state ) {
					$export_data = '';
					if ( isset( $creation_state->LabelData->exportLabelData ) ) {
						$export_data = $creation_state->LabelData->exportLabelData;
					}

					if ( isset( $creation_state->sequenceNumber ) && isset( $creation_state->LabelData->labelData ) ) {
						$multi_label_info[ $creation_key ] = $this->save_data_files( $creation_state->sequenceNumber, $creation_state->LabelData->labelData, $export_data );

						$tracking_number = isset( $creation_state->shipmentNumber ) ? $creation_state->shipmentNumber : '';
						$multi_tracking_info['tracking_number'][ $creation_key ] = $tracking_number;

						$return_label_number = isset( $creation_state->returnShipmentNumber ) ? $creation_state->returnShipmentNumber : '';
						if ( $return_label_number ) {
							$multi_tracking_info['return_label_number'][ $creation_key ] = $return_label_number;
						}
					}
				}

				$label_tracking_info  = $this->save_multiple_files( $multi_label_info, $order_id );
				$label_tracking_info += $multi_tracking_info;

			} else {
				$export_data = '';
				if ( isset( $response_body->CreationState->LabelData->exportLabelData ) ) {
					$export_data = $response_body->CreationState->LabelData->exportLabelData;
				}

				$label_tracking_info = $this->save_data_files( $response_body->CreationState->sequenceNumber, $response_body->CreationState->LabelData->labelData, $export_data );

				$tracking_number                        = isset( $response_body->CreationState->shipmentNumber ) ? $response_body->CreationState->shipmentNumber : '';
				$label_tracking_info['tracking_number'] = $tracking_number;

				$return_label_number = isset( $response_body->CreationState->returnShipmentNumber ) ? $response_body->CreationState->returnShipmentNumber : '';
				if ( $return_label_number ) {
					$label_tracking_info['return_label_number'] = $return_label_number;
				}
			}

			return $label_tracking_info;
		}
	}


	public function delete_dhl_label_call( $args ) {
		$soap_request = array(
			'Version'        =>
				array(
					'majorRelease' => '3',
					'minorRelease' => '1',
				),
			'shipmentNumber' => $args['tracking_number'],
		);

		try {

			PR_DHL()->log_msg( '"deleteShipmentOrder" called with: ' . print_r( $soap_request, true ) );

			$soap_client   = $this->get_access_token( $args['api_user'], $args['api_pwd'] );
			$response_body = $soap_client->deleteShipmentOrder( $soap_request );

			PR_DHL()->log_msg( 'Response Body: ' . print_r( $response_body, true ) );
		} catch ( Exception $e ) {
			throw $e;
		}

		if ( $response_body->Status->statusCode != 0 ) {

			/* translators: %s is the status message describing the error */
			throw new Exception( sprintf( esc_html__( 'Could not delete label - %s', 'dhl-for-woocommerce' ), esc_html( $response_body->Status->statusMessage ) ) );
		}
	}

	public function delete_dhl_label( $args ) {
		// Delete the label remotely first
		try {
			$this->delete_dhl_label_call( $args );
		} catch ( Exception $e ) {
			throw $e;
		}

		// Check if path exists (new way)...
		if ( isset( $args['label_path'] ) ) {
			$label_path = $args['label_path'];
		} elseif ( isset( $args['label_url'] ) ) { // ...otherwise check for URL and create path
			$upload_path = wp_upload_dir();
			$label_path  = str_replace( $upload_path['url'], $upload_path['path'], $args['label_url'] );
		} else {
			return;
		}

		// Then delete file
		if ( file_exists( $label_path ) ) {
			$res = wp_delete_file( $label_path );

			if ( ! $res ) {
				throw new Exception( esc_html__( 'DHL Label could not be deleted!', 'dhl-for-woocommerce' ) );
			}
		}
	}

	protected function save_multiple_files( $multiple_files, $order_id ) {
		if ( is_array( $multiple_files ) ) {

			// Merge PDF files
			$loader    = PR_DHL_Libraryloader::instance();
			$pdfMerger = $loader->get_pdf_merger();

			if ( $pdfMerger ) {

				foreach ( $multiple_files as $key => $value ) {
					$pdfMerger->addPDF( $value['label_path'], 'all' );
				}

				$filename   = 'dhl-multi-label-export-' . $order_id . '.pdf';
				$label_url  = PR_DHL()->get_dhl_label_folder_url() . $filename;
				$label_path = PR_DHL()->get_dhl_label_folder_dir() . $filename;
				$pdfMerger->merge( 'file', $label_path );
			}
		}

		return array(
			'label_url'  => $label_url,
			'label_path' => $label_path,
		);
	}

	protected function save_data_files( $order_id, $label_data, $export_data ) {

		$label_info = $this->save_data_file( 'label', $order_id, $label_data );

		if ( ! empty( $export_data ) ) {
			$export_info = $this->save_data_file( 'export', $order_id, $export_data );

			// Merge PDF files
			$loader    = PR_DHL_Libraryloader::instance();
			$pdfMerger = $loader->get_pdf_merger();

			if ( $pdfMerger ) {

				$pdfMerger->addPDF( $label_info['data_path'], 'all' );
				$pdfMerger->addPDF( $export_info['data_path'], 'all' );

				$filename   = 'dhl-label-export-' . $order_id . '.pdf';
				$label_url  = PR_DHL()->get_dhl_label_folder_url() . $filename;
				$label_path = PR_DHL()->get_dhl_label_folder_dir() . $filename;
				$pdfMerger->merge( 'file', $label_path );
			} else {
				$label_url  = $label_info['data_url'];
				$label_path = $label_info['data_path'];
			}
		} else {
			$label_url  = $label_info['data_url'];
			$label_path = $label_info['data_path'];
		}

		return array(
			'label_url'  => $label_url,
			'label_path' => $label_path,
		);
	}

	protected function save_data_file( $prefix, $order_id, $label_data ) {
		$data_name = 'dhl-' . $prefix . '-' . $order_id . '.pdf';
		$data_path = PR_DHL()->get_dhl_label_folder_dir() . $data_name;
		$data_url  = PR_DHL()->get_dhl_label_folder_url() . $data_name;

		// windows path will not get exception
		if ( validate_file( $data_path ) > 0 && validate_file( $data_path ) !== 2 ) {
			throw new Exception( esc_html__( 'Invalid file path!', 'dhl-for-woocommerce' ) );
		}

		$label_data_decoded = base64_decode( $label_data );
		$file_ret           = file_put_contents( $data_path, $label_data_decoded );

		if ( empty( $file_ret ) ) {
			throw new Exception( esc_html__( 'File cannot be saved!', 'dhl-for-woocommerce' ) );
		}

		return array(
			'data_url'  => $data_url,
			'data_path' => $data_path,
		);
	}

	protected function set_arguments( $args ) {
		// Validate set args

		if ( empty( $args['dhl_settings']['api_user'] ) ) {
			throw new Exception( esc_html__( 'Please, provide the username in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['api_pwd'] ) ) {
			throw new Exception( esc_html__( 'Please, provide the password for the username in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		// Validate order details
		if ( empty( $args['dhl_settings']['account_num'] ) ) {
			throw new Exception( esc_html__( 'Please, provide an account in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['participation'] ) ) {
			throw new Exception( esc_html__( 'Please, provide a participation number for the shipping method in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['shipper_name'] ) ) {
			throw new Exception( esc_html__( 'Please, provide a shipper name in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['shipper_address'] ) ) {
			throw new Exception( esc_html__( 'Please, provide a shipper address in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['shipper_address_no'] ) ) {
			throw new Exception( esc_html__( 'Please, provide a shipper address number in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['shipper_address_city'] ) ) {
			throw new Exception( esc_html__( 'Please, provide a shipper city in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['shipper_address_zip'] ) ) {
			throw new Exception( esc_html__( 'Please, provide a shipper postcode in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( $args['dhl_settings']['add_logo'] == 'yes' && empty( $args['dhl_settings']['shipper_reference'] ) ) {
			throw new Exception( esc_html__( 'Please, provide a shipper reference in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		// Order details
		if ( empty( $args['order_details']['dhl_product'] ) ) {
			throw new Exception( esc_html__( 'DHL "Product" is empty!', 'dhl-for-woocommerce' ) );
		}

		// return receiver
		if ( isset( $args['order_details']['return_address'] ) && ( $args['order_details']['return_address'] == 'yes' ) ) {

			if ( ( $args['order_details']['dhl_product'] != 'V01PAK' ) && ( $args['order_details']['dhl_product'] != 'V01PRIO' ) && ( $args['order_details']['dhl_product'] != 'V86PARCEL' ) && ( $args['order_details']['dhl_product'] != 'V55PAK' ) ) {

				throw new Exception( esc_html__( 'Returns are not supported by this DHL Service.', 'dhl-for-woocommerce' ) );
			}

			if ( empty( $args['dhl_settings']['return_name'] ) ) {
				throw new Exception( esc_html__( 'Please, provide a return name in the DHL shipping settings', 'dhl-for-woocommerce' ) );
			}

			if ( empty( $args['dhl_settings']['return_address'] ) ) {
				throw new Exception( esc_html__( 'Please, provide a return address in the DHL shipping settings', 'dhl-for-woocommerce' ) );
			}

			if ( empty( $args['dhl_settings']['return_address_no'] ) ) {
				throw new Exception( esc_html__( 'Please, provide a return address number in the DHL shipping settings', 'dhl-for-woocommerce' ) );
			}

			if ( empty( $args['dhl_settings']['return_address_city'] ) ) {
				throw new Exception( esc_html__( 'Please, provide a return city in the DHL shipping settings', 'dhl-for-woocommerce' ) );
			}

			if ( empty( $args['dhl_settings']['return_address_zip'] ) ) {
				throw new Exception( esc_html__( 'Please, provide a return postcode in the DHL shipping settings', 'dhl-for-woocommerce' ) );
			}
		}

		if ( empty( $args['order_details']['order_id'] ) ) {
			throw new Exception( esc_html__( 'Shop "Order ID" is empty!', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['order_details']['weightUom'] ) ) {
			throw new Exception( esc_html__( 'Shop "Weight Units of Measure" is empty!', 'dhl-for-woocommerce' ) );
		}

		if ( isset( $args['order_details']['identcheck'] ) && ( $args['order_details']['identcheck'] == 'yes' ) ) {
			if ( empty( $args['shipping_address']['first_name'] ) || empty( $args['shipping_address']['last_name'] ) ) {
				throw new Exception( esc_html__( 'First name and last name must be passed for "Identity Check".', 'dhl-for-woocommerce' ) );
			}

			if ( empty( $args['order_details']['identcheck_dob'] ) && empty( $args['order_details']['identcheck_age'] ) ) {
				throw new Exception( esc_html__( 'Either a "Date of Birth" or "Minimum Age" must be eneted for "Ident-Check".', 'dhl-for-woocommerce' ) );
			}
		}

		// Validate weight
		try {
			$this->validate_field( 'weight', $args['order_details']['weight'] );
		} catch ( Exception $e ) {
			throw new Exception( 'Weight - ' . esc_html( $e->getMessage() ) );
		}

		if ( isset( $args['order_details']['multi_packages_enabled'] ) && ( $args['order_details']['multi_packages_enabled'] == 'yes' ) ) {

			if ( isset( $args['order_details']['total_packages'] ) ) {

				for ( $i = 0; $i < intval( $args['order_details']['total_packages'] ); $i++ ) {

					if ( empty( $args['order_details']['packages_number'][ $i ] ) ) {
						throw new Exception( esc_html__( 'A package number is empty. Ensure all package details are filled in.', 'dhl-for-woocommerce' ) );
					}

					if ( empty( $args['order_details']['packages_weight'][ $i ] ) ) {
						throw new Exception( esc_html__( 'A package weight is empty. Ensure all package details are filled in.', 'dhl-for-woocommerce' ) );
					}

					if ( empty( $args['order_details']['packages_length'][ $i ] ) ) {
						throw new Exception( esc_html__( 'A package length is empty. Ensure all package details are filled in.', 'dhl-for-woocommerce' ) );
					}

					if ( empty( $args['order_details']['packages_width'][ $i ] ) ) {
						throw new Exception( esc_html__( 'A package width is empty. Ensure all package details are filled in.', 'dhl-for-woocommerce' ) );
					}

					if ( empty( $args['order_details']['packages_height'][ $i ] ) ) {
						throw new Exception( esc_html__( 'A package height is empty. Ensure all package details are filled in.', 'dhl-for-woocommerce' ) );
					}
				}
			}
		} elseif ( empty( $args['order_details']['weight'] ) ) {

				throw new Exception( esc_html__( 'Order "Weight" is empty!', 'dhl-for-woocommerce' ) );
		}

		// if ( empty( $args['order_details']['duties'] )) {
		// throw new Exception( __('DHL "Duties" is empty!', 'dhl-for-woocommerce') );
		// }

		if ( empty( $args['order_details']['currency'] ) ) {
			throw new Exception( esc_html__( 'Shop "Currency" is empty!', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['shipping_address']['city'] ) ) {
			throw new Exception( esc_html__( 'Shipping "City" is empty!', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['shipping_address']['country'] ) ) {
			throw new Exception( esc_html__( 'Shipping "Country" is empty!', 'dhl-for-woocommerce' ) );
		}

		// Validate shipping address
		if ( empty( $args['shipping_address']['address_1'] ) ) {
			throw new Exception( esc_html__( 'Shipping "Address 1" is empty!', 'dhl-for-woocommerce' ) );
		}

		$this->pos_ps = PR_DHL()->is_packstation( $args['shipping_address']['address_1'] );
		$this->pos_rs = PR_DHL()->is_parcelshop( $args['shipping_address']['address_1'] );
		$this->pos_po = PR_DHL()->is_post_office( $args['shipping_address']['address_1'] );

		// If Packstation, post number is mandatory
		if ( $this->pos_ps && empty( $args['shipping_address']['dhl_postnum'] ) ) {
			throw new Exception( esc_html__( 'Post Number is missing, it is mandatory for "Packstation" delivery.', 'dhl-for-woocommerce' ) );
		}

		// Check address 2 if no parcel shop is being selected
		if ( ! $this->pos_ps && ! $this->pos_rs && ! $this->pos_po ) {
			// If address 2 missing, set last piece of an address to be address 2
			if ( empty( $args['shipping_address']['address_2'] ) ) {
				$set_key = false;
				// Break address into pieces by spaces
				$address_exploded = explode( ' ', $args['shipping_address']['address_1'] );

				// If no spaces found
				if ( count( $address_exploded ) == 1 ) {
					// Break address into pieces by '.'
					$address_exploded = explode( '.', $args['shipping_address']['address_1'] );

					// If no address number and in Germany, return error
					if ( 1 === count( $address_exploded ) && 'DE' === $args['shipping_address']['country'] ) {
						throw new Exception( esc_html__( 'Shipping street number is missing!', 'dhl-for-woocommerce' ) );
					}
				}

				// If greater than 1, means there are two parts to the address...otherwise Address 2 is empty which is possible in some countries outside of Germany
				if ( count( $address_exploded ) > 1 ) {
					// Loop through address and set number value only...
					// ...last found number will be 'address_2'
					foreach ( $address_exploded as $address_key => $address_value ) {
						if ( is_numeric( $address_value ) ) {
							// Set last index as street number
							$set_key = $address_key;
						}
					}

					// If no number was found, then take last part of address no matter what it is
					if ( false === $set_key ) {
						$set_key = $address_key;
					}

					// The number is the first part of address 1
					if ( 0 === $set_key ) {
						// Set "address_2" first, as first part
						$args['shipping_address']['address_2'] = implode( ' ', array_slice( $address_exploded, 0, 1 ) );
						// Remove "address_2" from "address_1"
						$args['shipping_address']['address_1'] = implode( ' ', array_slice( $address_exploded, 1 ) );
					} else {
						// Set "address_2" first
						$args['shipping_address']['address_2'] = implode( ' ', array_slice( $address_exploded, $set_key ) );
						// Remove "address_2" from "address_1"
						$args['shipping_address']['address_1'] = implode( ' ', array_slice( $address_exploded, 0, $set_key ) );
					}
				}
			}
		}

		// Add default values for required fields that might not be passed e.g. phone
		$default_args = array(
			'shipping_address' =>
										array(
											'name'      => '',
											'company'   => '',
											'address_2' => '',
											'email'     => '',
											'postcode'  => '',
											'state'     => '',
											'phone'     => ' ',
										),
		);

		$args['shipping_address'] = wp_parse_args( $args['shipping_address'], $default_args['shipping_address'] );

		$default_args_item = array(
			'item_description' => '',
			'sku'              => '',
			'line_total'       => 0,
			'country_origin'   => '',
			'hs_code'          => '',
			'qty'              => 1,
		);

		foreach ( $args['items'] as $key => $item ) {

			if ( ! empty( $item['hs_code'] ) ) {
				try {
					$this->validate_field( 'hs_code', $item['hs_code'] );
				} catch ( Exception $e ) {
					throw new Exception( 'HS Code - ' . esc_html( $e->getMessage() ) );
				}
			}

			$args['items'][ $key ] = wp_parse_args( $item, $default_args_item );
		}

		$this->args = $args;
	}

	protected function set_message() {

		if ( ! empty( $this->args ) ) {
			// Set date related functions to German time
			// date_default_timezone_set('Europe/Berlin');

			// SERVICES DATA
			$services_map = array(
				'age_visual'           => array(
					'name' => 'VisualCheckOfAge',
					'type' => 'type',
				),
				'preferred_location'   => array(
					'name' => 'PreferredLocation',
					'type' => 'details',
				),
				'preferred_neighbor'   => array(
					'name' => 'PreferredNeighbour',
					'type' => 'details',
				),
				'preferred_day'        => array(
					'name' => 'PreferredDay',
					'type' => 'details',
				),
				'personally'           => array(
					'name' => 'Personally',
				),
				'no_neighbor'          => array(
					'name' => 'NoNeighbourDelivery',
				),
				'named_person'         => array(
					'name' => 'NamedPersonOnly',
				),
				'premium'              => array(
					'name' => 'Premium',
				),
				'additional_insurance' => array(
					'name' => 'AdditionalInsurance',
				),
				'bulky_goods'          => array(
					'name' => 'BulkyGoods',
				),
				'identcheck'           => array(
					'name' => 'IdentCheck',
				),
				'routing'              => array(
					'name' => 'ParcelOutletRouting',
				),
				'go_green_plus'        => array(
					'name' => 'goGreenPlus',
				),
				'PDDP'                 => array(
					'name' => 'PDDP',
				),
				'cdp_delivery'         => array(
					'name' => 'CDP',
				),
				'endorsement'          => array(
					'name' => 'Endorsement',
					'type' => 'type',
				),
				'signature_service'    => array(
					'name' => 'signedForByRecipient',
				),
			);

			$services = array();
			foreach ( $services_map as $key => $value ) {

				if ( ! empty( $this->args['order_details'][ $key ] ) ) {

					// If checkbox not checked
					if ( empty( $this->args['order_details'][ $key ] ) || ( $this->args['order_details'][ $key ] == 'no' ) ) {
						continue;
					}

					// If a checkbox is checked, check specific structure
					if ( $this->args['order_details'][ $key ] == 'yes' ) {

						$services[ $value['name'] ] = array(
							'active' => 1,
						);

						switch ( $key ) {
							case 'additional_insurance':
								$services[ $value['name'] ]['insuranceAmount'] = $this->args['order_details']['total_value'];
								break;
							case 'identcheck':
								$services[ $value['name'] ]['Ident']['surname']     = isset( $this->args['shipping_address']['first_name'] ) ? $this->args['shipping_address']['first_name'] : '';
								$services[ $value['name'] ]['Ident']['givenName']   = isset( $this->args['shipping_address']['last_name'] ) ? $this->args['shipping_address']['last_name'] : '';
								$services[ $value['name'] ]['Ident']['dateOfBirth'] = isset( $this->args['order_details']['identcheck_dob'] ) ? $this->args['order_details']['identcheck_dob'] : '';
								$services[ $value['name'] ]['Ident']['minimumAge']  = isset( $this->args['order_details']['identcheck_age'] ) ? $this->args['order_details']['identcheck_age'] : '';
								break;
							case 'routing':
								$services[ $value['name'] ]['details'] = isset( $this->args['order_details']['routing_email'] ) ? $this->args['order_details']['routing_email'] : '';
								break;
							case 'go_green_plus':
								$services['returnShipmentGoGreenPlus'] = array(
									'active' => 1,
								);
								break;
						}
					} else {
						$services[ $value['name'] ] = array(
							'active'       => 1,
							$value['type'] => $this->args['order_details'][ $key ],
						);
					}
				}
			}

			// EMAIL NOTIFCATION
			$notification_email = array();
			if ( ( isset( $this->args['order_details']['email_notification'] ) &&
					( $this->args['order_details']['email_notification'] == 'yes' || $this->args['order_details']['email_notification'] == '1' )
				) ||
				( isset( $this->args['dhl_settings']['email_notification'] ) &&
					( $this->args['dhl_settings']['email_notification'] == 'sendviatc' ) ) ) {

				$notification_email['recipientEmailAddress'] = $this->args['shipping_address']['email'];
			}

			// COD DATA
			$bank_data = array();

			if ( ! empty( $this->args['order_details']['cod_value'] ) ) {

				$services['CashOnDelivery'] = array(
					'active' => 1,
				);

				// If the fee was added to the customer i.e. 'cod_fee' == 'yes', then do not add to merchange i.e. 'addFee' = 0
				// if( isset( $this->args['dhl_settings']['cod_fee'] ) && $this->args['dhl_settings']['cod_fee'] == 'yes' ) {
				// $services['CashOnDelivery']['addFee'] = 0;
				// } else {
				// $services['CashOnDelivery']['addFee'] = 1;
				// }

				$services['CashOnDelivery']['codAmount'] = $this->args['order_details']['cod_value'];

				$bank_data_map = array(
					'bank_holder' => 'accountOwner',
					'bank_name'   => 'bankName',
					'bank_iban'   => 'iban',
					'bank_ref'    => 'note1',
					'bank_ref_2'  => 'note2',
					'bank_bic'    => 'bic',
				);

				foreach ( $bank_data_map as $key => $value ) {

					if ( isset( $this->args['dhl_settings'][ $key ] ) ) {
						$bank_data[ $value ] = $this->args['dhl_settings'][ $key ];
					}
				}

				// $bank_data['note1'] = $this->args['order_details']['order_id'];
				// $bank_data['note2'] = $this->args['shipping_address']['email'];
			}

			// create account number
			$product_number = preg_match( '!\d+!', $this->args['order_details']['dhl_product'], $matches );

			if ( $product_number ) {
				$account_number = $this->args['dhl_settings']['account_num'] . $matches[0] . $this->args['dhl_settings']['participation'];
			} else {
				throw new Exception( esc_html__( 'Could not create account number - no product number.', 'dhl-for-woocommerce' ) );
			}

			$this->args['order_details']['weight'] = $this->maybe_convert_weight( $this->args['order_details']['weight'], $this->args['order_details']['weightUom'] );

			// Ensure company name is first for shipper address
			if ( ! empty( $this->args['dhl_settings']['shipper_company'] ) ) {
				$shipper_name1 = $this->args['dhl_settings']['shipper_company'];
				$shipper_name2 = $this->args['dhl_settings']['shipper_name'];
			} else {
				$shipper_name1 = $this->args['dhl_settings']['shipper_name'];
				$shipper_name2 = '';
			}

			// Ensure company name is first for receiver address
			if ( ! empty( $this->args['shipping_address']['company'] ) ) {
				$receiver_name1 = $this->args['shipping_address']['company'];
				$receiver_name2 = $this->args['shipping_address']['name'];
			} else {
				$receiver_name1 = $this->args['shipping_address']['name'];
				$receiver_name2 = '';
			}

			$receiver_name3 = '';
			if ( isset( $this->args['shipping_address']['name3'] ) && ! empty( $this->args['shipping_address']['name3'] ) ) {
				$receiver_name3 = $this->args['shipping_address']['name3'];
			}

			$berlin_date = new DateTime( 'now', new DateTimeZone( 'Europe/Berlin' ) );

			$shipment_items = array();

			if ( isset( $this->args['order_details']['multi_packages_enabled'] ) && ( $this->args['order_details']['multi_packages_enabled'] == 'yes' ) ) {

				foreach ( $this->args['order_details']['packages_weight'] as $key => $item ) {

					$shipment_items[] = array(
						'weightInKG' => $this->maybe_convert_weight( $item, $this->args['order_details']['weightUom'] ),
						'lengthInCM' => $this->maybe_convert_centimeters( $this->args['order_details']['packages_length'][ $key ], $this->args['order_details']['dimUom'] ),
						'widthInCM'  => $this->maybe_convert_centimeters( $this->args['order_details']['packages_width'][ $key ], $this->args['order_details']['dimUom'] ),
						'heightInCM' => $this->maybe_convert_centimeters( $this->args['order_details']['packages_height'][ $key ], $this->args['order_details']['dimUom'] ),
					);
				}
			}

			$dhl_label_body =
				array(
					'Version'           =>
						array(
							'majorRelease' => '3',
							'minorRelease' => '1',
						),
					'ShipmentOrder'     =>
						array(
							'sequenceNumber' => $this->args['order_details']['order_id'],
							'Shipment'       =>
								array(
									'ShipmentDetails' =>
										array(
											'product'      => $this->args['order_details']['dhl_product'],
											'accountNumber' => $account_number,
											'shipmentDate' => $berlin_date->format( 'Y-m-d' ),
											'ShipmentItem' =>
												array(
													'weightInKG' => $this->args['order_details']['weight'],
												),
											'Service'      => $services,
											'Notification' => $notification_email,
											'BankData'     => $bank_data,
											'customerReference' => $this->args['order_details']['order_id'],
											'returnShipmentReference' => $this->args['order_details']['order_id'],
										),
									'Shipper'         =>
										array(
											'Name'    =>
												array(
													'name1' => $shipper_name1,
													'name2' => $shipper_name2,
												),
											'Address' =>
												array(
													'streetName' => $this->args['dhl_settings']['shipper_address'],
													'streetNumber' => $this->args['dhl_settings']['shipper_address_no'],
													'zip'  => $this->args['dhl_settings']['shipper_address_zip'],
													'city' => $this->args['dhl_settings']['shipper_address_city'],
													'Origin' =>
														array(
															'countryISOCode' => $this->args['dhl_settings']['shipper_country'],
															'state' => $this->args['dhl_settings']['shipper_address_state'],
														),
												),
											'Communication' =>
												array(
													'phone' => $this->args['dhl_settings']['shipper_phone'],
													'email' => $this->args['dhl_settings']['shipper_email'],
												),
										),
									'Receiver'        =>
										array(
											'name1'   => $receiver_name1,
											'Address' =>
												array(
													'name2' => $receiver_name2,
													'name3' => $receiver_name3,
													'streetName' => $this->args['shipping_address']['address_1'],
													'streetNumber' => $this->args['shipping_address']['address_2'],
													'zip'  => $this->args['shipping_address']['postcode'],
													'city' => $this->args['shipping_address']['city'],
													'Origin' =>
														array(
															'countryISOCode' => $this->args['shipping_address']['country'],
															'state' => $this->args['shipping_address']['state'],
														),
												),
											// 'Packstation' => array(),
											'Communication' =>
												array(
													'phone' => $this->args['shipping_address']['phone'],
													'email' => $this->args['shipping_address']['email'],
												),
										),
								),

						),
					'labelResponseType' => 'B64',
					'labelFormat'       => $this->args['dhl_settings']['label_format'],
				);

			if ( $this->args['dhl_settings']['add_logo'] == 'yes' ) {

				unset( $dhl_label_body['ShipmentOrder']['Shipment']['Shipper'] );
				$dhl_label_body['ShipmentOrder']['Shipment']['ShipperReference'] = $this->args['dhl_settings']['shipper_reference'];
			}

			// Unset receiver email if set to don't send in settings
			if ( isset( $this->args['dhl_settings']['email_notification'] ) && $this->args['dhl_settings']['email_notification'] == 'no' ) {

				unset( $dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['Communication']['email'] );

			}

			// Unset receiver phone if set to don't send in settings
			if ( isset( $this->args['dhl_settings']['phone_notification'] ) && $this->args['dhl_settings']['phone_notification'] == 'no' ) {

				unset( $dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['Communication']['phone'] );
			}

			if ( $this->pos_ps || $this->pos_rs || $this->pos_po ) {

				// Address is NOT needed if using a parcel shop
				unset( $dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['Address'] );

				$parcel_shop = array(
					'zip'    => $this->args['shipping_address']['postcode'],
					'city'   => $this->args['shipping_address']['city'],
					'Origin' =>
						array(
							'countryISOCode' => $this->args['shipping_address']['country'],
							'state'          => $this->args['shipping_address']['state'],
						),
				);

				$address_num = filter_var( $this->args['shipping_address']['address_1'], FILTER_SANITIZE_NUMBER_INT );

				if ( $this->pos_ps ) {
					$parcel_shop['postNumber']        = $this->args['shipping_address']['dhl_postnum'];
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

					if ( ! empty( $this->args['shipping_address']['dhl_postnum'] ) ) {
						$parcel_shop['postNumber'] = $this->args['shipping_address']['dhl_postnum'];
						// Only post number should be set, so unset email
						unset( $dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['Communication']['email'] );
					}

					$parcel_shop['postfilialNumber'] = $address_num;

					$dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['Postfiliale'] = $parcel_shop;
				}
			}

			if ( isset( $this->args['order_details']['return_address_enabled'] ) && ( $this->args['order_details']['return_address_enabled'] == 'yes' ) ) {

				$dhl_label_body['ShipmentOrder']['Shipment']['ShipmentDetails']['returnShipmentAccountNumber'] = $this->args['dhl_settings']['account_num'] . self::DHL_RETURN_PRODUCT . $this->args['dhl_settings']['participation_return'];

				// Ensure company name is first for return address
				if ( ! empty( $this->args['order_details']['return_company'] ) ) {
					$return_name1 = $this->args['order_details']['return_company'];
					$return_name2 = $this->args['order_details']['return_name'];
				} else {
					$return_name1 = $this->args['order_details']['return_name'];
					$return_name2 = '';
				}

				$dhl_label_body['ShipmentOrder']['Shipment']['ReturnReceiver'] = array(
					'Name'          =>
						array(
							'name1' => $return_name1,
							'name2' => $return_name2,
						),
					'Address'       =>
						array(
							'streetName'   => $this->args['order_details']['return_address'],
							'streetNumber' => $this->args['order_details']['return_address_no'],
							'zip'          => $this->args['order_details']['return_address_zip'],
							'city'         => $this->args['order_details']['return_address_city'],
							'Origin'       =>
								array(
									'countryISOCode' => $this->args['dhl_settings']['return_country'],
									'state'          => $this->args['order_details']['return_address_state'],
								),
						),
					'Communication' =>
						array(
							'phone' => $this->args['order_details']['return_phone'],
							'email' => $this->args['order_details']['return_email'],
						),
				);
			}

			// Is codeable set here since it's at a high level in the message
			if ( isset( $this->args['order_details']['is_codeable'] ) && ( $this->args['order_details']['is_codeable'] == 'yes' ) ) {
				$dhl_label_body['ShipmentOrder']['PrintOnlyIfCodeable'] = array( 'active' => 1 );
			}

			// Add customs info
			if ( PR_DHL()->is_crossborder_shipment( $this->args['shipping_address'] ) ) {

				$customsDetails = array();

				$item_description = '';
				foreach ( $this->args['items'] as $key => $item ) {
					// weightInKG is in KG needs to be changed if 'g' or 'lbs' etc.
					$item['item_weight'] = $this->maybe_convert_weight( $item['item_weight'], $this->args['order_details']['weightUom'] );

					$item_description .= ! empty( $item_description ) ? ', ' : '';
					$item_description .= $item['item_description'];

					$json_item = array(
						'description'         => substr( $item['item_description'], 0, 255 ),
						'countryCodeOrigin'   => $item['country_origin'],
						'customsTariffNumber' => $item['hs_code'],
						'amount'              => intval( $item['qty'] ),
						'netWeightInKG'       => round( floatval( $item['item_weight'] ), 4 ),
						'customsValue'        => round( floatval( $item['item_value'] ), 4 ),
					);
					// $customsDetails = $json_item;
					array_push( $customsDetails, $json_item );
				}

				$item_description = substr( $item_description, 0, 255 );

				$dhl_label_body['ShipmentOrder']['Shipment']['ExportDocument'] =
					array(
						'invoiceNumber'         => $this->args['order_details']['invoice_num'],
						'exportType'            => apply_filters( 'pr_shipping_dhl_paket_label_shipment_export_type', 'COMMERCIAL_GOODS' ),
						'exportTypeDescription' => $item_description,
						'termsOfTrade'          => $this->args['order_details']['duties'],
						'placeOfCommital'       => $this->args['shipping_address']['country'],
						'customsCurrency'       => $this->args['order_details']['currency'],
						'ExportDocPosition'     => $customsDetails,
					);
			}

			// Unset/remove any items that are empty strings or 0, even if required!
			$this->body_request = $this->walk_recursive_remove( $dhl_label_body );

			if ( ! isset( $this->body_request['Version']['minorRelease'] ) ) {
				$this->body_request['Version']['minorRelease'] = 0;
			}

			// Ensure Export Document is set before adding additional fee
			if ( isset( $this->body_request['ShipmentOrder']['Shipment']['ExportDocument'] ) ) {
				// Additional fees, required and 0 so place after check
				$additional_fee = floatval( $this->args['order_details']['additional_fee'] );
				$shipping_fee   = floatval( $this->args['order_details']['shipping_fee'] );
				$total_add_fee  = $additional_fee + $shipping_fee;
				$this->body_request['ShipmentOrder']['Shipment']['ExportDocument']['additionalFee'] = $total_add_fee;
			}

			// If "Ident-Check" enabled, then ensure both fields are passed even if empty
			if ( isset( $this->args['order_details']['identcheck'] ) && ( $this->args['order_details']['identcheck'] == 'yes' ) ) {

				if ( ! isset( $this->body_request['ShipmentOrder']['Shipment']['ShipmentDetails']['Service']['IdentCheck']['Ident']['minimumAge'] ) ) {

					$this->body_request['ShipmentOrder']['Shipment']['ShipmentDetails']['Service']['IdentCheck']['Ident']['minimumAge'] = '';
				}

				if ( ! isset( $this->body_request['ShipmentOrder']['Shipment']['ShipmentDetails']['Service']['IdentCheck']['Ident']['dateOfBirth'] ) ) {

					$this->body_request['ShipmentOrder']['Shipment']['ShipmentDetails']['Service']['IdentCheck']['Ident']['dateOfBirth'] = '';
				}
			}

			// Ensure 'postNumber' is passed with 'Postfiliale' even if empty
			if ( ! isset( $this->body_request['ShipmentOrder']['Shipment']['Receiver']['Postfiliale']['postNumber'] ) ) {
				// Additional fees, required and 0 so place after check
				$this->body_request['ShipmentOrder']['Shipment']['Receiver']['Postfiliale']['postNumber'] = '';
			}

			// Ensure 'zip' is passed, it is required
			if ( ! isset( $this->body_request['ShipmentOrder']['Shipment']['Receiver']['Address']['zip'] ) && ! isset( $this->body_request['ShipmentOrder']['Shipment']['Receiver']['Packstation'] ) && ! isset( $this->body_request['ShipmentOrder']['Shipment']['Receiver']['Postfiliale'] ) ) {
				$this->body_request['ShipmentOrder']['Shipment']['Receiver']['Address']['zip'] = '';
			}

			// Set shipping address Zip to null if Country is United Arab Emirates (and other countries will no zip) (this passes the SOAP required zip field check, but ensures no zip is sent)
			if ( isset( $this->body_request['ShipmentOrder']['Shipment']['Receiver']['Address'] ) ) {
				if ( isset( $this->args['shipping_address']['country'] ) && in_array(
					$this->args['shipping_address']['country'],
					array(
						'AE',
						'AG',
						'AI',
						'AO',
						'AW',
						'BF',
						'BI',
						'BJ',
						'BO',
						'BS',
						'BW',
						'BZ',
						'CD',
						'CF',
						'CG',
						'CI',
						'CM',
						'CO',
						'DJ',
						'DM',
						'ER',
						'FJ',
						'GA',
						'GD',
						'GH',
						'GM',
						'GQ',
						'HK',
						'IE',
						'JM',
						'KI',
						'KM',
						'KN',
						'KP',
						'LC',
						'LY',
						'ML',
						'MO',
						'MR',
						'MW',
						'NA',
						'NR',
						'QA',
						'RW',
						'SC',
						'SL',
						'SR',
						'ST',
						'TD',
						'TG',
						'UG',
						'ZW',
					)
				) ) {
					if ( empty( $this->body_request['ShipmentOrder']['Shipment']['Receiver']['Address']['zip'] ) ) {
						$this->body_request['ShipmentOrder']['Shipment']['Receiver']['Address']['zip'] = null; // Cannot be empty string, must be null
					}
				}
			}

			if ( count( $shipment_items ) > 0 ) {
				$shipment_order  = $this->body_request['ShipmentOrder'];
				$shipment_orders = array();
				$sequence        = 0;
				foreach ( $shipment_items as $shipment_item ) {
					++$sequence;
					$copy_ship_order = $shipment_order;
					$copy_ship_order['Shipment']['ShipmentDetails']['ShipmentItem'] = $shipment_item;
					$copy_ship_order['sequenceNumber']                              = $this->args['order_details']['order_id'] . '-' . $sequence;

					// Add an "Index" to ensure "ref" is NOT used in XML and actual values are passed instead
					$copy_ship_order['Shipment']['ShipmentDetails']['Service']['Index'] = $sequence;

					if ( isset( $copy_ship_order['Shipment']['Shipper'] ) ) {
						$copy_ship_order['Shipment']['Shipper']['Index']                      = $sequence;
						$copy_ship_order['Shipment']['Shipper']['Address']['Origin']['Index'] = $sequence;
					}

					$copy_ship_order['Shipment']['Receiver']['Index'] = $sequence;
					if ( isset( $copy_ship_order['Shipment']['Receiver']['Address']['Origin'] ) ) {
						$copy_ship_order['Shipment']['Receiver']['Address']['Origin']['Index'] = $sequence;
					}

					if ( isset( $copy_ship_order['Shipment']['Receiver']['Packstation']['Origin'] ) ) {
						$copy_ship_order['Shipment']['Receiver']['Packstation']['Origin']['Index'] = $sequence;
					}

					if ( isset( $copy_ship_order['Shipment']['Receiver']['Postfiliale']['Origin'] ) ) {
						$copy_ship_order['Shipment']['Receiver']['Postfiliale']['Origin']['Index'] = $sequence;
					}

					if ( isset( $this->args['order_details']['return_address_enabled'] ) && ( $this->args['order_details']['return_address_enabled'] == 'yes' ) ) {
						$copy_ship_order['Shipment']['ReturnReceiver']['Index']                      = $sequence;
						$copy_ship_order['Shipment']['ReturnReceiver']['Address']['Origin']['Index'] = $sequence;
					}

					if ( isset( $copy_ship_order['Shipment']['ExportDocument']['ExportDocPosition'] ) ) {
						foreach ( $copy_ship_order['Shipment']['ExportDocument']['ExportDocPosition'] as $key => $value ) {

							$copy_ship_order['Shipment']['ExportDocument']['ExportDocPosition'][ $key ]['Index'] = $sequence;
						}
					}

					$shipment_orders[] = $copy_ship_order;
				}
				$this->body_request['ShipmentOrder'] = $shipment_orders;
			}

			// die(print_r($this->body_request, true));

			// error_log(print_r($this->body_request, true));
			return $this->body_request;
		}
	}
}
