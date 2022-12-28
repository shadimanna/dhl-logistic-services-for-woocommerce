<?php

use PR\DHL\REST_API\Parcel_DE\Auth;
use PR\DHL\REST_API\Parcel_DE\Client;
use PR\DHL\REST_API\Parcel_DE\Item_Info;
use PR\DHL\REST_API\Drivers\JSON_API_Driver;
use PR\DHL\REST_API\Drivers\Logging_Driver;
use PR\DHL\REST_API\Drivers\WP_API_Driver;
use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;

// Exit if accessed directly or class already exists
if ( ! defined( 'ABSPATH' ) || class_exists( 'PR_DHL_API_Parcel_DE', false ) ) {
	return;
}

class PR_DHL_API_REST_Parcel_DE extends PR_DHL_API {
	/**
	 * The URL to the API.
	 *
	 * @since [*next-version*]
	 */
	const API_URL_PRODUCTION = 'https://api.dhl.com/parcel/de/';

	/**
	 * The URL to the sandbox API.
	 *
	 * @since [*next-version*]
	 */
	const API_URL_SANDBOX = 'https://api-sandbox.dhl.com/parcel/de/';

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
	 * Initializes the API driver instance.
	 *
	 * @since [*next-version*]
	 *
	 * @return API_Driver_Interface
	 *
	 * @throws Exception If failed to create the API driver.
	 */
	protected function create_api_driver() {
		// Use a standard WordPress-driven API driver to send requests using WordPress' functions
		$driver = new WP_API_Driver();

		// This will log requests given to the original driver and log responses returned from it
		$driver = new Logging_Driver( PR_DHL(), $driver );

		// This will prepare requests given to the previous driver for JSON content
		// and parse responses returned from it as JSON.
		$driver = new JSON_API_Driver( $driver );

		//, decorated using the JSON driver decorator class
		return $driver;
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
		list( $client_id, $client_secret, $api_key ) = $this->get_api_creds();

		// Create the auth object using this instance's API driver and URL
		return new Auth(
			$this->api_driver,
			$this->get_api_url(),
			$client_id,
			$client_secret,
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
			'l7do9bl8gS6y9aHys0u3NR5uqAufPARS'
		);
	}

	/**
	 * Retrieves a single setting.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $key     The key of the setting to retrieve.
	 * @param string $default The value to return if the setting is not saved.
	 *
	 * @return mixed The setting value.
	 */
	public function get_setting( $key, $default = '' ) {
		$settings = $this->get_settings();

		return $settings[ $key ] ?? $default;
	}

