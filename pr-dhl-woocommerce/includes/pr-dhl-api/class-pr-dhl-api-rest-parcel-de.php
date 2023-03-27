<?php

use PR\DHL\REST_API\Parcel_DE\Auth;
use PR\DHL\REST_API\Parcel_DE\Client;
use PR\DHL\REST_API\Parcel_DE\Item_Info;
use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;

// Exit if accessed directly or class already exists
if ( ! defined( 'ABSPATH' ) || class_exists( 'PR_DHL_API_Parcel_DE', false ) ) {
	return;
}

class PR_DHL_API_REST_Parcel_DE extends PR_DHL_API_REST_Paket {
	/**
	 * The URL to the API.
	 *
	 * @since [*next-version*]
	 */
	const API_URL_PRODUCTION = 'https://api.dhl.com/parcel/de/shipping/';

	/**
	 * The URL to the sandbox API.
	 *
	 * @since [*next-version*]
	 */
	const API_URL_SANDBOX = 'https://api-sandbox.dhl.com/parcel/de/shipping/';

	/**
	 * The API driver instance.
	 *
	 * @since [*next-version*]
	 *
	 * @var API_Driver_Interface
	 */
	public $api_driver;
	/**
	 * The API authorization instance.
	 *
	 * @since [*next-version*]
	 *
	 * @var Auth
	 */
	public $api_auth;
	/**
	 * The API client instance.
	 *
	 * @since [*next-version*]
	 *
	 * @var Client
	 */
	public $api_client;

	/**
	 * Constructor.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $country_code The country code.
	 *
	 * @throws Exception If an error occurred while creating the API driver, auth or client.
	 */
	public function __construct( ) {
		try {
			$this->api_driver = $this->create_api_driver();
			$this->api_auth = $this->create_api_auth();
			$this->api_client = $this->create_api_client();
		} catch ( Exception $e ) {
			throw $e;
		}
	}

	/**
	 * Initializes the API client instance.
	 *
	 * @since [*next-version*]
	 *
	 * @return Client
	 *
	 * @throws Exception If failed to create the API client.
	 */
	protected function create_api_client() {
		// Create the API client, using this instance's driver and auth objects
		return new Client(
			$this->get_api_url(),
			$this->api_driver,
			$this->api_auth
		);
	}

	/**
	 * Initializes the API auth instance.
	 *
	 * @since [*next-version*]
	 *
	 * @return API_Auth_Interface
	 *
	 * @throws Exception If failed to create the API auth.
	 */
	protected function create_api_auth() {
		// Get the saved DHL customer API credentials
		list( $username, $password, $api_key ) = $this->get_api_creds();

		// Create the auth object using this instance's API driver and URL
		return new Auth(
			$this->api_driver,
			$this->get_api_url(),
			$username,
			$password,
			$api_key
		);
	}

	/**
	 * Retrieves the API URL.
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 *
	 * @throws Exception If failed to determine if using the sandbox API or not.
	 */
	public function get_api_url() {
		$is_sandbox = $this->get_setting( 'dhl_sandbox' );
		$is_sandbox = filter_var($is_sandbox, FILTER_VALIDATE_BOOLEAN);
		$api_url = ( $is_sandbox ) ? static::API_URL_SANDBOX : static::API_URL_PRODUCTION;

		return $api_url;
	}

