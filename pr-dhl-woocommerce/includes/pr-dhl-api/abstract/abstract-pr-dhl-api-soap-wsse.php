<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


abstract class PR_DHL_API_SOAP_WSSE implements PR_DHL_API_Base {

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

			$this->dhl_soap_auth = PR_DHL_API_Model_Auth_SOAP_WSSE::get_instance( );
			// error_log(print_r($this->dhl_soap_auth,true));

		} catch (Exception $e) {
			throw $e;
		}
	}

	public function dhl_test_connection( $client_id, $client_secret ) {
		return $this->get_access_token( $client_id, $client_secret );
	}

	public function dhl_validate_field( $key, $value ) {
		$this->validate_field( $key, $value );
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
	
	protected function maybe_convert_weight( $weight, $UoM ) {
		switch ( $UoM ) {
			case 'g':
				$weight = $weight / 1000;
				break;
			case 'lb':
				$weight = $weight / 2.2;
				break;
			case 'oz':
				$weight = $weight / 35.274;
				break;
			default:
				break;
		}
		return $weight;
	}

	protected function maybe_convert_dimension( $dimension, $UoM ) {
		switch ( $UoM ) {
			case 'm':
				$dimension = $dimension  / 100;
				break;
			case 'mm':
				$dimension = $dimension / .1;
				break;
			case 'in':
				$dimension = $dimension / 2.54;
			case 'yd':
				$dimension = $dimension / 91.44;
				break;
			default:
				break;
		}
		return $dimension;
	}

	// Unset/remove any items that are empty strings or 0
	protected function walk_recursive_remove( array $array ) { 
	    foreach ($array as $k => $v) { 
	        if (is_array($v)) { 
	            $array[$k] = $this->walk_recursive_remove($v); 
	        } 
            
            if ( empty( $v ) ) { 
                unset($array[$k]); 
            } 
	        
	    }
	    return $array; 
	} 	
	// abstract protected function parse_response( );
}
