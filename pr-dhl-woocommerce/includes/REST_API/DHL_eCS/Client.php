<?php

namespace PR\DHL\REST_API\DHL_eCS;

use Exception;
use PR\DHL\REST_API\API_Client;
use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;
use stdClass;

/**
 * The API client for DHL eCS.
 *
 * @since [*next-version*]
 */
class Client extends API_Client {

	/**
	 * The api auth.
	 *
	 * @since [*next-version*]
	 *
	 * @var API_Auth_Interface
	 */
	protected $auth;

	/**
	 * The pickup address data.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	protected $pickup_address;

	/**
	 * The shipper address data.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	protected $shipper_address;

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 *
	 * @param string $contact_name The contact name to use for creating orders.
	 */
	public function __construct( $base_url, API_Driver_Interface $driver, API_Auth_Interface $auth = null ) {
		parent::__construct( $base_url, $driver, $auth );

		$this->auth 		= $auth;
	}

	/**
	 * Creates the DHL eCS order for the current local order of items.
	 *
	 * @since [*next-version*]
	 *
	 * @return object The response data.
	 *
	 * @throws Exception
	 */
	public function get_shipping_label_x(){

		$token 	= $this->auth->load_token();
		$order = $this->get_order();
		$items = $order['items'];
		$barcodes = array_keys( $items );

		$route = $this->shipping_label_route();

		$data = array( 'labelRequest' => array() );
		
		/* Header */
		$header = array(
			'messageType' 		=> 'LABEL',
			'messageDateTime' 	=> date( "c", time() ),
			'messageVersion' 	=> '1.4',
			'accessToken'		=> $token->token,
			'messageLanguage' 	=> 'en'
		);

		/* Body */
		$body = array(
			'pickupAccountId' 	=> '',
			'soldToAccountId'	=> '',
			'shipmentItems' 	=> array(
				array(
					"consigneeAddress" 	=> array(
						"name" 		=> "",
						"address1" 	=> "",
						"address2" 	=> "",
						"city" 		=> "",
						"state" 	=> "",
						"district" 	=> "",
						"country" 	=> "",
						"postCode" 	=> "",
						"phone"		=> "",
						"email" 	=> "",
						"idNumber" 	=> "",
						"idType" 	=> ""
					),
					"returnAddress" 	=> null
				),
				"shipmentID" 				=> "",
				"deliveryConfirmationNo" 	=> "",
				"packageDesc" 				=> "",
				"totalWeight" 				=> "",
				"totalWeightUOM" 			=> "G",
				"dimensionUOM" 				=> "cm",
				"height" 					=> "",
				"length" 					=> "",
				"width" 					=> "",
				"productCode" 				=> "PDO",
				"incoTerm" 					=> "",
				"totalValue" 				=> "",
				"currency" 					=> "",
				"isMult"					=> "true",
				"deliveryOption"			=> "P",
				"shipmentPieces" 			=> array(
					array(
						"pieceID" 			=> 11,
						"announcedWeight" 	=> array(
							"weight" 	=> null,
							"unit" 		=> null
						),
						"codAmount" 		=> 5,
						"insuranceAmount" 	=> null,
						"billingReference1"	=> "123",
						"billingReference2" => "123",
						"pieceDescription"	=> "Air Conditioner"
					),
					array(
						"pieceID" 			=> 11,
						"announcedWeight" 	=> array(
							"weight" 	=> null,
							"unit" 		=> null
						),
						"codAmount" 		=> 5,
						"insuranceAmount" 	=> null,
						"billingReference1"	=> "123",
						"billingReference2" => "123",
						"pieceDescription"	=> "Air Conditioner"
					),
				)
			),
			'label' 			=> array(
				'format' 	=> 'PDF',
				'layout' 	=> '1x1',
				'pageSize' 	=> '400x600'
			)
		);

		$body['pickupAddress'] 		= $this->pickup_address;
		$body['shipperAddress'] 	= $this->shipper_address;


		$data['labelRequest']['hdr'] 	= $header;
		$data['labelRequest']['bd'] 	= $body;

		$response = $this->post($route, $data);

		if ( $response->status === 200 ) {
			
			return $response->body;

		}

		throw new Exception(
			sprintf(
				__( 'Failed to create order: %s', 'pr-shipping-dhl' ),
				implode( ', ', $response->body->messages )
			)
		);
	}

