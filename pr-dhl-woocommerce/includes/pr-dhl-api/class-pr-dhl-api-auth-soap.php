<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


// Singleton API connection class
class PR_DHL_API_Auth_SOAP {


	/**
	 * define Auth API endpoint
	 */
	const PR_DHL_WSDL_LINK = 'https://cig.dhl.de/cig-wsdls/com/dpdhl/wsdl/geschaeftskundenversand-api/2.2/geschaeftskundenversand-api-2.2.wsdl';
	const PR_DHL_HEADER_LINK = 'http://dhl.de/webservice/cisbase';

	/**
	 * @var string
	 */
	private $client_id;

	/**
	 * @var string
	 */
	private $client_secret;

	/**
	 * @var PR_DHL_API_Auth_SOAP
	 */
	private static $_instance; //The single instance
	

	/**
	 * constructor.
	 */
	private function __construct( ) { }

	// Magic method clone is empty to prevent duplication of connection
	private function __clone() { }
   	
   	// Stopping unserialize of object
	private function __wakeup() { }

	public static function get_instance( ) {

		if( (! self::$_instance ) ) { 
			self::$_instance = new self( );
		}

		return self::$_instance;
	}

	public function get_access_token( $client_id, $client_secret ) {
		
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;

		if( empty( $this->client_id ) || empty( $this->client_secret ) ) {
			throw new Exception( __('The "Username" or "Password" is empty.','pr-shipping-dhl' ) );
		}

		PR_DHL()->log_msg( 'Authorize User - Client ID: ' . $this->client_id );
		
		if ( ! class_exists( 'SoapClient' ) || ! class_exists( 'SoapHeader' ) ) {
			throw new Exception( __( 'This plugin requires the <a href="http://php.net/manual/en/class.soapclient.php">SOAP</a> support on your server/hosting to function.', 'pr-shipping-dhl' ) );
		}

		try {
			
			$api_cred = PR_DHL()->get_api_url();

			$soap_client = new SoapClient( self::PR_DHL_WSDL_LINK,
			array( 	
					'login' => $api_cred['user'],
					'password' => $api_cred['password'],
					'location' => $api_cred['auth_url'],
					'soap_version' => SOAP_1_1,
					'trace' => true
					)
			);

		} catch ( Exception $e ) {
			throw $e;
		}
		
		$soap_authentication = array(
            'user' => $this->client_id,
            'signature' => $this->client_secret,
			'type' => 0
        );
        
        $soap_auth_header = new SoapHeader( self::PR_DHL_HEADER_LINK, 'Authentification', $soap_authentication );
        
        $soap_client->__setSoapHeaders( $soap_auth_header );
		
		return $soap_client;
	}
/*
	public function is_key_match( $client_id, $client_secret ) {

		if( ( $this->client_id == $client_id ) && ($this->client_secret == $client_secret) ) {
			return true;
		} else {
			return false;
		}
	}*/
}
