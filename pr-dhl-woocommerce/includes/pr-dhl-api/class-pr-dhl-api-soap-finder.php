<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class PR_DHL_API_SOAP_Finder extends PR_DHL_API_SOAP {

	/**
	 * WSDL definitions
	 */
	const PR_DHL_FINDER_WSDL_LINK = 'https://cig.dhl.de/cig-wsdls/com/dpdhl/wsdl/parcelshopfinder/1.0/parcelshopfinder-1.0-production.wsdl';
	const PR_DHL_FINDER_WSDL_LINK_QA = 'https://cig.dhl.de/cig-wsdls/com/dpdhl/wsdl/parcelshopfinder/1.0/parcelshopfinder-1.0-sandbox.wsdl';

	
	public function __construct( ) {
		try {

			parent::__construct( self::PR_DHL_FINDER_WSDL_LINK_QA );

		} catch (Exception $e) {
			throw $e;
		}
	}



	public function get_parcel_location( $args ) {
		$this->set_arguments( $args );
		$soap_request = $this->set_message();

		try {
			$soap_client = $this->get_access_token( $args['dhl_settings']['api_user'], $args['dhl_settings']['api_pwd'] );
			PR_DHL()->log_msg( '"getParcellocationByAddress" called with: ' . print_r( $soap_request, true ) );

			$response_body = $soap_client->getParcellocationByAddress($soap_request);
			error_log(print_r($soap_client->__getLastRequest(),true));
			error_log(print_r($soap_client->__getLastResponse(),true));
			error_log(print_r($response_body,true));

			PR_DHL()->log_msg( 'Response Body: ' . print_r( $response_body, true ) );
		
		} catch (Exception $e) {
			throw $e;
		}

		if( $response_body->Status->statusCode != 0 ) {
			throw new Exception( sprintf( __('Could not create label - %s', 'pr-shipping-dhl'), $response_body->Status->statusMessage ) );
		} else {
			return $label_tracking_info;
		}
	}

	protected function set_arguments( $args ) {
		// Validate set args
		
		if ( empty( $args['dhl_settings']['api_user'] ) ) {
			throw new Exception( __('Please, provide the username in the DHL shipping settings', 'pr-shipping-dhl' ) );
		}

		if ( empty( $args['dhl_settings']['api_pwd'] )) {
			throw new Exception( __('Please, provide the password for the username in the DHL shipping settings', 'pr-shipping-dhl') );
		}

		if ( empty( $args['shipping_address']['city'] ) && empty( $args['shipping_address']['postcode'] ) ) {
			throw new Exception( __('Shipping "City" and "Postcode" are empty!', 'pr-shipping-dhl') );
		}

		if ( empty( $args['shipping_address']['country'] )) {
			throw new Exception( __('Shipping "Country" is empty!', 'pr-shipping-dhl') );
		}

		$this->args = $args;
	}

	protected function set_message() {
		if( ! empty( $this->args ) ) {

			$dhl_label_body = 
				array(
					'Version' =>
						array(
								'majorRelease' => '2',
								'minorRelease' => '2'
						),
					'address' => 
						array( 
							'street' => $this->args['shipping_address']['address_1'],
							'streetNo' => $this->args['shipping_address']['address_2'],
							'zip' => $this->args['shipping_address']['postcode'],
							'city' => $this->args['shipping_address']['city'],
							'country' => $this->args['shipping_address']['country']
						),
				);

			// Unset/remove any items that are empty strings or 0, even if required!
			$this->body_request = $this->walk_recursive_remove( $dhl_label_body );

			return $this->body_request;
		}
		
	}
}