	/**
	 * Retrieves all of the Deutsche Post settings.
	 *
	 * @since [*next-version*]
	 *
	 * @return array An associative array of the settings keys mapping to their values.
	 */
	public function get_settings() {
		return get_option( 'woocommerce_pr_dhl_dp_settings', array() );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function dhl_test_connection( $client_id, $client_secret ) {
		try {
			// Test the given ID and secret
			$token = $this->api_auth->test_connection( $client_id, $client_secret );
			// Save the token if successful
			$this->api_auth->save_token( $token );

			return $token;
		} catch ( Exception $e ) {
			$this->api_auth->save_token( null );
			throw $e;
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function dhl_reset_connection() {
		return $this->api_auth->revoke();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function get_dhl_products_international() {
		return array(
			'V55PAK' => __('DHL Paket Connect', 'dhl-for-woocommerce'),
			'V54EPAK' => __('DHL Europaket (B2B)', 'dhl-for-woocommerce'),
			'V53WPAK' => __('DHL Paket International', 'dhl-for-woocommerce'),
			'V66WPI' => __('DHL Warenpost International', 'dhl-for-woocommerce'),
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function get_dhl_products_domestic() {
		return $this->get_dhl_products_international();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function get_dhl_label( $args ) {
		//var_dump($args);
		$order_id = isset( $args[ 'order_details' ][ 'order_id' ] )
			? $args[ 'order_details' ][ 'order_id' ]
			: null;

		$uom = get_option( 'woocommerce_weight_unit' );
		try {
			$item_info = new Item_Info( $args, $uom );
		} catch (Exception $e) {
			throw $e;
		}

		// Create the item and get the barcode
		$item_response = $this->api_client->create_item( $item_info );

		//$label_pdf_data = $this->api_client->get_item_label( $item_barcode );
		// Save the label to a file
		//$this->save_dhl_label_file( 'item', $item_barcode, $label_pdf_data );

		return $item_response;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function delete_dhl_label( $label_info ) {
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

	/**
	 * Retrieves the filename for DHL item label files.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $barcode The DHL item barcode.
	 * @param string $format The file format.
	 *
	 * @return string
	 */
	public function get_dhl_item_label_file_name( $barcode, $format = 'pdf' ) {
		return sprintf('dhl-label-%s.%s', $barcode, $format);
	}

	/**
	 * Retrieves the filename for DHL AWB label files.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $awb The AWB.
	 * @param string $format The file format.
	 *
	 * @return string
	 */
	public function get_dhl_awb_label_file_name( $awb, $format = 'pdf' ) {
		return sprintf('dhl-label-awb-%s.%s', $awb, $format);
	}

	/**
	 * Retrieves the filename for DHL order label files (a.k.a. merged AWB label files).
	 *
	 * @since [*next-version*]
	 *
	 * @param string $order_id The DHL order ID.
	 * @param string $format The file format.
	 *
	 * @return string
	 */
	public function get_dhl_order_label_file_name( $order_id, $format = 'pdf' ) {
		return sprintf('dhl-waybill-order-%s.%s', $order_id, $format);
	}

	/**
	 * Retrieves the file info for a DHL item label file.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $barcode The DHL item barcode.
	 * @param string $format The file format.
	 *
	 * @return object An object containing the file "path" and "url" strings.
	 */
	public function get_dhl_item_label_file_info( $barcode, $format = 'pdf' ) {
		$file_name = $this->get_dhl_item_label_file_name($barcode, $format);

		return (object) array(
			'path' => PR_DHL()->get_dhl_label_folder_dir() . $file_name,
			'url' => PR_DHL()->get_dhl_label_folder_url() . $file_name,
		);
	}

	/**
	 * Retrieves the file info for DHL AWB label files.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $awb The AWB.
	 * @param string $format The file format.
	 *
	 * @return object An object containing the file "path" and "url" strings.
	 */
	public function get_dhl_awb_label_file_info( $awb, $format = 'pdf' ) {
		$file_name = $this->get_dhl_awb_label_file_name($awb, $format);

		return (object) array(
			'path' => PR_DHL()->get_dhl_label_folder_dir() . $file_name,
			'url' => PR_DHL()->get_dhl_label_folder_url() . $file_name,
		);
	}

	/**
	 * Retrieves the file info for DHL order label files (a.k.a. merged AWB label files).
	 *
	 * @since [*next-version*]
	 *
	 * @param string $order_id The DHL order ID.
	 * @param string $format The file format.
	 *
	 * @return object An object containing the file "path" and "url" strings.
	 */
	public function get_dhl_order_label_file_info( $order_id, $format = 'pdf') {
		$file_name = $this->get_dhl_order_label_file_name( $order_id, $format);

		return (object) array(
			'path' => PR_DHL()->get_dhl_label_folder_dir() . $file_name,
			'url' => PR_DHL()->get_dhl_label_folder_url() . $file_name,
		);
	}

	/**
	 * Retrieves the file info for any DHL label file, based on type.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $type The label type: "item", "awb" or "order".
	 * @param string $key The key: barcode for type "item", AWB for type "awb" and order ID for type "order".
	 *
	 * @return object An object containing the file "path" and "url" strings.
	 */
	public function get_dhl_label_file_info( $type, $key ) {
		// Return file info for "awb" type
		if ( $type === 'awb') {
			return $this->get_dhl_awb_label_file_info( $key );
		}

		// Return file info for "order" type
		if ( $type === 'order' ) {
			return $this->get_dhl_order_label_file_info( $key );
		}

		// Return info for "item" type
		return $this->get_dhl_item_label_file_info( $key );
	}

	/**
	 * Saves an item label file.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $type The label type: "item", "awb" or "order".
	 * @param string $key The key: barcode for type "item", AWB for type "awb" and order ID for type "order".
	 * @param string $data The label file data.
	 *
	 * @return object The info for the saved label file, containing the "path" and "url".
	 *
	 * @throws Exception If failed to save the label file.
	 */
	public function save_dhl_label_file( $type, $key, $data ) {
		// Get the file info based on type
		$file_info = $this->get_dhl_label_file_info( $type, $key );

		// Validate the file path
		if ( validate_file( $file_info->path ) > 0 && validate_file( $file_info->path ) !== 2 ) {
			throw new Exception( __( 'Invalid file path!', 'dhl-for-woocommerce' ) );
		}

		$file_ret = file_put_contents( $file_info->path, $data );

		if ( empty( $file_ret ) ) {
			throw new Exception( __( 'DHL label file cannot be saved!', 'dhl-for-woocommerce' ) );
		}

		return $file_info;
	}

	/**
	 * Deletes an AWB label file.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $type The label type: "item", "awb" or "order".
	 * @param string $key The key: barcode for type "item", AWB for type "awb" and order ID for type "order".
	 *
	 * @throws Exception If the file could not be deleted.
	 */
	public function delete_dhl_label_file( $type, $key )
	{
		// Get the file info based on type
		$file_info = $this->get_dhl_label_file_info( $type, $key );

		// Do nothing if file does not exist
		if ( ! file_exists( $file_info->path ) ) {
			return;
		}

		// Attempt to delete the file
		$res = unlink( $file_info->path );

		// Throw error if the file could not be deleted
		if (!$res) {
			throw new Exception(__('DHL AWB Label could not be deleted!', 'dhl-for-woocommerce'));
		}
	}

	/**
	 * Checks if an AWB label file already exist, and if not fetches it from the API and saves it.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $awb The AWB.
	 *
	 * @return object An object containing the "path" and "url" to the label file.
	 *
	 * @throws Exception
	 */
	public function create_dhl_awb_label_file( $awb )
	{
		$file_info = $this->get_dhl_awb_label_file_info( $awb );

		// Skip creating the file if it already exists
		if ( file_exists( $file_info->path ) ) {
			return $file_info;
		}

		// Get the label data from the API client
		$label_data = $this->api_client->get_awb_label( $awb );
		// Save the label file
		$this->save_dhl_label_file( 'awb', $awb, $label_data );

		return $file_info;
	}

	/**
	 * Checks if an order label file already exist, and if not fetches it from the API and saves it.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $order_id The DHL order ID.
	 *
	 * @return object An object containing the "path" and "url" to the label file.
	 *
	 * @throws Exception
	 */
	public function create_dhl_order_label_file( $order_id )
	{
		$file_info = $this->get_dhl_order_label_file_info( $order_id );

		// Skip creating the file if it already exists
		if ( file_exists( $file_info->path ) ) {
			return $file_info;
		}

		// Get the order with the given ID
		$order = $this->api_client->get_order( $order_id );
		if ($order === null) {
			throw new Exception("DHL order {$order_id} does not exist.");
		}

		// For multiple shipments, maybe create each label file and then merge them
		$loader = PR_DHL_Libraryloader::instance();
		$pdfMerger = $loader->get_pdf_merger();

		if( $pdfMerger === null ){

			throw new Exception( __('Library conflict, could not merge PDF files. Please download PDF files individually.', 'dhl-for-woocommerce') );
		}

		foreach ( $order['shipments'] as $shipment ) {
			// Create the single AWB label file
			$awb_label_info = $this->create_dhl_awb_label_file( $shipment->awb );

			// Ensure file exists
			if ( ! file_exists( $awb_label_info->path ) ) {
				continue;
			}

			// Ensure it is a PDF file
			$ext = pathinfo($awb_label_info->path, PATHINFO_EXTENSION);
			if ( stripos($ext, 'pdf') === false) {
				throw new Exception( __('Not all the file formats are the same.', 'dhl-for-woocommerce') );
			}

			// Add to merge queue
			$pdfMerger->addPDF( $awb_label_info->path, 'all' );
		}

		// Merge all files in the queue
		$pdfMerger->merge( 'file',  $file_info->path );

		return $file_info;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function dhl_validate_field( $key, $value ) {
	}

	/**
	 * Finalizes and creates the current Deutsche Post order.
	 *
	 * @since [*next-version*]
	 *
	 * @return string The ID of the created DHL order.
	 *
	 * @throws Exception If an error occurred while and the API failed to create the order.
	 */
	public function create_order( $copy_count = 1 )
	{
		// Create the DHL order
		$response = $this->api_client->create_order( $copy_count );

		$this->get_settings();

		// Get the current DHL order - the one that was just submitted
		$order = $this->api_client->get_order($response->orderId);
		$order_items = $order['items'];

		// Get the tracking note type setting
		// $tracking_note_type = $this->get_setting('dhl_tracking_note', 'customer');
		// $tracking_note_type = ($tracking_note_type == 'yes') ? '' : 'customer';

		// Go through the shipments retrieved from the API and save the AWB of the shipment to
		// each DHL item's associated WooCommerce order in post meta. This will make sure that each
		// WooCommerce order has a reference to the its DHL shipment AWB.
		// At the same time, we will be collecting the AWBs to merge the label PDFs later on, as well
		// as adding order notes for the AWB to each WC order.
		$awbs = array();
		foreach ($response->shipments as $shipment) {
			foreach ($shipment->items as $item) {
				if ( ! isset( $order_items[ $item->barcode ] ) ) {
					continue;
				}

				// Get the WC order for this DHL item
				$item_wc_order_id = $order_items[ $item->barcode ];
				$item_wc_order = wc_get_order( $item_wc_order_id );

				// Save the AWB to the WC order
				update_post_meta( $item_wc_order_id, 'pr_dhl_dp_awb', $shipment->awb );

				// An an order note for the AWB
				$item_awb_note = __('Shipment AWB: ', 'dhl-for-woocommerce') . $shipment->awb;
				// 'type' should alwasys be private for AWB
				$item_wc_order->add_order_note( $item_awb_note, '', true );

				// Save the AWB in the list.
				$awbs[] = $shipment->awb;

				// Save the DHL order ID in the WC order meta
				update_post_meta( $item_wc_order_id, 'pr_dhl_dp_order', $response->orderId );
			}
		}

		// Generate the merged AWB label file
		$this->create_dhl_order_label_file( $response->orderId );

		return $response->orderId;
	}

	public function get_dhl_nature_type() {
		return array(
			'SALE_GOODS' => __( 'Sale Goods', 'dhl-for-woocommerce' ),
			'RETURN_GOODS' => __( 'Return Goods', 'dhl-for-woocommerce' ),
			'GIFT' => __( 'Gift', 'dhl-for-woocommerce' ),
			'COMMERCIAL_SAMPLE' => __( 'Commercial Sample', 'dhl-for-woocommerce' ),
			'DOCUMENTS' => __( 'Documents', 'dhl-for-woocommerce' ),
			'MIXED_CONTENTS' => __( 'Mixed Contents', 'dhl-for-woocommerce' ),
			'OTHERS' => __( 'Others', 'dhl-for-woocommerce' ),
		);
	}

	protected function set_arguments( $args ) {
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
}