	/**
	 * Retrieves the API credentials.
	 *
	 * @since [*next-version*]
	 *
	 * @return array The client ID and client secret.
	 *
	 * @throws Exception If failed to retrieve the API credentials.
	 */
	public function get_api_creds() {
		/*return array(
			$this->get_setting( 'dhl_api_key' ),
			$this->get_setting( 'dhl_api_secret' ),
		);*/

		return array(
			'3333333333_01',
			'pass',
			'AYjXP5URDZnGbNVtxQa8iHNvXlboQqtG'
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function get_dhl_label( $args ) {
		$this->set_arguments( $args );
		$uom = get_option( 'woocommerce_weight_unit' );
		try {
			$item_info = new Item_Info( $args, $uom );
		} catch ( Exception $e ) {
			throw $e;
		}

		// Create the item and get the barcode
		$item_response = $this->api_client->create_item( $item_info );

		$file = $this->save_data_file( 'label', $args['order_details']['order_id'],
			$item_response->items[0]->label->b64 );

		return array(
			'label_path'      => $file['data_path'],
			'item_barcode'    => $item_response->items[0]->shipmentNo,
			'tracking_number' => $item_response->items[0]->shipmentNo,
			'tracking_status' => '',
		);
	}

	/**
	 * Delete label.
	 *
	 * @param $label_info.
	 *
	 * @throws Exception.
	 */
	public function delete_dhl_label( $label_info ) {
		$this->api_client->delete_item( $label_info['tracking_number'] );

		if ( ! isset( $label_info['label_path'] ) ) {
			throw new Exception( __( 'DHL Label has no path!', 'dhl-for-woocommerce' ) );
		}

		$label_path = $label_info['label_path'];

		if ( file_exists( $label_path ) ) {
			$res = unlink( $label_path );

			if ( ! $res ) {
				throw new Exception( __( 'DHL Label could not be deleted!', 'dhl-for-woocommerce' ) );
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

	public function save_data_file( $prefix, $order_id, $label_data ) {
		$data_name = 'dhl-' . $prefix . '-' . $order_id . '.pdf';
		$data_path = PR_DHL()->get_dhl_label_folder_dir() . $data_name;
		$data_url = PR_DHL()->get_dhl_label_folder_url() . $data_name;

		//windows path will not get exception
		if( validate_file($data_path) > 0 && validate_file($data_path) !== 2 ) {
			throw new Exception( __('Invalid file path!', 'dhl-for-woocommerce' ) );
		}

		$label_data_decoded = base64_decode($label_data);
		$file_ret = file_put_contents( $data_path, $label_data_decoded );

		if( empty( $file_ret ) ) {
			throw new Exception( __('File cannot be saved!', 'dhl-for-woocommerce' ) );
		}

		return array( 'data_url' => $data_url, 'data_path' => $data_path);
	}

	public function set_arguments( $args ) {
		// Validate set args

		if ( empty( $args['dhl_settings']['api_user'] ) ) {
			throw new Exception( __('Please, provide the username in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['api_pwd'] )) {
			throw new Exception( __('Please, provide the password for the username in the DHL shipping settings', 'dhl-for-woocommerce') );
		}

		// Validate order details
		if ( empty( $args['dhl_settings']['account_num'] ) ) {
			throw new Exception( __('Please, provide an account in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['participation'] )) {
			throw new Exception( __('Please, provide a participation number for the shipping method in the DHL shipping settings', 'dhl-for-woocommerce') );
		}

		if ( empty( $args['dhl_settings']['shipper_name'] )) {
			throw new Exception( __('Please, provide a shipper name in the DHL shipping settings', 'dhl-for-woocommerce') );
		}

		if ( empty( $args['dhl_settings']['shipper_address'] )) {
			throw new Exception( __('Please, provide a shipper address in the DHL shipping settings', 'dhl-for-woocommerce') );
		}

		if ( empty( $args['dhl_settings']['shipper_address_no'] )) {
			throw new Exception( __('Please, provide a shipper address number in the DHL shipping settings', 'dhl-for-woocommerce') );
		}

		if ( empty( $args['dhl_settings']['shipper_address_city'] )) {
			throw new Exception( __('Please, provide a shipper city in the DHL shipping settings', 'dhl-for-woocommerce') );
		}

		if ( empty( $args['dhl_settings']['shipper_address_zip'] )) {
			throw new Exception( __('Please, provide a shipper postcode in the DHL shipping settings', 'dhl-for-woocommerce') );
		}

		if( $args['dhl_settings']['add_logo'] == 'yes' && empty( $args['dhl_settings']['shipper_reference'] ) ){
			throw new Exception( __('Please, provide a shipper reference in the DHL shipping settings', 'dhl-for-woocommerce') );
		}

		// Order details
		if ( empty( $args['order_details']['dhl_product'] )) {
			throw new Exception( __('DHL "Product" is empty!', 'dhl-for-woocommerce') );
		}

		// return receiver
		if ( isset( $args['order_details']['return_address'] ) && ( $args['order_details']['return_address'] == 'yes' ) ) {

			if ( ( $args['order_details']['dhl_product'] != 'V01PAK' ) && ( $args['order_details']['dhl_product'] != 'V01PRIO' ) && ( $args['order_details']['dhl_product'] != 'V86PARCEL' ) && ( $args['order_details']['dhl_product'] != 'V55PAK' ) ){

				throw new Exception( __('Returns are not supported by this DHL Service.', 'dhl-for-woocommerce') );
			}

			if ( empty( $args['dhl_settings']['return_name'] )) {
				throw new Exception( __('Please, provide a return name in the DHL shipping settings', 'dhl-for-woocommerce') );
			}

			if ( empty( $args['dhl_settings']['return_address'] )) {
				throw new Exception( __('Please, provide a return address in the DHL shipping settings', 'dhl-for-woocommerce') );
			}

			if ( empty( $args['dhl_settings']['return_address_no'] )) {
				throw new Exception( __('Please, provide a return address number in the DHL shipping settings', 'dhl-for-woocommerce') );
			}

			if ( empty( $args['dhl_settings']['return_address_city'] )) {
				throw new Exception( __('Please, provide a return city in the DHL shipping settings', 'dhl-for-woocommerce') );
			}

			if ( empty( $args['dhl_settings']['return_address_zip'] )) {
				throw new Exception( __('Please, provide a return postcode in the DHL shipping settings', 'dhl-for-woocommerce') );
			}
		}

		if ( empty( $args['order_details']['order_id'] )) {
			throw new Exception( __('Shop "Order ID" is empty!', 'dhl-for-woocommerce') );
		}

		if ( empty( $args['order_details']['weightUom'] )) {
			throw new Exception( __('Shop "Weight Units of Measure" is empty!', 'dhl-for-woocommerce') );
		}



		if ( isset( $args['order_details']['identcheck'] ) && ( $args['order_details']['identcheck'] == 'yes' ) ) {
			if ( empty( $args['shipping_address']['first_name'] ) || empty( $args['shipping_address']['last_name'] ) ) {
				throw new Exception( __('First name and last name must be passed for "Identity Check".', 'dhl-for-woocommerce') );
			}

			if ( empty( $args['order_details']['identcheck_dob'] ) && empty( $args['order_details']['identcheck_age'] ) ) {
				throw new Exception( __('Either a "Date of Birth" or "Minimum Age" must be eneted for "Ident-Check".', 'dhl-for-woocommerce') );
			}
		}

		// Validate weight
		try {
			$this->validate_field( 'weight', $args['order_details']['weight'] );
		} catch (Exception $e) {
			throw new Exception( 'Weight - ' . $e->getMessage() );
		}

		if ( isset( $args['order_details']['multi_packages_enabled'] ) && ( $args['order_details']['multi_packages_enabled'] == 'yes' ) ) {

			if ( isset( $args['order_details']['total_packages'] ) ) {

				for ($i=0; $i<intval($args['order_details']['total_packages']); $i++) {

					if( empty($args['order_details']['packages_number'][$i]) ) {
						throw new Exception( __('A package number is empty. Ensure all package details are filled in.', 'dhl-for-woocommerce') );
					}

					if( empty($args['order_details']['packages_weight'][$i]) ) {
						throw new Exception( __('A package weight is empty. Ensure all package details are filled in.', 'dhl-for-woocommerce') );
					}

					if( empty($args['order_details']['packages_length'][$i]) ) {
						throw new Exception( __('A package length is empty. Ensure all package details are filled in.', 'dhl-for-woocommerce') );
					}

					if( empty($args['order_details']['packages_width'][$i]) ) {
						throw new Exception( __('A package width is empty. Ensure all package details are filled in.', 'dhl-for-woocommerce') );
					}

					if( empty($args['order_details']['packages_height'][$i]) ) {
						throw new Exception( __('A package height is empty. Ensure all package details are filled in.', 'dhl-for-woocommerce') );
					}
				}
			}
		} else {

			if ( empty( $args['order_details']['weight'] )) {
				throw new Exception( __('Order "Weight" is empty!', 'dhl-for-woocommerce') );
			}

		}

		// if ( empty( $args['order_details']['duties'] )) {
		// 	throw new Exception( __('DHL "Duties" is empty!', 'dhl-for-woocommerce') );
		// }

		if ( empty( $args['order_details']['currency'] )) {
			throw new Exception( __('Shop "Currency" is empty!', 'dhl-for-woocommerce') );
		}

		if ( empty( $args['shipping_address']['city'] )) {
			throw new Exception( __('Shipping "City" is empty!', 'dhl-for-woocommerce') );
		}

		if ( empty( $args['shipping_address']['country'] )) {
			throw new Exception( __('Shipping "Country" is empty!', 'dhl-for-woocommerce') );
		}

		// Validate shipping address
		if ( empty( $args['shipping_address']['address_1'] )) {
			throw new Exception( __('Shipping "Address 1" is empty!', 'dhl-for-woocommerce') );
		}


		$this->pos_ps = PR_DHL()->is_packstation( $args['shipping_address']['address_1'] );
		$this->pos_rs = PR_DHL()->is_parcelshop( $args['shipping_address']['address_1'] );
		$this->pos_po = PR_DHL()->is_post_office( $args['shipping_address']['address_1'] );

		// If Packstation, post number is mandatory
		if ( $this->pos_ps && empty( $args['shipping_address']['dhl_postnum'] ) ) {
			throw new Exception( __('Post Number is missing, it is mandatory for "Packstation" delivery.', 'dhl-for-woocommerce') );
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

					// If no address number and in Germany, return error
					if ( 1 === count( $address_exploded ) && 'DE' === $args['shipping_address']['country'] ) {
						throw new Exception(__('Shipping street number is missing!', 'dhl-for-woocommerce'));
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
					if( false === $set_key ) {
						$set_key = $address_key;
					}

					// The number is the first part of address 1
					if( 0 === $set_key ) {
						// Set "address_2" first, as first part
						$args['shipping_address']['address_2'] = implode( ' ', array_slice( $address_exploded, 0, 1 ) );
						// Remove "address_2" from "address_1"
						$args['shipping_address']['address_1'] = implode( ' ', array_slice( $address_exploded, 1 ) );
					} else {
						// Set "address_2" first
						$args['shipping_address']['address_2'] = implode( ' ', array_slice( $address_exploded, $set_key ) );
						// Remove "address_2" from "address_1"
						$args['shipping_address']['address_1'] = implode( ' ', array_slice( $address_exploded, 0 , $set_key ) );
					}
				}
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

	protected function validate_field( $key, $value ) {

		try {

			switch ( $key ) {
				case 'weight':
					$this->validate( wc_format_decimal( $value ) );
					break;
				case 'hs_code':
					$this->validate( $value, 'string', 4, 11 );
					break;
				case 'pickup':
					$this->validate( $value, 'string', 14, 14 );
					break;
				case 'distribution':
					$this->validate( $value, 'string', 6, 6 );
					break;
			}

		} catch (Exception $e) {
			throw $e;
		}
	}

	protected function validate( $value, $type = 'int', $min_len = 0, $max_len = 0 ) {

		switch ( $type ) {
			case 'string':
				if( ( strlen($value) < $min_len ) || ( strlen($value) > $max_len ) ) {
					if ( $min_len == $max_len ) {
						throw new Exception( sprintf( __('The value must be %s characters.', 'dhl-for-woocommerce'), $min_len) );
					} else {
						throw new Exception( sprintf( __('The value must be between %s and %s characters.', 'dhl-for-woocommerce'), $min_len, $max_len ) );
					}
				}
				break;
			case 'int':
				if( ! is_numeric( $value ) ) {
					throw new Exception( __('The value must be a number') );
				}
				break;
		}
	}
}