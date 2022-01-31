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
	const API_URL_PRODUCTION = 'https://api.dhl.com/';

	public function __construct() {}

	public function get_parcel_location( $args ) {

		$soap_request = $this->set_message();

		try {

			$this->set_arguments( $args );

			$this->set_endpoint( 'location-finder/v1/find-by-address' );
			$this->set_query_string();

			// PR_DHL()->log_msg( '"get_parcel_location" called with: ' . print_r( $args , true ) );

			$response_body = $this->get_request();

			PR_DHL()->log_msg( 'Response: Successful');

			return $response_body;
		} catch (Exception $e) {
			PR_DHL()->log_msg( 'Response Error: ' . $e->getMessage() );
			throw $e;
		}
	}

	protected function set_arguments( $args ) {
		// Validate set args

		if ( empty( $args['dhl_settings']['api_user'] ) ) {
			throw new Exception( __('Please, provide the username in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['api_pwd'] )) {
			throw new Exception( __('Please, provide the password for the username in the DHL shipping settings', 'dhl-for-woocommerce') );
		}

		if ( empty( $args['shipping_address']['city'] ) && empty( $args['shipping_address']['postcode'] ) ) {
			throw new Exception( __('Shipping "City" and "Postcode" are empty!', 'dhl-for-woocommerce') );
		}

		if ( empty( $args['shipping_address']['country'] )) {
			throw new Exception( __('Shipping "Country" is empty!', 'dhl-for-woocommerce') );
		}

		$this->args = $args;
	}

	protected function set_query_string() {

		$finder_query_string = array(
			'radius' => 2000, //in meters
			'limit' => ($args['dhl_parcel_limit']) ? $args['dhl_parcel_limit'] : 10,
			'serviceType'		=> 'parcel:pick-up',
			'streetAddress' 	=> $this->args['shipping_address']['address'],
			'zipCode'			=> $this->args['shipping_address']['postcode'],
			'addressLocality' 	=> $this->args['shipping_address']['city'],
			'countryCode' 		=> $this->args['shipping_address']['country']
		);

		$this->query_string = http_build_query($finder_query_string);
	}

	protected function set_header( $authorization = '' ) {
		$dhl_header['Accept'] = 'application/json';
		//$dhl_header['X-EKP'] = $this->args['account_num'];
		$dhl_header['DHL-API-Key'] = '64zQ1dGifbYPb1CGr0xaXmxeaoAjDgil';

		if ( !empty( $authorization ) ) {
			$dhl_header['Authorization'] = $authorization;
		}

		$this->remote_header = $dhl_header;
	}

	public function get_request() {

		$rest_auth = '';
		$api_url = $this->get_api_url();
		if ( is_array( $api_url ) ) {
			$rest_auth = $this->get_basic_auth_encode( $api_url['user'], $api_url['password'] );
			$api_url = str_replace('/soap', '/rest', $api_url['auth_url'] );
		}

		$this->set_header( $rest_auth );

		$wp_request_url = $api_url . $this->get_endpoint() . '?' . $this->get_query_string();
		$wp_request_headers = $this->get_header();

		PR_DHL()->log_msg( 'GET URL: ' . $wp_request_url );

		$wp_dhl_rest_response = wp_remote_get(
		    $wp_request_url,
		    array( 'headers' => $wp_request_headers,
		    		'timeout' => self::WP_POST_TIMEOUT
		    	)
		);

		PR_DHL()->log_msg( 'GET Request Headers: ' . print_r( $wp_request_headers, true ) );

		$response_code = wp_remote_retrieve_response_code( $wp_dhl_rest_response );
		$response_body = json_decode( wp_remote_retrieve_body( $wp_dhl_rest_response ) );

		PR_DHL()->log_msg( 'GET Response Code: ' . $response_code );
		PR_DHL()->log_msg( 'GET Response Body: ' . print_r( $response_body, true ) );

		switch ( $response_code ) {
			case '200':
			case '201':
				break;
			case '400':
				$error_message = ($response_body->detail) ? $response_body->detail : '';
				throw new Exception( __('400 - ', 'dhl-for-woocommerce') . $error_message );
				break;
			case '401':
				throw new Exception( __('401 - Unauthorized Access - Invalid token or Authentication Header parameter', 'dhl-for-woocommerce') );
				break;
			case '408':
				throw new Exception( __('408 - Request Timeout', 'dhl-for-woocommerce') );
				break;
			case '429':
				throw new Exception( __('429 - Too many requests in given amount of time', 'dhl-for-woocommerce') );
				break;
			case '503':
				throw new Exception( __('503 - Service Unavailable', 'dhl-for-woocommerce') );
				break;
			default:
				if ( empty($response_body->detail) ) {
					$error_message = __('GET error or timeout occured. Please try again later.', 'dhl-for-woocommerce');
				} else {
					$error_message = str_replace('/', ' / ', $response_body->detail);
				}

				PR_DHL()->log_msg( 'GET Error: ' . $response_code . ' - ' . $error_message );

				throw new Exception( $response_code .' - ' . $error_message );
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
	 *
	 * @throws Exception If failed to determine if using the sandbox API or not.
	 */
	public function get_api_url() {
		$api_url = self::API_URL_PRODUCTION;
		return $api_url;
	}

	/*
	protected function set_message() {
		if( ! empty( $this->args ) ) {

			$shipping_address = implode(' ', $this->args['shipping_address']);

			$dhl_label_body =
				array(
					'Version' =>
						array(
								'majorRelease' => '2',
								'minorRelease' => '2'
						),
					'address' => $shipping_address,
					'countrycode' => $this->args['shipping_address']['country']
				);

			// Unset/remove any items that are empty strings or 0, even if required!
			$this->body_request = $this->walk_recursive_remove( $dhl_label_body );

			return $this->body_request;
		}

	}
	*/
}
