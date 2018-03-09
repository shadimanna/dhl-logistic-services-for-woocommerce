<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


// Singleton API connection class
class PR_DHL_API_Model_Auth_SOAP_WSSE extends PR_DHL_API_Auth_SOAP {

	/**
	 * define Auth API endpoint
	 */
	const PR_DHL_WSDL_LINK = 'https://wsbexpress.dhl.com:443/sndpt/expressRateBook?WSDL';
	// const PR_DHL_HEADER_LINK = 'http://dhl.de/webservice/cisbase';

	protected function get_soap_header( $client_id, $client_secret ) {
        return new PR_DHL_API_Model_Auth_SOAP_WSSE_Header( $client_id, $client_secret );
	}

	protected function get_soap_wsdl() {
		return self::PR_DHL_WSDL_LINK;
	}
}
