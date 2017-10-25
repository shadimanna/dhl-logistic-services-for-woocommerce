<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

abstract class PR_DHL_API {

	protected $dhl_label;

	protected $country_code;

	// abstract public function set_dhl_auth( $client_id, $client_secret );
	
	public function is_dhl_paket( ) {
		return false;
	}

	public function is_dhl_ecomm( ) {
		return false;
	}

	public function get_dhl_label( $args ) {
		return $this->dhl_label->get_dhl_label( $args );
	}

	public function delete_dhl_label( $label_url ) {
		return $this->dhl_label->delete_dhl_label( $label_url );
	}

	abstract public function get_dhl_products_international();

	abstract public function get_dhl_products_domestic();

	public function get_dhl_content_indicator( ) {
		return array();
	}

	public function dhl_test_connection( $client_id, $client_secret ) {
		return $this->dhl_label->dhl_test_connection( $client_id, $client_secret );
	}

	public function dhl_validate_field( $key, $value ) {
		return $this->dhl_label->dhl_validate_field( $key, $value );
	}

	public function get_dhl_preferred_day( $cutoff_time, $working_days ) {
		return array();
	}

	public function get_dhl_preferred_time() {
		return array();	
	}

	public function get_dhl_duties() {
		$duties = array(
					'DDU' => __('Delivery Duty Unpaid', 'pr-shipping-dhl'),
					'DDP' => __('Delivery Duty Paid', 'pr-shipping-dhl')
					);
		return $duties;
	}

	public function get_dhl_visual_age() {
		return array();	
	}
}
