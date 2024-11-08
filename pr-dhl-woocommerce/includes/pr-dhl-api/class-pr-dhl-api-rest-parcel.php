<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class PR_DHL_API_REST_Parcel extends PR_DHL_API_REST {

	// const PR_DHL_AUTO_CLOSE = '1';

	private $args = array();

	public function __construct() {}

	public function get_dhl_parcel_services( $args ) {
		// curl -X GET --header 'Accept: application/json' --header 'X-EKP: 2222222222' 'https://cig.dhl.de/services/sandbox/rest/checkout/28757/availableServices?startDate=2018-08-17'

		$this->set_arguments( $args );
		$this->set_endpoint( '/checkout/' . $args['postcode'] . '/availableServices' );
		$this->set_query_string();

		return $this->get_request();
	}

	protected function set_arguments( $args ) {
		// Validate set args

		if ( empty( $args['account_num'] ) ) {
			throw new Exception( esc_html__( 'Please, provide an account in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['postcode'] ) ) {
			throw new Exception( esc_html__( 'Please, provide the receiver postnumber.', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['start_date'] ) ) {
			throw new Exception( esc_html__( 'Please, provide the shipment start date.', 'dhl-for-woocommerce' ) );
		}

		$this->args = $args;
	}

	protected function set_query_string() {
		// 2018-08-17
		$dhl_label_query_string = array( 'startDate' => $this->args['start_date'] );

		$this->query_string = http_build_query( $dhl_label_query_string );
	}

	protected function get_query_string() {
		return $this->query_string;
	}

	protected function set_header( $authorization = '' ) {
		$dhl_header['Accept'] = 'application/json';
		$dhl_header['X-EKP']  = $this->args['account_num'];

		if ( ! empty( $authorization ) ) {
			$dhl_header['Authorization'] = $authorization;
		}

		$this->remote_header = $dhl_header;
	}
}
