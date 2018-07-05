<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class PR_DHL_API_Controller_Express extends PR_DHL_API {

	public function __construct( $country_code ) {
		$this->country_code = $country_code;
		try {
			$this->dhl_label = new PR_DHL_API_Model_SOAP_WSSE_Label( );
			$this->dhl_rate = new PR_DHL_API_Model_SOAP_WSSE_Rate( );
		} catch (Exception $e) {
			throw $e;	
		}
	}

	
	public function get_dhl_products_international() {
		$country_code = $this->country_code;
		
		$dhl_prod_int =  array( 
								'E' => __('EXPRESS 9:00', 'pr-shipping-dhl'),
								'K' => __('EXPRESS 9:00', 'pr-shipping-dhl'),
								'M' => __('EXPRESS 10:30', 'pr-shipping-dhl'),
								'T' => __('EXPRESS 12:00', 'pr-shipping-dhl'),
								'Y' => __('EXPRESS 12:00', 'pr-shipping-dhl'),
								'P' => __('EXPRESS WORLDWIDE', 'pr-shipping-dhl'),
								'U' => __('EXPRESS WORLDWIDE', 'pr-shipping-dhl'),
								'H' => __('ECONOMY SELECT', 'pr-shipping-dhl'),
								'W' => __('ECONOMY SELECT', 'pr-shipping-dhl'),
								);
		return $dhl_prod_int;
	}

	public function get_dhl_products_domestic() {
		$country_code = $this->country_code;

		$dhl_prod_dom = array(  
								'N' => __('DOMESTIC EXPRESS', 'pr-shipping-dhl'),
								'I' => __('DOMESTIC EXPRESS 9:00', 'pr-shipping-dhl'),
								'1' => __('DOMESTIC EXPRESS 12:00', 'pr-shipping-dhl'),
								'O' => __('DOMESTIC EXPRESS 10:30', 'pr-shipping-dhl'),
								'G' => __('DOMESTIC ECONOMY SELECT', 'pr-shipping-dhl'),
								);
		
		return $dhl_prod_dom;
	}

	public function get_dhl_duties() {
		$duties = array(
					'DAP' => __('Delivery At Paid', 'pr-shipping-dhl'),
					'DDP' => __('Delivery Duty Paid', 'pr-shipping-dhl')
					);
		return $duties;
	}
}
