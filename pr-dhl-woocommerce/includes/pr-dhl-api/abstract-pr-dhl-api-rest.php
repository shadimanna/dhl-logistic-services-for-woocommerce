<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


abstract class PR_DHL_API_REST {

	const WP_POST_TIMEOUT = 30;

	/**
	 * The request endpoint
	 *
	 * @var string
	 */
	protected $endpoint = '';

	/**
	 * The query string
	 *
	 * @var string
	 */
	private $query = array();

	/**
	 * The request response
	 * @var array
	 */
	protected $response = null;

	/**
	 * @var PR_DHL_API_Auth_REST
	 */
	protected $dhl_rest_auth;

	/**
	 * @var Integrater
	 */
	protected $id = '';

	/**
	 * @var string
	 */
	protected $token_bearer = '';

	/**
	 * @var array
	 */
	protected $remote_header = array();

	/**
	 * @var string
	 */
	protected $query_string = '';

	/**
	 * @var array
	 */
	protected $body_request = array();

	/**
	 * DHL_Api constructor.
	 *
	 * @param string $api_key, $api_secret
	 */
	public function __construct( ) {

		try {

			$this->dhl_rest_auth = PR_DHL_API_Auth_REST::get_instance( );

		} catch (Exception $e) {
			throw $e;
		}
	}

	/**
	 * Method to set id
	 *
	 * @param $id
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Get the id
	 *
	 * @return $id
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Method to set endpoint
	 *
	 * @param $endpoint
	 */
	protected function set_endpoint( $endpoint ) {
		$this->endpoint = $endpoint;
	}

	/**
	 * Get the endpoint
	 *
	 * @return String
	 */
	protected function get_endpoint() {
		return $this->endpoint;
	}

	/**
	 * @return string
	 */
	protected function get_query() {
		return $this->query;
	}


	/**
	 * @param string $query
	 */
	protected function set_query( $query ) {
		$this->query = $query;
	}

	/**
	 * Get response
	 *
	 * @return array
	 */
	public function get_response() {
		return $this->response;
	}

	/**
	 * Clear the response
	 *
	 * @return bool
	 */
	private function clear_response() {
		$this->response = null;

		return true;
	}

	public function get_access_token( $client_id, $client_secret ) {
		$this->token_bearer = $this->dhl_rest_auth->get_access_token( $client_id, $client_secret );
		return $this->token_bearer;
	}

	public function delete_access_token( ) {
		$this->dhl_rest_auth->delete_access_token();
	}

	public function post_request( $client_id, $client_secret ) {
		$this->set_header( $this->get_access_token( $client_id, $client_secret ) );

		$wp_request_url = PR_DHL()->get_api_url() . $this->get_endpoint() . '?' . $this->get_query_string();
		$wp_request_headers = $this->get_header();
		$this->set_message();
		
		$wp_request_body = $this->get_message();

		PR_DHL()->log_msg( 'POST URL: ' . $wp_request_url );
		// PR_DHL()->log_msg( 'POST Header: ' . print_r( $wp_request_headers, true ) );
		PR_DHL()->log_msg( 'POST Body: ' . $wp_request_body );

		$wp_dhl_rest_response = wp_remote_post(
		    $wp_request_url,
		    array( 'headers' => $wp_request_headers,
		    		'body' => $wp_request_body,
		    		'timeout' => self::WP_POST_TIMEOUT
		    	)
		);

		$response_code = wp_remote_retrieve_response_code( $wp_dhl_rest_response );
		$response_body = json_decode( wp_remote_retrieve_body( $wp_dhl_rest_response ) );
		$session_id = wp_remote_retrieve_header( $wp_dhl_rest_response, 'x-correlationid' );

		PR_DHL()->log_msg( 'POST Response Header Session ID: ' . $session_id );
		PR_DHL()->log_msg( 'POST Response Code: ' . $response_code );
		PR_DHL()->log_msg( 'POST Response Body: ' . print_r( $response_body, true ) );

		switch ( $response_code ) {
			case '201':
				break;
			case '400':
				$error_message = str_replace('/', ' / ', $response_body->message);
				
				if ( isset( $response_body->backendError->message ) ) {
					$error_message .= ' ' . $response_body->backendError->message;
				}

				throw new Exception( __('400 - ', 'pr-shipping-dhl') . $error_message );
				break;
			case '401':
				throw new Exception( __('401 - Unauthorized Access - Invalid token or Authentication Header parameter', 'pr-shipping-dhl') );
				break;
			case '408':
				throw new Exception( __('408 - Request Timeout', 'pr-shipping-dhl') );
				break;
			case '429':
				throw new Exception( __('429 - Too many requests in given amount of time', 'pr-shipping-dhl') );
				break;
			case '503':
				throw new Exception( __('503 - Service Unavailable', 'pr-shipping-dhl') );
				break;
			default:
				if ( empty($response_body->message) ) {
					$error_message = __('POST error or timeout occured. Please try again later.', 'pr-shipping-dhl');
				} else {
					$error_message = str_replace('/', ' / ', $response_body->message);
				}
				
				PR_DHL()->log_msg( 'POST Error: ' . $response_code . ' - ' . $error_message );

				throw new Exception( $response_code .' - ' . $error_message );
				break;
		}

		
		return $response_body;
	}

