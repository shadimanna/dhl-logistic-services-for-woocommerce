<?php

namespace PR\DHL\REST_API\Paket;

use Exception;
use PR\DHL\REST_API\API_Client;
use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;
use PR\DHL\Utils\Args_Parser;
use stdClass;

/**
 * The API client for DHL Paket.
 *
 * @since [*next-version*]
 */
class Client extends API_Client {

	/**
	 * The DHL Customer Portal Username
	 *
	 */
	protected $customer_portal_user;

	/**
	 * The DHL Customer Portal Password
	 *
	 */
	protected $customer_portal_password;

	/**
	 * The language of the message
	 *
	 */
	protected $language = 'en';

	/**
	 * The version of the message
	 *
	 */
	protected $version = '1';

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 *
	 * @param string $customer_portal_user, $customer_portal_password The customer username and password
	 */
	public function __construct( $customer_portal_user, $customer_portal_password, $base_url, API_Driver_Interface $driver, API_Auth_Interface $auth = null ) {
		parent::__construct( $base_url, $driver, $auth );

		$this->customer_portal_user = $customer_portal_user;
		$this->customer_portal_password = $customer_portal_password;
	}

	/**
	 * Create pickup_request
	 *
	 * @since [*next-version*]
	 *
	 * @param class $pickup_request_info Pickup_Request_Info
	 *
	 */
	public function create_pickup_request ( Pickup_Request_Info $pickup_request_info, $blnIncludeBillingNumber = false ){

		$route 	= $this->request_pickup_route();

		//Customer business portal user auth
		$headers = array(
			'DPDHL-User-Authentication-Token' => base64_encode( $this->customer_portal_user . ':' . $this->customer_portal_password )
		);

		$data = $this->request_pickup_info_to_request_data( $pickup_request_info, $blnIncludeBillingNumber );

		$response = $this->post($route, $data, $headers);
		$response_body = $response->body;

		if ( $response->status === 200 ) {

			if ( isset( $response->body->orderNumber) ) {

				return $response->body;

			} elseif ( isset( $response->body[0]->code) ) {

				throw new Exception(
					sprintf(
						__( 'Failed DHL Request Pickup: %s', 'dhl-for-woocommerce' ),
						$this->generate_error_details( $response->body )
					)
				);
			}

		} elseif ( $response->status >= 400 && $response->status <= 499  ) {

			$error_msg = 'HTTP status: '. $response->status;

			if ( $response->status == 400 ) {
				$error_msg .= ' Bad Request. ';
			} elseif ( $response->status == 401 ) {
				$error_msg .= ' Authentication failed. Wrong credentials. ';
			} elseif ( $response->status == 402 ) {
				$error_msg .= ' Payment failed. ';
			} elseif ( $response->status == 403 ) {
				$error_msg .= ' Authentication failed. Insufficient priviledges. ';
			}

			throw new Exception(
				sprintf(
					__( 'Failed DHL Request Pickup: %s', 'dhl-for-woocommerce' ),
					$this->generate_error_details( $error_msg . $response->body )
				)
			);
		}

		throw new Exception(
			sprintf(
				__( 'Failed DHL Request Pickup: %s', 'dhl-for-woocommerce' ),
				$this->generate_error_details( $response->body )
			)
		);
	}

	/**
	 * Create get pickup locations request
	 *
	 * @since [*next-version*]
	 *
	 *
	 */
	public function get_pickup_location( $zipCode = '' ){

		$route 	= $this->get_pickup_location_route();

		//Customer business portal user auth
		$headers = array(
			'DPDHL-User-Authentication-Token' => base64_encode( $this->customer_portal_user . ':' . $this->customer_portal_password )
		);

		$data = [];
		$data = ['zipCode' => $zipCode];

		$response = $this->get($route, $data, $headers);

		$response_body = $response->body;

		if ( $response->status === 200 ) {

			return $response->body;

		} elseif ( $response->status >= 400 && $response->status <= 499  ) {

			$error_msg = 'HTTP status: '. $response->status;

			if ( $response->status == 400 ) {
				$error_msg .= ' Bad Request. ';
			} elseif ( $response->status == 401 ) {
				$error_msg .= ' Authentication failed. Wrong credentials. ';
			} elseif ( $response->status == 402 ) {
				$error_msg .= ' Payment failed. ';
			} elseif ( $response->status == 403 ) {
				$error_msg .= ' Authentication failed. Insufficient priviledges. ';
			}

			throw new Exception(
				sprintf(
					__( 'Failed DHL Request Pickup: %s', 'dhl-for-woocommerce' ),
					$this->generate_error_details( $error_msg . $response->body )
				)
			);
		}

		throw new Exception(
			sprintf(
				__( 'Failed DHL Request Pickup: %s', 'dhl-for-woocommerce' ),
				$this->generate_error_details( $response->body )
			)
		);
	}

