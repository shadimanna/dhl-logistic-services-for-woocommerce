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

	/**
	 * Checkout the shopping cart and retrieve the voucher PDF.
	 *
	 * Endpoint verified against the live API: POST /app/shoppingcart/pdf?directCheckout=true
	 * (the previous /orders path does not exist on this API). The JSON_API_Driver
	 * JSON-encodes the payload and decodes the response.
	 *
	 * @param  array $payload ShoppingCartPDFRequest payload (pageFormatId, positions, total).
	 * @return object         Decoded CheckoutShoppingCartPDFResponse (link, shoppingCart, walletBalance).
	 * @throws Exception      On non-2xx HTTP status.
	 */
	public function create_label( array $payload ) {
		$response = $this->post( 'app/shoppingcart/pdf?directCheckout=true', $payload );

		$status = (int) $response->status;
		if ( 200 !== $status && 201 !== $status ) {
			throw new Exception( $this->extract_error_message( $response->body, $status, 'checkout' ) );
		}

		return $response->body;
	}

	protected function extract_error_message( $body, $status, $operation ) {
		if ( is_string( $body ) ) {
			$decoded = json_decode( $body );
		} else {
			$decoded = $body;
		}

		// INTERNETMARKE errors carry a machine-readable `title` plus a `description`.
		if ( ! empty( $decoded->title ) ) {
			$friendly = $this->friendly_error( $decoded->title );
			if ( '' !== $friendly ) {
				return $friendly;
			}

			$description = ! empty( $decoded->description ) ? ' (' . sanitize_text_field( $decoded->description ) . ')' : '';
			return sanitize_text_field( $decoded->title ) . $description;
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

	/**
	 * Map known INTERNETMARKE error titles to a human-readable message.
	 *
	 * @param  string $title The `title` field from the API error body.
	 * @return string        A translated message, or '' if the title is unmapped.
	 */
	protected function friendly_error( $title ) {
		switch ( $title ) {
			case 'walletBalanceNotEnough':
				return esc_html__( 'Not enough balance in your Portokasse to buy this label. Please top up your Portokasse account and try again.', 'dhl-for-woocommerce' );
			default:
				return '';
		}
	}
}
