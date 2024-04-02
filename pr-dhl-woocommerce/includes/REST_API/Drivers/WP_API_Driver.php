<?php

namespace PR\DHL\REST_API\Drivers;

use PR\DHL\REST_API\Interfaces\API_Driver_Interface;
use PR\DHL\REST_API\Request;
use PR\DHL\REST_API\Response;
use RuntimeException;

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
    // Set request timeout to 30 seconds, default of 5 is to small
    const WP_REQUEST_TIMEOUT = 30;
	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function send( Request $request ) {
		// Send the request
		$response = wp_remote_request(
			$request->url,
			array(
				'method'     => $this->get_request_type( $request ),
				'body'       => $request->body,
				'headers'    => $request->headers,
				'cookies'    => $request->cookies,
				'timeout'    => self::WP_REQUEST_TIMEOUT,
				'user-agent' => 'WooCommerce/'. WC_VERSION . ' (WordPress/'. get_bloginfo('version') . ') DHL-plug-in/' . PR_DHL_VERSION,
			)
		);

		// Check if an error occurred
		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( __( $response->get_error_message() ) );
		}

		// Retrieve the headers from the response
		$raw_headers = wp_remote_retrieve_headers( $response );
		$headers = array();

		// Ensures that the headers use the correct casing
		foreach ( $raw_headers as $header => $value ) {
			// 1. split the header name by dashes
			$header_parts = explode( '-', $header );
			// 2. upper-case the first letter of each part
			$uc_parts = array_map( 'ucfirst', $header_parts );
			// 3. combine the parts back again using dashes
			$fixed_header = implode( '-', $uc_parts );

			$headers[ $fixed_header ] = $value;
		}

		// Create and return the response object
		return new Response(
			$request,
			wp_remote_retrieve_response_code( $response ),
			wp_remote_retrieve_body( $response ),
			$headers,
			wp_remote_retrieve_cookies( $response )
		);
	}

	/**
	 * Get Request type.
	 *
	 * @param  Request  $request.
	 *
	 * @return string.
	 */
	public function get_request_type( Request $request ) {
		if ( $request->type === Request::TYPE_GET ) {
			return 'GET';
		}

		if ( $request->type === Request::TYPE_DELETE ) {
			return 'DELETE';
		}

		return 'POST';
	}
}