	/**
	 * Creates an item on the remote API.
	 *
	 * @since [*next-version*]
	 *
	 * @param Item_Info $item_info The information of the item to be created.
	 *
	 * @return stdClass The item information as returned by the remote API.
	 *
	 * @throws Exception
	 */
	public function create_item( Item_Info $item_info ) {

		// Prepare the request route and data
		$data = $this->item_info_to_request_data( $item_info );

	}

	/**
	 * Retrieves the label for a DHL item, by its barcode.
	 *
	 * @param string $item_barcode The barcode of the item whose label to retrieve.
	 *
	 * @return string The raw PDF data for the item's label.
	 *
	 * @throws Exception
	 */
	public function get_item_label($item_barcode)
	{
		$route = sprintf('items/%s/label', $item_barcode);

		$response = $this->get(
			$this->customer_route( $route ),
			array(),
			array(
				'Accept' => 'application/pdf'
			)
		);

		if ($response->status === 200) {
			return $response->body;
		}

		$message = ! empty( $response->body->messages )
			? implode( ', ', $response->body->messages )
			: strval( $response->body );

		throw new Exception(
			sprintf( __( 'API error: %s', 'pr-shipping-dhl' ), $message )
		);
	}

	/**
	 * Retrieves items from the remote API.
	 *
	 * @since [*next-version*]
	 *
	 * @return array The list of items.
	 *
	 * @throws Exception
	 */
	public function get_items() {
		$response = $this->get( $this->customer_route( 'items' ) );

		if ( $response->status === 200 ) {
			return (array) $response->body;
		}

		throw new Exception(
			sprintf(
				__( 'Failed to get items from the API: %s', 'pr-shipping-dhl' ),
				implode( ', ', $response->body->messages )
			)
		);
	}

	/**
	 * Retrieves the current DHL order, or an existing one if an ID is given.
	 *
	 * @since [*next-version*]
	 *
	 * @param int|null $orderId Optional DHL order ID.
	 *
	 * @return array
	 */
	public function get_order($orderId = null)
	{
		$current = get_option( 'pr_dhl_ecs_order', $this->get_default_order_info() );

		if (empty($orderId)) {
			return $current;
		}

		return get_option( 'pr_dhl_ecs_order_' . $orderId, $current );
	}

	/**
	 * Adds an item to the current DHL order.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $item_barcode The barcode of the item to add.
	 * @param string $wc_order The ID of the WooCommerce order.
	 */
	public function add_item_to_order( $item_barcode, $wc_order )
	{
		$order = $this->get_order();

		$order['items'][$item_barcode] = $wc_order;

		update_option( 'pr_dhl_ecs_order', $order );
	}

	/**
	 * Adds an item to the current DHL order.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $item_barcode The barcode of the item to add.
	 */
	public function remove_item_from_order( $item_barcode )
	{
		$order = $this->get_order();

		unset( $order['items'][$item_barcode] );

		update_option( 'pr_dhl_ecs_order', $order );
	}

	/**
	 * Resets the current order.
	 *
	 * @since [*next-version*]
	 */
	public function reset_current_order()
	{
		update_option( 'pr_dhl_ecs_order', $this->get_default_order_info() );
	}

	/**
	 * Transforms an item info object into a request data array.
	 *
	 * @param Item_Info $item_info The item info object to transform.
	 *
	 * @return array The request data for the given item info object.
	 */
	protected function item_info_to_request_data( Item_Info $item_info ) {
		$contents = array();
		foreach ( $item_info->contents as $content_info ) {
			$data = array(
				'contentPieceAmount' => $content_info[ 'qty' ],
				'contentPieceDescription' => $content_info[ 'description' ],
				'contentPieceIndexNumber' => $content_info[ 'product_id' ],
				'contentPieceNetweight' => $content_info[ 'weight' ],
				'contentPieceOrigin' => $content_info[ 'origin' ],
				'contentPieceValue' => $content_info[ 'value' ],
				'contentPieceHsCode' => trim( $content_info[ 'hs_code' ] )
			);
			// Only include HS code if it's not empty
			if ( empty( $content_info[ 'contentPieceHsCode' ] ) ) {
				unset( $data[ 'contentPieceHsCode' ] );
			}
			$contents[] = $data;
		}

		return array(
			'serviceLevel'        => 'PRIORITY',
			'product'             => $item_info->shipment[ 'product' ],
			'custRef'             => $item_info->shipment[ 'label_ref' ],
			'custRef2'            => $item_info->shipment[ 'label_ref_2' ],
			'shipmentAmount'      => $item_info->shipment[ 'value' ],
			'shipmentCurrency'    => $item_info->shipment[ 'currency' ],
			'shipmentGrossWeight' => $item_info->shipment[ 'weight' ],
			'shipmentNaturetype'  => $item_info->shipment[ 'nature_type' ],
			'recipient'           => $item_info->recipient[ 'name' ],
			'recipientPhone'      => $item_info->recipient[ 'phone' ],
			'recipientEmail'      => $item_info->recipient[ 'email' ],
			'addressLine1'        => $item_info->recipient[ 'address_1' ],
			'addressLine2'        => $item_info->recipient[ 'address_2' ],
			'city'                => $item_info->recipient[ 'city' ],
			'postalCode'          => $item_info->recipient[ 'postcode' ],
			'state'               => $item_info->recipient[ 'state' ],
			'destinationCountry'  => $item_info->recipient[ 'country' ],
			'contents'            => $contents
		);
	}

