<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class PR_DHL_API_REST_Finder extends PR_DHL_API_REST {

	/**
	 * The URL to the API.
	 *
	 * @since [*next-version*]
	 */
	public function __construct() {}

	public function get_parcel_location( $args ) {

		$soap_request = $this->set_message();

		try {

			$this->set_arguments( $args );

			$this->set_endpoint( 'location-finder/v1/find-by-address' );
			$this->set_query_string();

			// PR_DHL()->log_msg( '"get_parcel_location" called with: ' . print_r( $args , true ) );

			$response_body = $this->get_request();

			// PR_DHL()->log_msg( 'Response: Successful');

			return $response_body;
		} catch ( Exception $e ) {
			PR_DHL()->log_msg( 'Response Error: ' . $e->getMessage() );
			throw $e;
		}
	}

	protected function set_arguments( $args ) {
		// Validate set args

		if ( empty( $args['shipping_address']['city'] ) && empty( $args['shipping_address']['postcode'] ) ) {
			throw new Exception( esc_html__( 'Shipping "City" and "Postcode" are empty!', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['shipping_address']['country'] ) ) {
			throw new Exception( esc_html__( 'Shipping "Country" is empty!', 'dhl-for-woocommerce' ) );
		}

		$this->args = $args;
	}

	protected function set_query_string() {

		$finder_query_string = array(
			'radius'          => 2000, // in meters
			'limit'           => ( $this->args['dhl_parcel_limit'] ) ? $this->args['dhl_parcel_limit'] : 10,
			// 'serviceType'     => 'parcel:pick-up',
			'streetAddress'   => $this->args['shipping_address']['address'],
			'zipCode'         => $this->args['shipping_address']['postcode'],
			'addressLocality' => $this->args['shipping_address']['city'],
			'countryCode'     => $this->args['shipping_address']['country'],
		);

		// by serviceType
		// For Post office, Branch use parcel:pick-up
		// For Packstation (locker) use
		// parcel:pick-up-registered ==== Germany
		// parcel:pick-up-unregistered ===== Europe, excluding Germany
		if ( $this->args['dhl_packstation_filter'] == 'true' && $this->args['dhl_branch_filter'] != 'true' ) {
			if ( $this->args['shipping_address']['country'] == 'DE' ) {
				$finder_query_string['serviceType'] = 'parcel:pick-up-registered';
			} else {
				$finder_query_string['serviceType'] = 'pick-up-unregistered';
			}
		} else {
			$finder_query_string['serviceType'] = 'parcel:pick-up';
		}

		$this->query_string = http_build_query( $finder_query_string );
	}

	protected function set_header( $authorization = '' ) {
		$dhl_header['Accept']      = 'application/json';
		$dhl_header['DHL-API-Key'] = $this->get_api_key();

		if ( ! empty( $authorization ) ) {
			$dhl_header['Authorization'] = $authorization;
		}

		$this->remote_header = $dhl_header;
	}

	public function get_request() {

		$rest_auth = '';
		$api_url   = $this->get_api_url();
		$api_url   = trailingslashit( $api_url );

		$this->set_header();

		$wp_request_url     = $api_url . $this->get_endpoint() . '?' . $this->get_query_string();
		$wp_request_headers = $this->get_header();

		PR_DHL()->log_msg( 'GET URL: ' . $wp_request_url );

		$wp_dhl_rest_response = wp_remote_get(
			$wp_request_url,
			array(
				'headers' => $wp_request_headers,
				'timeout' => self::WP_POST_TIMEOUT,
			)
		);

		// PR_DHL()->log_msg( 'GET Request Headers: ' . print_r( $wp_request_headers, true ) );

		$response_code = wp_remote_retrieve_response_code( $wp_dhl_rest_response );
		$response_body = json_decode( wp_remote_retrieve_body( $wp_dhl_rest_response ) );

		PR_DHL()->log_msg( 'GET Response Code: ' . $response_code );
		// PR_DHL()->log_msg( 'GET Response Body: ' . print_r( $response_body, true ) );

		switch ( $response_code ) {
			case '200':
			case '201':
				break;
			case '400':
				$error_message = ( $response_body->detail ) ? $response_body->detail : '';
				throw new Exception( esc_html__( '400 - ', 'dhl-for-woocommerce' ) . esc_html( $error_message ) );
				break;
			case '401':
				throw new Exception( esc_html__( '401 - Unauthorized Access - Invalid token or Authentication Header parameter', 'dhl-for-woocommerce' ) );
				break;
			case '408':
				throw new Exception( esc_html__( '408 - Request Timeout', 'dhl-for-woocommerce' ) );
				break;
			case '429':
				throw new Exception( esc_html__( '429 - Too many requests in given amount of time', 'dhl-for-woocommerce' ) );
				break;
			case '503':
				throw new Exception( esc_html__( '503 - Service Unavailable', 'dhl-for-woocommerce' ) );
				break;
			default:
				if ( empty( $response_body->detail ) ) {
					$error_message = esc_html__( 'GET error or timeout occured. Please try again later.', 'dhl-for-woocommerce' );
				} else {
					$error_message = str_replace( '/', ' / ', $response_body->detail );
				}

				PR_DHL()->log_msg( 'GET Error: ' . $response_code . ' - ' . $error_message );

				throw new Exception( esc_html( $response_code ) . ' - ' . esc_html( $error_message ) );
				break;
		}

		return $response_body;
	}

	/**
	 * Retrieves the API URL.
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 */
	public function get_api_url() {
		$api_url = defined( 'PR_DHL_GLOBAL_URL' ) ? PR_DHL_GLOBAL_URL : '';
		return $api_url;
	}

	/**
	 * Retrieves the API KEY.
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 */
	public function get_api_key() {
		$api_key = defined( 'PR_DHL_GLOBAL_API' ) ? PR_DHL_GLOBAL_API : '';
		return $api_key;
	}
}
