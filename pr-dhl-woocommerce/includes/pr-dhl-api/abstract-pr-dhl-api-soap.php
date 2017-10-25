<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


abstract class PR_DHL_API_SOAP {

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
	 * @var PR_DHL_API_Auth_SOAP
	 */
	protected $dhl_soap_auth;

	/**
	 * @var Integrater
	 */
	protected $id = '';

	/**
	 * @var string
	 */
	protected $soap_client = '';

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

			$this->dhl_soap_auth = PR_DHL_API_Auth_SOAP::get_instance( );

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
		$this->soap_client = $this->dhl_soap_auth->get_access_token( $client_id, $client_secret );
		return $this->soap_client;
	}

	protected function validate_field( $key, $value ) {

		try {

			switch ( $key ) {
				case 'pickup':
					$this->validate( $value, 'string', 14, 14 );
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
