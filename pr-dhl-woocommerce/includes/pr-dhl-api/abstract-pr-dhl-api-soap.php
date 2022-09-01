<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


abstract class PR_DHL_API_SOAP {

	/**
	 * Passed arguments to the API
	 *
	 * @var string
	 */
	protected $args = array();

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
	 * @var array
	 */
	protected $body_request = array();

	/**
	 * DHL_Api constructor.
	 *
	 * @param string $api_key, $api_secret
	 */
	public function __construct( $wsdl_link ) {

		try {

			$this->dhl_soap_auth = new PR_DHL_API_Auth_SOAP( $wsdl_link );
			
		} catch (Exception $e) {
			throw $e;
		}
	}

	public function get_access_token( $client_id, $client_secret ) {
		return $this->dhl_soap_auth->get_access_token( $client_id, $client_secret );
	}

	protected function maybe_convert_weight( $weight, $UoM ) {
		$weight = floatval( wc_format_decimal( $weight ) );

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
		return round( $weight, 2 );
	}

	protected function maybe_convert_centimeters( $dimension, $UoM ) {
		$dimension = floatval( wc_format_decimal( $dimension ) );

		switch ( $UoM ) {
			case 'm':
				$dimension = $dimension * 100;
				break;
			case 'mm':
				$dimension = $dimension / 10;
				break;
			case 'in':
				$dimension = $dimension / 2.54;
				break;
			case 'yd':
				$dimension = $dimension / 91.44;
				break;
			default:
				break;
		}
		return round( $dimension, 2 );
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
						throw new Exception( sprintf( __('The value must be %s characters.', 'dhl-for-woocommerce'), $min_len) );
					} else {
						throw new Exception( sprintf( __('The value must be between %s and %s characters.', 'dhl-for-woocommerce'), $min_len, $max_len ) );
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
}