	/**
	 * @return array
	 */
	protected function get_default_order_info() {
		return array(
			'id' => null,
			'status' => null,
			'items' => array(),
			'shipments' => array(),
		);
	}

	/**
	 * @return array
	 */
	protected function get_default_label_info() {

		return array(
			'labelRequest' 	=> array(
				'hdr' 	=> array(
					'messageType' 		=> 'LABEL',
					'messageDateTime' 	=> date( "c", time() ),
					'messageVersion' 	=> '1.4',
					'accessToken'		=> '',
					'messageLanguage' 	=> 'en'
				),
				'bd' 	=> array(
					'pickupAccountId' 	=> '',
					'soldToAccountId'	=> '',
				)
			)
		);
	}

	/**
	 * Retrieves the current DHL order, or an existing one if an ID is given.
	 *
	 * @since [*next-version*]
	 *
	 * @param int|null $orderId Optional DHL order ID.
	 *
	 * @return array
	 */
	public function get_shipping_label($orderId = null)
	{
		$current = get_option( 'pr_dhl_ecs_label', $this->get_default_label_info() );

		if (empty($orderId)) {
			return $current;
		}

		return get_option( 'pr_dhl_ecs_label_' . $orderId, $current );
	}

	/**
	 * Get pickup address data from the settings
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 *
	 */
	public function get_pickup_address( $args ){

		$settings = $args[ 'dhl_settings' ];

		$this->pickup_address =  array(
			"name" 		=> $settings['dhl_contact_name'],
			"address1" 	=> $settings['dhl_address_1'],
			"address2" 	=> $settings['dhl_address_2'],
			"city" 		=> $settings['dhl_city'],
			"state" 	=> $settings['dhl_state'],
			"district" 	=> $settings['dhl_district'],
			"country" 	=> $settings['dhl_country'],
			"postCode" 	=> $settings['dhl_postcode'],
			"phone"		=> $settings['dhl_phone'],
			"email" 	=> $settings['dhl_email']	
		);


	}

	/**
	 * Get shipper address data from the settings
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 *
	 */
	public function get_shipper_address( $args ){

		$settings = $args[ 'dhl_settings' ];

		$this->shipper_address =  array(
			"name" 		=> $settings['dhl_contact_name'],
			"address1" 	=> $settings['dhl_address_1'],
			"address2" 	=> $settings['dhl_address_2'],
			"city" 		=> $settings['dhl_city'],
			"state" 	=> $settings['dhl_state'],
			"district" 	=> $settings['dhl_district'],
			"country" 	=> $settings['dhl_country'],
			"postCode" 	=> $settings['dhl_postcode'],
			"phone"		=> $settings['dhl_phone'],
			"email" 	=> $settings['dhl_email']	
		);


	}

	/**
	 * Update the token to the current DHL shipping label.
	 *
	 * @since [*next-version*]
	 *
	 */
	protected function update_access_token(){

		$token 	= $this->auth->load_token();

		$label = $this->get_shipping_label();

		$label['labelRequest']['hdr']['accessToken'] = $token->token;

		update_option( 'pr_dhl_ecs_label', $label );

	}

	/**
	 * Resets the current shipping label.
	 *
	 * @since [*next-version*]
	 */
	public function reset_current_shipping_label(){

		update_option( 'pr_dhl_ecs_label', $this->get_default_label_info() );
		
	}

	/**
	 * Prepares an API route with the customer namespace and EKP.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $route The route to prepare.
	 *
	 * @return string
	 */
	protected function shipping_label_route() {
		return 'rest/v2/Label';
	}

}
