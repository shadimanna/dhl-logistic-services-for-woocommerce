<?php

namespace PR\DHL\REST_API\Drivers;

use PR\DHL\REST_API\Interfaces\API_Driver_Interface;
use PR\DHL\REST_API\Request;
use PR\DHL\REST_API\Response;
use RuntimeException;
use Traversable;

/**
 * A REST API driver that uses WordPress's `wp_remote_*()` functions to make requests to a REST API.
 *
 * This is a relatively simply driver that encapsulates WordPress' `wp_remote_*()` family of functions into a driver
 * class, which can be used by API clients to dispatch request objects to the remote resource and receive full response
 * objects.
 *
 * For more information on REST API drivers, refer to the documentation for the {@link API_Driver_Interface}.
 *
 * @since [*next-version*]
 *
 * @see API_Driver_Interface
 */
class WP_API_Driver implements API_Driver_Interface {
	/**
	 * Constructor.
	 *
	 * @since [*next-version*]
	 */
	public function __construct() {
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function send( Request $request ) {
		// Send the request and get the response
		$response = $this->wp_remote_send( $request );

		// Prepare the response before returning it
		$response = $this->prepare_response( $response );

		return $response;
	}

	/**
	 * Prepares a response before it is returned to consumers.
	 *
	 * @since [*next-version*]
	 *
	 * @param Response $response The response instance to prepare.
	 *
	 * @return Response The prepared response instance.
	 */
	protected function prepare_response( Response $response ) {
		// Fixes header casing, by:
		// 1. splitting the header name by dashes
		// 2. upper-casing the first letter of each part
		// 3. combining the parts back again using dashes
		foreach ( $response->headers as $header => $value ) {
			$header_parts = explode( '-', $header );
			$uc_parts = array_map( 'ucfirst', $header_parts );
			$fixed_header = implode( '-', $uc_parts );

			$response->headers[ $fixed_header ] = $value;
		}

		return $response;
	}

	/**
	 * Sends a request using WordPress' remote functions and retrieves the response.
	 *
	 * This method is basically a wrapper for `wp_remote_request()`, but accepts a {@link Request} argument instead
	 * of accepting a URL and an args array.
	 *
	 * @since [*next-version*]
	 *
	 * @param Request $request The request to send.
	 *
	 * @return Response The response.
	 *
	 * @throws RuntimeException If the request failed.
	 */
	protected function wp_remote_send( Request $request ) {
		$method = ( $request->type === Request::TYPE_GET ) ? 'GET' : 'POST';

		$response = wp_remote_request(
			$request->url,
			array(
				'method'  => $method,
				'body'    => $request->body,
				'headers' => $request->headers,
				'cookies' => $request->cookies,
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( __( $response->get_error_message() ) );
		}

		// Unpack the headers if they're stored in an iterator
		$headers = wp_remote_retrieve_headers( $response );
		if ( $headers instanceof Traversable ) {
			$headers = iterator_to_array( $headers );
		}

		return new Response(
			$request,
			wp_remote_retrieve_response_code( $response ),
			wp_remote_retrieve_body( $response ),
			$headers,
			wp_remote_retrieve_cookies( $response )
		);
	}
}
