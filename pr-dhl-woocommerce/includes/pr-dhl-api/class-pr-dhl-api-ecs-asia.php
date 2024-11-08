<?php

use PR\DHL\REST_API\DHL_eCS_Asia\Auth;
use PR\DHL\REST_API\DHL_eCS_Asia\Client;
use PR\DHL\REST_API\DHL_eCS_Asia\Item_Info;
use PR\DHL\REST_API\Drivers\JSON_API_Driver;
use PR\DHL\REST_API\Drivers\Logging_Driver;
use PR\DHL\REST_API\Drivers\WP_API_Driver;
use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;

// Exit if accessed directly or class already exists
if ( ! defined( 'ABSPATH' ) || class_exists( 'PR_DHL_API_eCS_Asia', false ) ) {
	return;
}

class PR_DHL_API_eCS_Asia extends PR_DHL_API {
	/**
	 * The URL to the API.
	 *
	 * @since [*next-version*]
	 */
	const API_URL_PRODUCTION = 'https://api.dhlecommerce.dhl.com/';

	/**
	 * The URL to the sandbox API.
	 *
	 * @since [*next-version*]
	 */
	const API_URL_SANDBOX = 'https://sandbox.dhlecommerce.asia/';

	/**
	 * The transient name where the API access token is stored.
	 *
	 * @since [*next-version*]
	 */
	const ACCESS_TOKEN_TRANSIENT = 'pr_dhl_ecs_asia_access_token';

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
			$this->api_auth   = $this->create_api_auth();
			$this->api_client = $this->create_api_client();
		} catch ( Exception $e ) {
			throw $e;
		}
	}

	public function get_dhl_duties() {
		$duties = array(
			'DDU' => esc_html__( 'Duties Consignee Paid', 'dhl-for-woocommerce' ),
			'DDP' => esc_html__( 'Duties Shipper Paid', 'dhl-for-woocommerce' ),
		);
		return $duties;
	}

	public function get_dhl_tax_id_types() {
		$types = array(
			'3' => esc_html__( 'IOSS', 'dhl-for-woocommerce' ),
			'4' => esc_html__( 'IOSS (DHL)', 'dhl-for-woocommerce' ),
			'1' => esc_html__( 'GST/VAT', 'dhl-for-woocommerce' ),
			'2' => esc_html__( 'EORI', 'dhl-for-woocommerce' ),
		);
		return $types;
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
			$this->get_pickup_id(),
			$this->get_soldto_id(),
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

		// , decorated using the JSON driver decorator class
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
	public function is_dhl_ecs_asia() {
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
		$is_sandbox = filter_var( $is_sandbox, FILTER_VALIDATE_BOOLEAN );
		$api_url    = ( $is_sandbox ) ? static::API_URL_SANDBOX : static::API_URL_PRODUCTION;

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
	 * Retrieves the DHL Pickup Account ID
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 *
	 * @throws Exception If failed to retrieve the EKP from the settings.
	 */
	public function get_pickup_id() {
		return $this->get_setting( 'dhl_pickup_id' );
	}

	/**
	 * Retrieves the DHL Pickup Account ID
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 *
	 * @throws Exception If failed to retrieve the EKP from the settings.
	 */
	public function get_soldto_id() {
		return $this->get_setting( 'dhl_soldto_id' );
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
		return get_option( 'woocommerce_pr_dhl_ecs_asia_settings', array() );
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

	public function get_dhl_content_indicator() {
		return array(
			'00' => esc_html__( 'Does not contain Lithium Batteries', 'dhl-for-woocommerce' ),
			'01' => esc_html__( 'Lithium Batteries in item', 'dhl-for-woocommerce' ),
			'02' => esc_html__( 'Lithium Batteries packed with item', 'dhl-for-woocommerce' ),
			'03' => esc_html__( 'Lithium Batteries only', 'dhl-for-woocommerce' ),
			'04' => esc_html__( 'Rechargeable Batteries in item', 'dhl-for-woocommerce' ),
			'05' => esc_html__( 'Rechargeable Batteries packed with item', 'dhl-for-woocommerce' ),
			'06' => esc_html__( 'Rechargeable Batteries only', 'dhl-for-woocommerce' ),
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function get_dhl_products_international() {

		$country_code = $this->country_code;
		$products     = $this->list_dhl_products_international();

		$accepted_products = array();

		foreach ( $products as $product_code => $product ) {
			if ( strpos( $product['origin_countries'], $country_code ) !== false ) {
				$accepted_products[ $product_code ] = $product['name'];
			}
		}

		return $accepted_products;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function get_dhl_products_domestic() {

		$country_code = $this->country_code;

		$products = $this->list_dhl_products_domestic();

		$accepted_products = array();

		foreach ( $products as $product_code => $product ) {
			if ( strpos( $product['origin_countries'], $country_code ) !== false ) {
				$accepted_products[ $product_code ] = $product['name'];
			}
		}

		return $accepted_products;
	}

	public function list_dhl_products_international() {

		$products = array(
			'PPM' => array(
				'name'             => esc_html__( 'Packet Plus International Priority Manifest', 'dhl-for-woocommerce' ),
				'origin_countries' => 'CN,HK,TH',
			),
			'PPS' => array(
				'name'             => esc_html__( 'Packet Plus International Standard', 'dhl-for-woocommerce' ),
				'origin_countries' => 'AU,CN,HK,IL,IN,MY,SG,TH',
			),
			'PPW' => array(
				'name'             => esc_html__( 'Packet Plus Standard', 'dhl-for-woocommerce' ),
				'origin_countries' => 'AU,CN,HK,IL,IN,MY,SG,TH',
			),
			'PPR' => array(
				'name'             => esc_html__( 'Destination Redelivery Services', 'dhl-for-woocommerce' ),
				'origin_countries' => 'CN,HK',
			),
			'PKM' => array(
				'name'             => esc_html__( 'Packet International Priority Manifest', 'dhl-for-woocommerce' ),
				'origin_countries' => 'CN,HK,TH',
			),
			'PKD' => array(
				'name'             => esc_html__( 'Packet International Standard', 'dhl-for-woocommerce' ),
				'origin_countries' => 'CN,TH,HK,SG,AU,IN',
			),
			'PLT' => array(
				'name'             => esc_html__( 'Parcel International Direct Standard', 'dhl-for-woocommerce' ),
				'origin_countries' => 'AU,CN,HK,IL,IN,MY,SG,TH',
			),
			'PLE' => array(
				'name'             => esc_html__( 'Parcel International Direct Expedited', 'dhl-for-woocommerce' ),
				'origin_countries' => 'IN,CN,HK,SG,TH,AU,MY',
			),
			'PLG' => array(
				'name'             => esc_html__( 'Parcel International Direct Goods', 'dhl-for-woocommerce' ),
				'origin_countries' => 'CN,HK',
			),
			'PLD' => array(
				'name'             => esc_html__( 'Parcel International Standard', 'dhl-for-woocommerce' ),
				'origin_countries' => 'CN,HK,SG,AU,IN',
			),
			'PLR' => array(
				'name'             => esc_html__( 'Destination Intended Return Services', 'dhl-for-woocommerce' ),
				'origin_countries' => 'CN,HK',
			),
			'PKG' => array(
				'name'             => esc_html__( 'Packet International Economy', 'dhl-for-woocommerce' ),
				'origin_countries' => 'CN,HK,IN,TH',
			),
			'PKW' => array(
				'name'             => esc_html__( 'Parcel International Direct Semi', 'dhl-for-woocommerce' ),
				'origin_countries' => 'CN,HK',
			),
			'PLB' => array(
				'name'             => esc_html__( 'Bulky Goods Delivery', 'dhl-for-woocommerce' ),
				'origin_countries' => 'CN,HK',
			),
		);

		return $products;
	}

	public function list_dhl_products_domestic() {

		$products = array(
			'PDO' => array(
				'name'             => esc_html__( 'Parcel Domestic', 'dhl-for-woocommerce' ),
				'origin_countries' => 'TH,VN,AU,MY',
			),
			'PDE' => array(
				'name'             => esc_html__( 'Parcel Domestic Expedited', 'dhl-for-woocommerce' ),
				'origin_countries' => 'AU,VN,MY',
			), /*
			'PDR' => array(
				'name'      => esc_html__( 'Parcel Return', 'dhl-for-woocommerce' ),
				'origin_countries' => 'TH,VN,MY'
			),*/
			'SDP' => array(
				'name'             => esc_html__( 'DHL Parcel Metro', 'dhl-for-woocommerce' ),
				'origin_countries' => 'VN,TH,MY',
			),
			'ECO' => array(
				'name'             => esc_html__( 'Parcel Economy Delivery', 'dhl-for-woocommerce' ),
				'origin_countries' => 'TH',
			),
		);

		return $products;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function get_dhl_label( $args ) {

		$order_id = isset( $args['order_details']['order_id'] )
			? $args['order_details']['order_id']
			: null;

		$uom             = get_option( 'woocommerce_weight_unit' );
		$label_format    = $args['dhl_settings']['label_format'];
		$is_cross_border = PR_DHL()->is_crossborder_shipment( $args['shipping_address'] );
		try {
			$item_info = new Item_Info( $args, $uom, $is_cross_border );
		} catch ( Exception $e ) {
			throw $e;
		}

		// Create the shipping label

		$label_info = $this->api_client->create_label( $item_info );

		$label_pdf_data = ( $label_format == 'ZPL' ) ? $label_info->content : base64_decode( $label_info->content );
		$shipment_id    = $label_info->shipmentID;
		$this->save_dhl_label_file( 'item', $shipment_id, $label_pdf_data );

		return array(
			'label_path'      => $this->get_dhl_label_file_info( 'item', $shipment_id )->path,
			'shipment_id'     => $shipment_id,
			'tracking_number' => $shipment_id,
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
			throw new Exception( esc_html__( 'DHL Label has no path!', 'dhl-for-woocommerce' ) );
		}
		$shipment_id = $label_info['shipment_id'];
		$response    = $this->api_client->delete_label( $shipment_id );

		$label_path = $label_info['label_path'];

		if ( file_exists( $label_path ) ) {
			if ( ! is_writable( $label_path ) ) {
				throw new Exception( esc_html__( 'DHL Label file is not writable!', 'dhl-for-woocommerce' ) );
			}
			wp_delete_file( $label_path );
		} else {
			throw new Exception( esc_html__( 'DHL Label could not be deleted!', 'dhl-for-woocommerce' ) );
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function close_out_shipment( $shipment_ids = array() ) {

		$response = $this->api_client->close_out_labels( $this->country_code, $shipment_ids );

		$return = array();

		if ( isset( $response->handoverID ) ) {
			$return['handover_id'] = $response->handoverID;
		}

		if ( isset( $response->handoverNote ) && ! empty( $response->handoverNote ) ) {
			$data                = base64_decode( $response->handoverNote );
			$return['file_info'] = $this->save_dhl_label_file( 'closeout', $response->handoverID, $data );
		}

		if ( isset( $response->responseStatus->messageDetails ) ) {

			foreach ( $response->responseStatus->messageDetails as $msg ) {

				if ( isset( $msg->messageDetail ) ) {
					$return['message'] = $msg->messageDetail;
				}
			}
		}

		return $return;
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
		return sprintf( 'dhl-label-%s.%s', $barcode, $format );
	}

	/**
	 * Retrieves the filename for DHL closeout label file.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $barcode The DHL closeout id.
	 * @param string $format The file format.
	 *
	 * @return string
	 */
	public function get_dhl_close_out_label_file_name( $handover_id, $format = 'pdf' ) {
		return sprintf( 'dhl-closeout-%s.%s', $handover_id, $format );
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
		$file_name = $this->get_dhl_item_label_file_name( $barcode, $format );

		return (object) array(
			'path' => PR_DHL()->get_dhl_label_folder_dir() . $file_name,
			'url'  => PR_DHL()->get_dhl_label_folder_url() . $file_name,
		);
	}

	/**
	 * Retrieves the file info for a DHL close out label file.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $barcode The DHL item barcode.
	 * @param string $format The file format.
	 *
	 * @return object An object containing the file "path" and "url" strings.
	 */
	public function get_dhl_close_out_label_file_info( $handover_id, $format = 'pdf' ) {
		$file_name = $this->get_dhl_close_out_label_file_name( $handover_id, $format );

		return (object) array(
			'path' => PR_DHL()->get_dhl_label_folder_dir() . $file_name,
			'url'  => PR_DHL()->get_dhl_label_folder_url() . $file_name,
		);
	}

	/**
	 * Retrieves the file info for any DHL label file, based on type.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $type The label type: "item" or "order".
	 * @param string $key The key: barcode for type "item", and order ID for type "order".
	 *
	 * @return object An object containing the file "path" and "url" strings.
	 */
	public function get_dhl_label_file_info( $type, $key ) {

		if ( $type == 'closeout' ) {
			return $this->get_dhl_close_out_label_file_info( $key, 'pdf' );
		}

		$label_format = strtolower( $this->get_setting( 'dhl_label_format' ) );
		// Return info for "item" type
		return $this->get_dhl_item_label_file_info( $key, $label_format );
	}

	/**
	 * Saves an item label file.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $type The label type: "item", or "order".
	 * @param string $key The key: barcode for type "item", and order ID for type "order".
	 * @param string $data The label file data.
	 *
	 * @return object The info for the saved label file, containing the "path" and "url".
	 *
	 * @throws Exception If failed to save the label file.
	 */
	public function save_dhl_label_file( $type, $key, $data ) {
		// Get the file info based on type
		$file_info = $this->get_dhl_label_file_info( $type, $key );

		// Validate all file path including windows path
		if ( validate_file( $file_info->path ) > 0 && validate_file( $file_info->path ) !== 2 ) {
			throw new Exception( esc_html__( 'Invalid file path!', 'dhl-for-woocommerce' ) );
		}

		$file_ret = file_put_contents( $file_info->path, $data );

		// global $wp_filesystem;

		// // Initialize WP_Filesystem
		// if ( ! function_exists( 'WP_Filesystem' ) ) {
		// require_once ABSPATH . 'wp-admin/includes/file.php';
		// }

		// WP_Filesystem();

		// // Check if WP_Filesystem object is properly initialized
		// if ( empty( $wp_filesystem ) ) {
		// throw new Exception( esc_html__( 'DHL label file cannot be saved due to WP Filesystem initialization failure!', 'dhl-for-woocommerce' ) );
		// }

		// // Write the data to the file using WP_Filesystem
		// $file_ret = $wp_filesystem->put_contents( $file_info->path, $data, FS_CHMOD_FILE );

		if ( empty( $file_ret ) ) {
			throw new Exception( esc_html__( 'DHL label file cannot be saved!', 'dhl-for-woocommerce' ) );
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
	public function delete_dhl_label_file( $type, $key ) {
		// Get the file info based on type
		$file_info = $this->get_dhl_label_file_info( $type, $key );

		// Do nothing if file does not exist
		if ( ! file_exists( $file_info->path ) ) {
			return;
		}

		if ( ! is_writable( $file_info->path ) ) {
			return;
		}

		wp_delete_file( $file_info->path );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function dhl_validate_field( $key, $value ) {
	}
}