	public function generate_error_details( $body ){

		$error_details 	= '';

		if ( is_string( $body) ) {
			$error_details = $body;
		} elseif (is_array($body) ) {
			$error_details = print_r($body, true);
		} elseif ( is_object($body) ) {
			$error_details = print_r($body, true);
		}


		if( !empty( $error_details ) ){

			$error_exception .= '<ul class = "wc_dhl_error">' . $error_details . '</ul>';

		} else {
			$error_exception .= __( 'Error message detail is not exist!', 'dhl-for-woocommerce' );
		}

		return $error_exception;
	}

	/**
	 * Get date time.
	 *
	 * @return string The date and time of the message.
	 */
	protected function get_datetime(){
		return date( 'c', time() );
	}

	/**
	 * Get message language.
	 *
	 * @return string The language of the message.
	 */
	protected function get_language(){
		return $this->language;
	}

	/**
 * Get message version.
 *
 * @return string The version of the message.
 */
    protected function get_version(){
        return $this->version;
    }

	/**
	 * Transforms an item info object into a request data array.
	 *
	 * @param Item_Info $item_info The item info object to transform.
	 *
	 * @return array The request data for the given item info object.
	 */
	protected function request_pickup_info_to_request_data( Pickup_Request_Info $request_pickup_info, $blnIncludeBillingNumber ) {

		//Pickup date
		if ( $request_pickup_info->pickup_details['dhl_pickup_type'] == 'date' ) { //date or asap
			$pickup_info_array = array(
				'type' 		=> 'Date',
				'value' 	=> $request_pickup_info->pickup_details['dhl_pickup_date']
			);
		} else {
			$pickup_info_array = array(
				'type' 		=> 'ASAP'
			);
		}

		//Pickup location & business hours
		$pickup_location_array = array(
			'pickupAddress' 	=> array(
				'name1' 		=> $request_pickup_info->pickup_contact['name'],
				'nativeAddress' 	=> $request_pickup_info->pickup_address
			)
		);
		$business_hours = null;
		if ( $request_pickup_info->business_hours ) {
			$pickup_location_array['businessHours']	= $request_pickup_info->business_hours;
		}

		$request_data = array(
			'pickupLocation' 	=> $pickup_location_array,
			'pickupDetails'	  => array(
				'pickupDate' 	=> $pickup_info_array,
				'emailNotification'	=> '',
				'totalWeight'		=> $request_pickup_info->pickup_details['weight'],
				'comment'			=> '',
			),
			'shipmentDetails'	  => array(
				'shipments' 	=> $request_pickup_info->shipments
			),
			'contactPerson'	  => array(
				'name' 	=> $request_pickup_info->pickup_contact['name'],
				'phone' 	=> $request_pickup_info->pickup_contact['phone'],
				'email' 	=> $request_pickup_info->pickup_contact['email'],
			)
		);

		// Include customer details billing number (if excluded, we're forcing DHL to look for existing Pickup address in customers portal)
		if ( $blnIncludeBillingNumber ) {
			$request_data['customerDetails']  = array(
				'billingNumber' 	=> $request_pickup_info->customer_details['billingNumber'],
			);
		}

        return $request_data;
	}



	/**
	 * Prepares an API route with the customer namespace and EKP.
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 */
	protected function request_pickup_route() {
		return 'pickup360/order';
	}

	/**
	 * Prepares an API route with the customer namespace and EKP.
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 */
	protected function get_pickup_location_route() {
		return 'pickup360/pickuplocation';
	}


}
