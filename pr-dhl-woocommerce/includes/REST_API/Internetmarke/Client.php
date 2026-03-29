<?php

namespace PR\DHL\REST_API\Internetmarke;

use Exception;
use PR\DHL\REST_API\API_Client;
use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;

/**
 * API client for Deutsche Post INTERNETMARKE.
 */
class Client extends API_Client {
	public function __construct( $base_url, API_Driver_Interface $driver, API_Auth_Interface $auth = null ) {
		parent::__construct( $base_url, $driver, $auth );
	}

	public function get_api_info() {
		$response = $this->get( '/' );

		if ( 200 !== (int) $response->status ) {
			throw new Exception( $this->extract_error_message( $response->body, $response->status, 'health' ) );
		}

		return $response->body;
	}

	public function get_profile() {
		$response = $this->get( 'user/profile' );

		if ( 200 !== (int) $response->status ) {
			throw new Exception( $this->extract_error_message( $response->body, $response->status, 'profile' ) );
		}

		return $response->body;
	}

	/**
	 * Create a label order (POST /orders).
	 *
	 * The JSON_API_Driver automatically JSON-encodes the payload and decodes the response.
	 *
	 * @param  array $payload Order payload including positions, pageFormat, etc.
	 * @return object         Decoded response body.
	 * @throws Exception      On non-2xx HTTP status.
	 */
	public function create_label( array $payload ) {
		$response = $this->post( 'orders', $payload );

		$status = (int) $response->status;
		if ( 200 !== $status && 201 !== $status ) {
			throw new Exception( $this->extract_error_message( $response->body, $status, 'order' ) );
		}

		return $response->body;
	}

	protected function extract_error_message( $body, $status, $operation ) {
		if ( is_string( $body ) ) {
			$decoded = json_decode( $body );
		} else {
			$decoded = $body;
		}

		if ( ! empty( $decoded->detail ) ) {
			return sanitize_text_field( $decoded->detail );
		}

		if ( ! empty( $decoded->message ) ) {
			return sanitize_text_field( $decoded->message );
		}

		if ( 401 === (int) $status ) {
			return esc_html__( 'Authorization failed. Check the INTERNETMARKE credentials and confirm the business application in Portokasse if this is the first API use.', 'dhl-for-woocommerce' );
		}

		return sprintf(
			/* translators: 1: operation name, 2: HTTP status code. */
			esc_html__( 'INTERNETMARKE %1$s request failed with HTTP %2$d.', 'dhl-for-woocommerce' ),
			sanitize_text_field( $operation ),
			absint( $status )
		);
	}
}