	public function get_request() {

		$rest_auth = '';
		$api_url = PR_DHL()->get_api_url();
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

		$response_code = wp_remote_retrieve_response_code( $wp_dhl_rest_response );
		$response_body = json_decode( wp_remote_retrieve_body( $wp_dhl_rest_response ) );

		PR_DHL()->log_msg( 'GET Response Code: ' . $response_code );
		PR_DHL()->log_msg( 'GET Response Body: ' . print_r( $response_body, true ) );

		switch ( $response_code ) {
			case '200':
			case '201':
				break;
			case '400':
				$error_message = str_replace('/', ' / ', $response_body->message);
				throw new Exception( __('400 - ', 'pr-shipping-dhl') . $error_message );
				break;
			case '401':
				throw new Exception( __('401 - Unauthorized Access - Invalid token or Authentication Header parameter', 'pr-shipping-dhl') );
				break;
			case '408':
				throw new Exception( __('408 - Request Timeout', 'pr-shipping-dhl') );
				break;
			case '429':
				throw new Exception( __('429 - Too many requests in given amount of time', 'pr-shipping-dhl') );
				break;
			case '503':
				throw new Exception( __('503 - Service Unavailable', 'pr-shipping-dhl') );
				break;
			default:
				if ( empty($response_body->message) ) {
					$error_message = __('GET error or timeout occured. Please try again later.', 'pr-shipping-dhl');
				} else {
					$error_message = str_replace('/', ' / ', $response_body->message);
				}
				
				PR_DHL()->log_msg( 'GET Error: ' . $response_code . ' - ' . $error_message );

				throw new Exception( $response_code .' - ' . $error_message );
				break;
		}

		
		return $response_body;
	}

	protected function get_basic_auth_encode( $user, $pass ) {
		return 'Basic ' . base64_encode( $user . ':' . $pass );
	}

	protected function set_header( $authorization = '' ) {
		$wp_version = get_bloginfo('version');

		$dhl_header['Content-Type'] = 'application/json';
		$dhl_header['Accept'] = 'application/json';
		$dhl_header['Authorization'] = 'Bearer ' . $authorization;
		$dhl_header['User-Agent'] = 'WooCommerce/'. WC_VERSION . ' (WordPress/'. $wp_version . ') DHL-plug-in/' . PR_DHL_VERSION;

		$this->remote_header = array_merge($this->remote_header, $dhl_header);
	}

	protected function get_header( ) {
		return $this->remote_header;
	}

	abstract protected function set_query_string( );

	protected function get_query_string( ) {
		return $this->query_string;
	}

	protected function set_message( ) { }

	protected function get_message( ) {
		return $this->body_request;
	}

	protected function validate_field( $key, $value ) {

		try {

			switch ( $key ) {
				case 'pickup':
					$this->validate( $value, 'string', 5, 10 );
					break;
				case 'distribution':
					$this->validate( $value, 'string', 6, 6 );
					break;
			}
			
		} catch (Exception $e) {
			throw $e;
		}

	}

	protected function validate( $value, $type = 'int', $min_len = 0, $max_len = 0 ) {

		switch ( $type ) {
			case 'string':
				if( ( strlen($value) < $min_len ) || ( strlen($value) > $max_len ) ) {
					if ( $min_len == $max_len ) {
						throw new Exception( sprintf( __('The value must be %s characters.', 'pr-shipping-dhl'), $min_len) );
					} else {
						throw new Exception( sprintf( __('The value must be between %s and %s characters.', 'pr-shipping-dhl'), $min_len, $max_len ) );
					}
				}
				break;
			case 'int':
				if( ! is_numeric( $value ) ) {
					throw new Exception( __('The value must be a number') );
				}
				break;
		}
	}

	// abstract protected function parse_response( );
}
