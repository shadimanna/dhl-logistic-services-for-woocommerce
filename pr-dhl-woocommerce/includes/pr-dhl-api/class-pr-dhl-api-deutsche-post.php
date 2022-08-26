<?php

use PR\DHL\REST_API\Deutsche_Post\Auth;
use PR\DHL\REST_API\Deutsche_Post\Client;
use PR\DHL\REST_API\Deutsche_Post\Item_Info;
use PR\DHL\REST_API\Drivers\JSON_API_Driver;
use PR\DHL\REST_API\Drivers\Logging_Driver;
use PR\DHL\REST_API\Drivers\WP_API_Driver;
use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;

// Exit if accessed directly or class already exists
if ( ! defined( 'ABSPATH' ) || class_exists( 'PR_DHL_API_Deutsche_Post', false ) ) {
	return;
}

class PR_DHL_API_Deutsche_Post extends PR_DHL_API {
	/**
	 * The URL to the API.
	 *
	 * @since [*next-version*]
	 */
	const API_URL_PRODUCTION = 'https://api.dhl.com/';

	/**
	 * The URL to the sandbox API.
	 *
	 * @since [*next-version*]
	 */
	const API_URL_SANDBOX = 'https://api-sandbox.dhl.com/';

	/**
	 * The transient name where the API access token is stored.
	 *
	 * @since [*next-version*]
	 */
	const ACCESS_TOKEN_TRANSIENT = 'pr_dhl_deutsche_post_access_token';

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
	public function __construct( $country_code ) {
		$this->country_code = $country_code;

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
			$this->get_ekp(),
			$this->get_contact_name(),
			$this->get_contact_phone(),
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
		list( $client_id, $client_secret ) = $this->get_api_creds();

		// Create the auth object using this instance's API driver and URL
		return new Auth(
			$this->api_driver,
			$this->get_api_url(),
			$client_id,
			$client_secret,
			static::ACCESS_TOKEN_TRANSIENT
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function is_dhl_deutsche_post() {
		return true;
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
		return array(
			$this->get_setting( 'dhl_api_key' ),
			$this->get_setting( 'dhl_api_secret' ),
		);
	}

	/**
	 * Retrieves the DHL account number, or "EKP".
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 *
	 * @throws Exception If failed to retrieve the EKP from the settings.
	 */
	public function get_ekp() {
		return $this->get_setting( 'dhl_account_num' );
	}

	/**
	 * Retrieves the DHL contact name for orders.
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 *
	 * @throws Exception If failed to retrieve the contact name from the settings.
	 */
	public function get_contact_name() {
		return $this->get_setting( 'dhl_contact_name' );
	}

	/**
	 * Retrieves the DHL contact name for orders.
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 *
	 * @throws Exception If failed to retrieve the contact name from the settings.
	 */
	public function get_contact_phone() {
		return $this->get_setting( 'dhl_contact_phone_number' );
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

		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
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
			'GMP-STANDARD' => __( 'Packet Standard', 'dhl-for-woocommerce' ),
			'GMP' => __( 'Packet Priority', 'dhl-for-woocommerce' ),
			'GPP' => __( 'Packet Plus', 'dhl-for-woocommerce' ),
			'GPT' => __( 'Packet Tracked', 'dhl-for-woocommerce' ),
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
		$order_id = isset( $args[ 'order_details' ][ 'order_id' ] )
			? $args[ 'order_details' ][ 'order_id' ]
			: null;
		$order = wc_get_order( $order_id );
		$item_barcode = $order->get_meta('pr_dhl_dp_item_barcode' );

		// If order has no saved barcode, create the DHL item and get the barcode
		if ( empty( $item_barcode ) ) {
			$uom = get_option( 'woocommerce_weight_unit' );
			try {
				$item_info = new Item_Info( $args, $uom );
			} catch (Exception $e) {
				throw $e;
			}

			// Create the item and get the barcode
			$item_response = $this->api_client->create_item( $item_info );
			$item_barcode = $item_response->barcode;
			$item_id = $item_response->id;

			// Save it in the order
			$order->update_meta_data( 'pr_dhl_dp_item_barcode', $item_barcode );
			$order->update_meta_data( 'pr_dhl_dp_item_id', $item_id );
			$order->save();

		}

		// Get the label for the created item
		$label_pdf_data = $this->api_client->get_item_label( $item_barcode );
		// Save the label to a file
		$this->save_dhl_label_file( 'item', $item_barcode, $label_pdf_data );

		return array(
			'label_path' => $this->get_dhl_item_label_file_info( $item_barcode )->path,
			'item_barcode' => $item_barcode,
			'tracking_number' => $item_barcode,
			'tracking_status' => '',
		);
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
				$item_wc_order->update_meta_data( 'pr_dhl_dp_awb', $shipment->awb );

				// An an order note for the AWB
				$item_awb_note = __('Shipment AWB: ', 'dhl-for-woocommerce') . $shipment->awb;
				// 'type' should alwasys be private for AWB
				$item_wc_order->add_order_note( $item_awb_note, '', true );

				// Save the AWB in the list.
				$awbs[] = $shipment->awb;

				// Save the DHL order ID in the WC order meta
				$item_wc_order->update_meta_data( 'pr_dhl_dp_order', $response->orderId );
				$item_wc_order->save();
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
}
