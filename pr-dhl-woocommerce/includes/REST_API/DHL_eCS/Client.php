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
	public function get_shipping_label($orderId = null)
	{
		$current = get_option( 'pr_dhl_ecs_label', $this->get_default_label_info() );

		if (empty($orderId)) {
			return $current;
		}

		return get_option( 'pr_dhl_ecs_label_' . $orderId, $current );
	}

	/**
	 * Create shipping label
	 *
	 * @since [*next-version*]
	 *
	 * @param int $order_id The order id.
	 *
	 */
	public function create_shipping_label( $order_id ){

		$route 	= $this->shipping_label_route();
		$data 	= $this->get_shipping_label( $order_id );
		
		$response = $this->post($route, $data);
		error_log( print_r( $response, true ) );
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

		$label = $this->get_shipping_label();

		$label['labelRequest']['bd']['pickupAccountId'] = $settings['dhl_pickup_id'];
		$label['labelRequest']['bd']['soldToAccountId'] = $settings['dhl_soldto_id'];

		update_option( 'pr_dhl_ecs_label', $label );
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

		$pickup_address =  array(
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

		$label = $this->get_shipping_label();

		$label['labelRequest']['bd']['pickupAddress'] = $pickup_address;
		$label['labelRequest']['bd']['pickupAddress'] = null; //testing
		update_option( 'pr_dhl_ecs_label', $label );


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

		$shipper_address =  array(
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

		$label = $this->get_shipping_label();

		$label['labelRequest']['bd']['shipperAddress'] = $shipper_address;
		$label['labelRequest']['bd']['shipperAddress'] = null; //testing
		update_option( 'pr_dhl_ecs_label', $label );

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

		$label = $this->get_shipping_label();

		$label['labelRequest']['bd']['shipmentItems' ][] = $item_info->item;

		update_option( 'pr_dhl_ecs_label', $label );

	}

	/**
	 * Update the token to the current DHL shipping label.
	 *
	 * @since [*next-version*]
	 *
	 */
	public function update_access_token(){

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
