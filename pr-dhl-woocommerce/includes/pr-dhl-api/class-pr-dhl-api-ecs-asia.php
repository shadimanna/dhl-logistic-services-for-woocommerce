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
			$this->api_auth = $this->create_api_auth();
			$this->api_client = $this->create_api_client();
		} catch ( Exception $e ) {
			throw $e;
		}
	}

    public function get_dhl_duties() {
        $duties = array(
            'DDU' => __('Duties Consignee Paid', 'pr-shipping-dhl'),
            'DDP' => __('Duties Shipper Paid', 'pr-shipping-dhl')
        );
        return $duties;
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
			'00' => __('Does not contain Lithium Batteries', 'pr-shipping-dhl' ),
			'01' => __('Lithium Batteries in item', 'pr-shipping-dhl' ),
			'02' => __('Lithium Batteries packed with item', 'pr-shipping-dhl' ),
			'03' => __('Lithium Batteries only', 'pr-shipping-dhl' ),
			'04' => __('Rechargeable Batteries in item', 'pr-shipping-dhl' ),
			'05' => __('Rechargeable Batteries packed with item', 'pr-shipping-dhl' ),
			'06' => __('Rechargeable Batteries only', 'pr-shipping-dhl' ),
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function get_dhl_products_international() {

		$country_code 	= $this->country_code;
		$products 	    = $this->list_dhl_products_international();

		$accepted_products = array();

		foreach( $products as $product_code => $product ){
			if( strpos( $product['origin_countries'],  $country_code ) !== false ){
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

		$country_code 	= $this->country_code;

		$products 	= $this->list_dhl_products_domestic();

		$accepted_products = array();

		foreach( $products as $product_code => $product ){
			if( strpos( $product['origin_countries'],  $country_code ) !== false ){
				$accepted_products[ $product_code ] = $product['name'];
			}
		}

		return $accepted_products;
	}

	public function list_dhl_products_international() {

		$products = array(
			'PPM' => array(
				'name' 	    => __( 'Packet Plus International Priority Manifest', 'pr-shipping-dhl' ),
				'origin_countries' => 'CN,HK,TH'
			),
			'PPS' => array(
				'name' 	    => __( 'Packet Plus International Standard', 'pr-shipping-dhl' ),
				'origin_countries' => 'AU,CN,HK,IL,IN,MY,SG,TH'
			),
			'PKM' => array(
				'name' 	    => __( 'Packet International Priority Manifest', 'pr-shipping-dhl' ),
				'origin_countries' => 'CN,HK,TH'
			),
			'PKD' => array(
				'name' 	    => __( 'Packet International Standard', 'pr-shipping-dhl' ),
				'origin_countries' => 'CN,TH,HK,SG,AU,IN,MY'
			),
			'PLT' => array(
				'name' 	    => __( 'Parcel International Direct Standard', 'pr-shipping-dhl' ),
				'origin_countries' => 'AU,CN,HK,IL,IN,MY,SG,TH'
			),
			'PLE' => array(
				'name' 	    => __( 'Parcel International Direct Expedited', 'pr-shipping-dhl' ),
				'origin_countries' => 'IN,CN,HK,SG,TH,AU,MY'
			),
			'PLD' => array(
				'name' 	    => __( 'Parcel International Standard', 'pr-shipping-dhl' ),
				'origin_countries' => 'CN,HK,SG,AU,IN'
			),
			'PKG' => array(
				'name' 	    => __( 'Packet International Economy', 'pr-shipping-dhl' ),
				'origin_countries' => 'CN,HK,IN,TH'
			),
			'PKW' => array(
                'name' 	    => __( 'Parcel International Direct Semi', 'pr-shipping-dhl' ),
                'origin_countries' => 'CN,HK'
            ),
		);

		return $products;
	}

	public function list_dhl_products_domestic() {

		$products = array(
			'PDO' => array(
				'name' 	    => __( 'Parcel Domestic', 'pr-shipping-dhl' ),
				'origin_countries' => 'TH,VN,AU,MY'
			),
			'PDE' => array(
				'name' 	    => __( 'Parcel Domestic Expedited', 'pr-shipping-dhl' ),
				'origin_countries' => 'AU,VN'
			),/*
			'PDR' => array(
				'name' 	    => __( 'Parcel Return', 'pr-shipping-dhl' ),
				'origin_countries' => 'TH,VN,MY'
			),*/
			'SDP' => array(
				'name' 	    => __( 'DHL Parcel Metro', 'pr-shipping-dhl' ),
				'origin_countries' => 'VN,TH,MY'
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

		$order_id = isset( $args[ 'order_details' ][ 'order_id' ] )
			? $args[ 'order_details' ][ 'order_id' ]
			: null;

		$uom 				= get_option( 'woocommerce_weight_unit' );
		$label_format 		= $args['dhl_settings']['label_format'];
        $is_cross_border 	= PR_DHL()->is_crossborder_shipment( $args['shipping_address']['country'] );
		try {
			$item_info = new Item_Info( $args, $uom, $is_cross_border );
		} catch (Exception $e) {
			throw $e;
		}
		
		// Create the shipping label

		$label_info			= $this->api_client->create_label( $item_info );

		$label_pdf_data 	= ( $label_format == 'ZPL' )? $label_info->content : base64_decode( $label_info->content );
		$shipment_id 		= $label_info->shipmentID;
		$this->save_dhl_label_file( 'item', $shipment_id, $label_pdf_data );
		
		return array(
			'label_path' 			=> $this->get_dhl_label_file_info( 'item', $shipment_id )->path,
			'shipment_id' 			=> $shipment_id,
			'tracking_number' 		=> $shipment_id,
			'tracking_status' 		=> '',
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
		$shipment_id 		= $label_info['shipment_id'];
		$label_response 	= $this->api_client->delete_label( $shipment_id );
		$label_response 	= json_decode( $label_response );
		
		$response_status 	= $label_response->deleteShipmentResp->bd->responseStatus;
		if( $response_status->code != 200 ){
			throw new Exception( 
				"Error: " . $response_status->message . "<br /> " .
				"Detail: " . $response_status->messageDetails[0]->messageDetail 
			);
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

		if ( validate_file( $file_info->path ) > 0 ) {
			throw new Exception( __( 'Invalid file path!', 'pr-shipping-dhl' ) );
		}

		$file_ret = file_put_contents( $file_info->path, $data );

		if ( empty( $file_ret ) ) {
			throw new Exception( __( 'DHL label file cannot be saved!', 'pr-shipping-dhl' ) );
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
			throw new Exception(__('DHL AWB Label could not be deleted!', 'pr-shipping-dhl'));
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function dhl_validate_field( $key, $value ) {
	}

}
