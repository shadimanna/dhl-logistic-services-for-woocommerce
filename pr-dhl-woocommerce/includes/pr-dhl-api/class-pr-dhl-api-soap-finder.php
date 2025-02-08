<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class PR_DHL_API_SOAP_Finder extends PR_DHL_API_SOAP {

	/**
	 * WSDL definitions
	 */
	const PR_DHL_FINDER_WSDL_LINK    = 'https://cig.dhl.de/cig-wsdls/com/dpdhl/wsdl/parcelshopfinder/1.0/parcelshopfinder-1.0-production.wsdl';
	const PR_DHL_FINDER_WSDL_LINK_QA = 'https://cig.dhl.de/cig-wsdls/com/dpdhl/wsdl/parcelshopfinder/1.0/parcelshopfinder-1.0-sandbox.wsdl';


	public function __construct() {
		try {

			parent::__construct( self::PR_DHL_FINDER_WSDL_LINK );

		} catch ( Exception $e ) {
			throw $e;
		}
	}



	public function get_parcel_location( $args ) {
		$this->set_arguments( $args );
		$soap_request = $this->set_message();

		try {
			$soap_client = $this->get_access_token( $args['dhl_settings']['api_user'], $args['dhl_settings']['api_pwd'] );
			PR_DHL()->log_msg( '"getParcellocationByAddress" called with: ' . print_r( $soap_request, true ) );

			$response_body = $soap_client->getParcellocationByAddress( $soap_request );

			PR_DHL()->log_msg( 'Response: Successful' );

			return $response_body;
		} catch ( Exception $e ) {
			PR_DHL()->log_msg( 'Response Error: ' . $e->getMessage() );
			throw $e;
		}
	}

	protected function set_arguments( $args ) {
		// Validate set args

		if ( empty( $args['dhl_settings']['api_user'] ) ) {
			throw new Exception( esc_html__( 'Please, provide the username in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['api_pwd'] ) ) {
			throw new Exception( esc_html__( 'Please, provide the password for the username in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['shipping_address']['city'] ) && empty( $args['shipping_address']['postcode'] ) ) {
			throw new Exception( esc_html__( 'Shipping "City" and "Postcode" are empty!', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['shipping_address']['country'] ) ) {
			throw new Exception( esc_html__( 'Shipping "Country" is empty!', 'dhl-for-woocommerce' ) );
		}

		$this->args = $args;
	}

	protected function set_message() {
		if ( ! empty( $this->args ) ) {

			$shipping_address = implode( ' ', $this->args['shipping_address'] );

			$dhl_label_body =
				array(
					'Version'     =>
						array(
							'majorRelease' => '2',
							'minorRelease' => '2',
						),
					'address'     => $shipping_address,
					'countrycode' => $this->args['shipping_address']['country'],
				);

			// Unset/remove any items that are empty strings or 0, even if required!
			$this->body_request = $this->walk_recursive_remove( $dhl_label_body );

			return $this->body_request;
		}
	}
}
