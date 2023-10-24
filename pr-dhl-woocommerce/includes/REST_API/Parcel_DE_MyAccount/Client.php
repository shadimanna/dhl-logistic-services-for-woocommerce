<?php

namespace PR\DHL\REST_API\Parcel_DE_MyAccount;

use Exception;
use PR\DHL\REST_API\API_Client;
use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;
use stdClass;

/**
 * The API client for Deutsche Post.
 *
 * @since [*next-version*]
 */
class Client extends API_Client {

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
	public function get_user() {
		// Prepare the request route and data
		$route = $this->myaccount_route();
		$lang = 'en';
		$params = array('lang' => $lang );

		// Send the request and get the response
		$response = $this->get( $route, $params );

		// Return the response body on success
		if ( $response->status === 200 ) {
			return $response->body;
		}

		// Otherwise throw an exception using the response's error messages
		$message = ! empty( $response->body->messages )
			? implode( ', ', $response->body->messages )
			: strval( $response->body );

		throw new Exception(
			sprintf( __( 'API error: %s', 'dhl-for-woocommerce' ), $message )
		);
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
	protected function myaccount_route() {
		return 'myaccount/v1/user';
	}
}
