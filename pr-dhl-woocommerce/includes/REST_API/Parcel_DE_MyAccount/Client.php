<?php

namespace PR\DHL\REST_API\Parcel_DE_MyAccount;

use Exception;
use PR\DHL\REST_API\API_Client;
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
	 * @return stdClass The item information as returned by the remote API.
	 *
	 * @throws Exception
	 */
	public function get_user() {
		// Prepare the request route and data.
		$route  = $this->myaccount_route();
		$lang   = 'en';
		$params = array( 'lang' => $lang );

		// Send the request and get the response.
		$response = $this->get( $route, $params );

		// Return the response body on success
		if ( $response->status === 200 ) {
			return $response->body;
		}

		// Otherwise throw an exception using the response's error messages.
		$message = ! empty( $response->body->messages )
			? implode( ', ', $response->body->messages )
			: strval( $response->body );

		throw new Exception(
			// Translators: %s is replaced with the error message returned from the API.
			sprintf( esc_html__( 'API error: %s', 'dhl-for-woocommerce' ), esc_html( $message ) )
		);
	}

	/**
	 * Prepares an API route with the customer namespace and EKP.
	 *
	 * @return string
	 */
	protected function myaccount_route(): string {
		return 'myaccount/v1/user';
	}
}
