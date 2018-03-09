<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


// Singleton API connection class
abstract class PR_DHL_API_Auth_SOAP {


	/**
	 * define Auth API endpoint
	 */
	// const PR_DHL_WSDL_LINK = 'https://cig.dhl.de/cig-wsdls/com/dpdhl/wsdl/geschaeftskundenversand-api/2.2/geschaeftskundenversand-api-2.2.wsdl';
	// const PR_DHL_HEADER_LINK = 'http://dhl.de/webservice/cisbase';
	// protected $pr_dhl_wsdl_link = '';
	/**
	 * @var string
	 */
	// private $client_id;

	/**
	 * @var string
	 */
	// private $client_secret;

	/**
	 * @var PR_DHL_API_Auth_SOAP
	 */
	// private static $_instance; //The single instance
	private static $_instances; // The instances array
	

	/**
	 * constructor.
	 */
	private function __construct( ) { }

	// Magic method clone is empty to prevent duplication of connection
	private function __clone() { }
   	
   	// Stopping unserialize of object
	private function __wakeup() { }

	public static function get_instance( ) {

		$class = get_called_class();
		// error_log($class);
        if ( ! isset(self::$_instances[$class] ) ) {
            self::$_instances[$class] = new static();
        }
        return self::$_instances[$class];

        /*
		if( (! self::$_instance ) ) { 
			self::$_instance = new self( );
		}

		return self::$_instance;
		*/
	}

	public function get_access_token( $client_id, $client_secret ) {
		// error_log('get access token');
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;

		if( empty( $this->client_id ) || empty( $this->client_secret ) ) {
			throw new Exception( __('The "Username" or "Password" is empty.','pr-shipping-dhl' ) );
		}

		PR_DHL()->log_msg( 'Authorize User - Client ID: ' . $this->client_id );
		PR_DHL()->log_msg( 'Authorize User - Client Secret: ' . $this->client_secret );
		
		if ( ! class_exists( 'SoapClient' ) || ! class_exists( 'SoapHeader' ) ) {
			throw new Exception( __( 'This plugin requires the <a href="http://php.net/manual/en/class.soapclient.php">SOAP</a> support on your server/hosting to function.', 'pr-shipping-dhl' ) );
		}

		try {
			
			$api_cred = PR_DHL()->get_api_url();
			// error_log(print_r($api_cred,true));
			$soap_variables = $this->get_soap_variables( $api_cred );
			// error_log(print_r($soap_variables,true));
			
			$soap_wsdl = $this->get_soap_wsdl();
			// error_log(print_r($soap_wsdl,true));

			$soap_client = new SoapClient( $soap_wsdl, $soap_variables );

	        $soap_auth_header = $this->get_soap_header( $client_id, $client_secret );
			// error_log(print_r($soap_auth_header,true));
	        
	        $soap_client->__setSoapHeaders( $soap_auth_header );
			// error_log(print_r($soap_client,true));
			
			return $soap_client;

		} catch ( Exception $e ) {
			throw $e;
		}
		
		
	}

	protected function get_soap_variables( $api_cred )	{
		return array( 	
					'trace' => true
				);
	}

	abstract protected function get_soap_header( $client_id, $client_secret );
	
	abstract protected function get_soap_wsdl();
/*
	public function is_key_match( $client_id, $client_secret ) {

		if( ( $this->client_id == $client_id ) && ($this->client_secret == $client_secret) ) {
			return true;
		} else {
			return false;
		}
	}*/
}
