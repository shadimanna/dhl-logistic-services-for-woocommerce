<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


// Singleton API connection class
class PR_DHL_API_Model_Auth_SOAP extends PR_DHL_API_Auth_SOAP {
	
	/**
	 * define Auth API endpoint
	 */
	const PR_DHL_WSDL_LINK = 'https://cig.dhl.de/cig-wsdls/com/dpdhl/wsdl/geschaeftskundenversand-api/2.2/geschaeftskundenversand-api-2.2.wsdl';
	const PR_DHL_HEADER_LINK = 'http://dhl.de/webservice/cisbase';

	protected function get_soap_variables( $api_cred )	{
		return array( 	
					'login' => $api_cred['user'],
					'password' => $api_cred['password'],
					'location' => $api_cred['auth_url'],
					'soap_version' => SOAP_1_1,
					'trace' => true
				);
	}

	protected function get_soap_header( $client_id, $client_secret ) {
		$soap_authentication = array(
            'user' => $client_id,
            'signature' => $client_secret,
			'type' => 0
        );
        
        $soap_auth_header = new SoapHeader( self::PR_DHL_HEADER_LINK, 'Authentification', $soap_authentication );

        return $soap_auth_header;
	}
	
	protected function get_soap_wsdl() {
		return self::PR_DHL_WSDL_LINK;
	}
}
