<?php

namespace PR\DHL\REST_API\DHL_eCS_Asia;

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
	 * The label info.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	protected $label_info;

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

		$this->label_info   = $this->get_default_label_info();
	}

	/**
	 * Get Default value for the label info 
	 * 
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
					'customerAccountId' => null,
					'pickupDateTime' 	=> null,
					'inlineLabelReturn' => null,
					'handoverMethod' 	=> null,
					
					'label' 			=> array(
						'format' 	=> 'PDF',
						'layout' 	=> '1x1',
						'pageSize' 	=> '400x600'
					)
					
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
	public function get_shipping_label($orderId = null){
		$current = $this->label_info;

		return $current;
	}

	/**
	 * Create shipping label
	 *
	 * @since [*next-version*]
	 *
	 * @param int $order_id The order id.
	 *
	 */
	public function create_shipping_label(){

		$route 	= $this->shipping_label_route();
		$data 	= $this->get_shipping_label();
		
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
	 * Update accountID
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 *
	 */
	public function update_account_id( $args ){

		$settings = $args[ 'dhl_settings' ];

		$label = $this->label_info;

		$label['labelRequest']['bd']['pickupAccountId'] = $settings['pickup_id'];
		$label['labelRequest']['bd']['soldToAccountId'] = $settings['soldto_id'];

		$this->label_info = $label;
	}

	/**
	 * Update accountID
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 *
	 */
	public function update_label_info( $args ){

		$settings = $args[ 'dhl_settings' ];

		$label = $this->label_info;

		$label['labelRequest']['bd']['label']['format'] = $settings['label_format'];
		$label['labelRequest']['bd']['label']['layout'] = $settings['label_layout'];

		$this->label_info = $label;
	}

	/**
	 * Update pickup address data from the settings
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 *
	 */
	public function update_pickup_address( $args ){

		$settings = $args[ 'dhl_settings' ];

		if( empty( $settings['dhl_address_1'] ) || 
			empty( $settings['dhl_address_2'] ) || 
			empty( $settings['dhl_city'] ) || 
			empty( $settings['dhl_state'] ) || 
			empty( $settings['dhl_district'] ) || 
			empty( $settings['dhl_country'] ) || 
			empty( $settings['dhl_postcode'] ) )
		{
			return;
		}

		$pickup_address = array(
			"name" 		=> $settings['dhl_contact_name'],
			"address1" 	=> $settings['dhl_address_1'],
			"address2" 	=> $settings['dhl_address_2'],
			"city" 		=> $settings['dhl_city'],
			"state" 	=> $settings['dhl_state'],
			"district" 	=> $settings['dhl_district'],
			"country" 	=> $settings['dhl_country'],
			"postCode" 	=> $settings['dhl_postcode'],
		);

		if( !empty( $settings['dhl_phone'] ) ){
			$pickup_address['phone'] = $settings['dhl_phone'];
		}

		if( !empty( $settings['dhl_email'] ) ){
			$pickup_address['email'] = $settings['dhl_email'];
		}

		$label = $this->label_info;

		$label['labelRequest']['bd']['pickupAddress'] = $pickup_address;
		//$label['labelRequest']['bd']['pickupAddress'] = null; //testing

		$this->label_info = $label;

	}

	/**
	 * Update shipper address data from the settings
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 *
	 */
	public function update_shipper_address( $args ){

		$settings = $args[ 'dhl_settings' ];

		if( empty( $settings['dhl_address_1'] ) || 
			empty( $settings['dhl_address_2'] ) || 
			empty( $settings['dhl_city'] ) || 
			empty( $settings['dhl_state'] ) || 
			empty( $settings['dhl_district'] ) || 
			empty( $settings['dhl_country'] ) || 
			empty( $settings['dhl_postcode'] ) )
		{
			return;
		}

		$shipper_address = array(
			"name" 		=> $settings['dhl_contact_name'],
			"address1" 	=> $settings['dhl_address_1'],
			"address2" 	=> $settings['dhl_address_2'],
			"city" 		=> $settings['dhl_city'],
			"state" 	=> $settings['dhl_state'],
			"district" 	=> $settings['dhl_district'],
			"country" 	=> $settings['dhl_country'],
			"postCode" 	=> $settings['dhl_postcode'],
		);

		if( !empty( $settings['dhl_phone'] ) ){
			$shipper_address['phone'] = $settings['dhl_phone'];
		}

		if( !empty( $settings['dhl_email'] ) ){
			$shipper_address['email'] = $settings['dhl_email'];
		}

		$label = $this->label_info;

		$label['labelRequest']['bd']['shipperAddress'] = $shipper_address;
		//$label['labelRequest']['bd']['shipperAddress'] = null; //testing
		$this->label_info = $label;

	}

	/**
	 * Add an item to the current.
	 *
	 * @since [*next-version*]
	 *
	 * @param Item_Info $item_info The information of the item to be created.
	 *
	 * @return stdClass The item information as returned by the remote API.
	 *
	 */
	public function add_item( Item_Info $item_info ) {

		$label = $this->label_info;

		$label['labelRequest']['bd']['shipmentItems' ][] = $item_info->item;

		$this->label_info = $label;

	}

	/**
	 * Update the token to the current DHL shipping label.
	 *
	 * @since [*next-version*]
	 *
	 */
	public function update_access_token(){

		$token 	= $this->auth->load_token();

		$label = $this->label_info;

		$label['labelRequest']['hdr']['accessToken'] = $token->token;

		$this->label_info = $label;

	}

	/**
	 * Resets the current shipping label.
	 *
	 * @since [*next-version*]
	 */
	public function reset_current_shipping_label(){

		$this->label_info = $this->get_default_label_info();

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
