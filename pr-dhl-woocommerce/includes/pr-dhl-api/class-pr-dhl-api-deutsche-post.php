<?php

use PR\DHL\REST_API\Deutsche_Post\Auth;
use PR\DHL\REST_API\Deutsche_Post\Client;
use PR\DHL\REST_API\Deutsche_Post\Item_Info;
use PR\DHL\REST_API\Drivers\JSON_API_Driver;
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
	const API_URL_PRODUCTION = 'https://api-qa.deutschepost.com/';

	/**
	 * The URL to the sandbox API.
	 *
	 * @since [*next-version*]
	 */
	const API_URL_SANDBOX = 'https://api-qa.deutschepost.com/';

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
		// Use a standard WordPress-driven API driver, decorated using the JSON driver decorator class
		return new JSON_API_Driver( new WP_API_Driver() );
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
			'GMP' => __( 'Packet', 'pr-shipping-dhl' ),
			'GPP' => __( 'Packet Plus', 'pr-shipping-dhl' ),
			'GPT' => __( 'Packet Tracked', 'pr-shipping-dhl' ),
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
		$item_barcode = get_post_meta( $order_id, 'pr_dhl_dp_item_barcode', true );

		// If order has no saved barcode, create the DHL item and get the barcode
		if ( empty( $item_barcode ) ) {
			try {
				$item_info = new Item_Info( $args );
			} catch (Exception $e) {
				throw $e;
			}

			// Create the item and get the barcode
			$item_response = $this->api_client->create_item( $item_info );
			$item_barcode = $item_response->barcode;
			$item_id = $item_response->id;

			// Save it in the order
			update_post_meta( $order_id, 'pr_dhl_dp_item_barcode', $item_barcode );
			update_post_meta( $order_id, 'pr_dhl_dp_item_id', $item_id );
		}

		// Get the label for the created item
		$label_pdf_data = $this->api_client->get_item_label( $item_barcode );
		// Save the label to a file
		$this->save_dhl_label_file( $item_barcode, $label_pdf_data );

		return array(
			'label_path' => $this->get_dhl_label_path( $item_barcode ),
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
			throw new Exception( __( 'DHL Label has no path!', 'pr-shipping-dhl' ) );
		}

		$label_path = $label_info['label_path'];

		if ( file_exists( $label_path ) ) {
			$res = unlink( $label_path );

			if ( ! $res ) {
				throw new Exception( __( 'DHL Label could not be deleted!', 'pr-shipping-dhl' ) );
			}
		}
	}

	/**
	 * Retrieves the path to an item label file.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $barcode The item barcode.
	 * @param string $format The label file format.
	 *
	 * @return string
	 */
	public function get_dhl_label_path( $barcode, $format = 'pdf' ) {
		return PR_DHL()->get_dhl_label_folder_dir() . 'dhl-label-' . $barcode . '.' . $format;
	}

	/**
	 * Saves an item label file.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $barcode The item barcode.
	 * @param string $label_data The label file data.
	 *
	 * @throws Exception If failed to save the label file.
	 */
	public function save_dhl_label_file( $barcode, $label_data ) {
		$label_path = $this->get_dhl_label_path( $barcode );

		if ( validate_file( $label_path ) > 0 ) {
			throw new Exception( __( 'Invalid file path!', 'pr-shipping-dhl' ) );
		}

		$file_ret = file_put_contents( $label_path, $label_data );

		if ( empty( $file_ret ) ) {
			throw new Exception( __( 'DHL Item Label file cannot be saved!', 'pr-shipping-dhl' ) );
		}
	}

	/**
	 * Retrieves the path to an AWB label file.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $awb The AWB.
	 *
	 * @return string
	 */
	public function get_awb_label_file_path( $awb ) {
		return $this->get_dhl_label_path( 'awb-' . $awb );
	}

	/**
	 * Checks if an AWB label file already exist, and if not fetches it from the API and saves it.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $awb The AWB.
	 *
	 * @return array An array containing the "path" and "url" to the label file.
	 *
	 * @throws Exception
	 */
	public function maybe_create_awb_label_file( $awb )
	{
		$label_path = $this->get_awb_label_file_path( $awb );

		// Create the file if it does not exist
		if ( ! file_exists( $label_path ) ) {
			$label_data = $this->api_client->get_awb_label( $awb );
			$this->save_awb_label_file( $awb, $label_data );
		}

		$filename = basename( $label_path );

		return array(
			'path' => PR_DHL()->get_dhl_label_folder_dir() . $filename,
			'url' => PR_DHL()->get_dhl_label_folder_url() . $filename,
		);
	}

	/**
	 * Saves an AWB label file.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $awb The AWB.
	 * @param string $label_data The label data.
	 *
	 * @return string The path to the label file.
	 *
	 * @throws Exception
	 */
	public function save_awb_label_file( $awb, $label_data )
	{
		$label_path = $this->get_awb_label_file_path( $awb );

		if ( validate_file( $label_path ) > 0 ) {
			throw new Exception( __( 'Invalid file path!', 'pr-shipping-dhl' ) );
		}

		$file_ret = file_put_contents( $label_path, $label_data );

		if ( empty( $file_ret ) ) {
			throw new Exception( __( 'DHL AWB Label file cannot be saved!', 'pr-shipping-dhl' ) );
		}

		return $label_path;
	}

	public function get_merged_awb_label_info( $dhl_order_id )
	{
		$filename = 'dhl-label-merged-awbs-' . $dhl_order_id . '.pdf';

		return array(
			'path' => PR_DHL()->get_dhl_label_folder_dir() . $filename,
			'url' => PR_DHL()->get_dhl_label_folder_url() . $filename,
		);
	}

	public function create_merged_awb_label( $awbs, $dhl_order_id ) {
		if ( empty ($awbs) ) {
			throw new Exception(__('No AWBs given', 'pr-shipping-dhl'));
		}

		// Don't merge if there is only 1 AWB. Just return the info for the single AWB label file
		if (count($awbs) === 1) {
			return $this->maybe_create_awb_label_file( $awbs[0] );
		}

		$pdfMerger = new PDFMerger();

		foreach ( $awbs as $awb ) {
			$file = $this->maybe_create_awb_label_file( $awb );

			if ( ! file_exists( $file['path'] ) ) {
				continue;
			}

			$ext = pathinfo($file['path'], PATHINFO_EXTENSION);
			if ( stripos($ext, 'pdf') === false) {
				throw new Exception( __('Not all the file formats are the same.', 'pr-shipping-dhl') );
			}

			$pdfMerger->addPDF( $file['path'], 'all' );
		}

		$label_info = $this->get_merged_awb_label_info( $dhl_order_id );
		$pdfMerger->merge( 'file',  $label_info['path'] );

		return $label_info;
	}

	/**
	 * Deletes an AWB label file.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $awb The AWB.
	 *
	 * @throws Exception
	 */
	public function delete_awb_label( $awb )
	{
		$label_path = $this->get_awb_label_file_path( $awb );

		if (file_exists($label_path)) {
			$res = unlink($label_path);

			if (!$res) {
				throw new Exception(__('DHL AWB Label could not be deleted!', 'pr-shipping-dhl'));
			}
		}
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
	public function create_order()
	{
		// Create the DHL order
		$response = $this->api_client->create_order();

		$this->get_settings();

		// Get the current DHL order - the one that was just submitted
		$order = $this->api_client->get_order($response->orderId);
		$order_items = $order['items'];

		// Get the tracking note type setting
		$tracking_note_type = $this->get_setting('dhl_tracking_note', 'customer');
		$tracking_note_type = ($tracking_note_type == 'yes') ? '' : 'customer';

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
				$item_awb_note = __('Shipment AWB: ', 'pr-shipping-dhl') . $shipment->awb;
				$item_wc_order->add_order_note( $item_awb_note, $tracking_note_type, true );

				// Save the AWB in the list.
				$awbs[] = $shipment->awb;

				// Save the DHL order ID in the WC order meta
				update_post_meta( $item_wc_order_id, 'pr_dhl_dp_order', $response->orderId );
			}
		}

		// Generate the merged AWB label file
		$this->create_merged_awb_label( $awbs, $response->orderId );

		return $response->orderId;
	}
}
