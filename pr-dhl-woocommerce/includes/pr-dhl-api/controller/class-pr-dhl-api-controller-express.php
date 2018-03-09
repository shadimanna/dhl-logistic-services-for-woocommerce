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
		
		$germany_int =  array( 
								'V53WPAK' => __('DHL Express International', 'pr-shipping-dhl'),
								'V55PAK' => __('DHL Express Connect', 'pr-shipping-dhl')
								);

		$austria_int = array(  
							'V87PARCEL' => __('DHL Express Connect', 'pr-shipping-dhl'),
							'V82PARCEL' => __('DHL Express International', 'pr-shipping-dhl')
							);

		$dhl_prod_int = array();

		switch ($country_code) {
			case 'DE':
				$dhl_prod_int = $germany_int;
				break;
			case 'AT':
				$dhl_prod_int = $austria_int;
				break;
			default:
				$dhl_prod_int = $germany_int;
				break;
		}

		return $dhl_prod_int;
	}

	public function get_dhl_products_domestic() {
		$country_code = $this->country_code;

		$germany_dom = array(  
								'V01PAK' => __('DHL Express', 'pr-shipping-dhl'),
								'V01PRIO' => __('DHL Express PRIO', 'pr-shipping-dhl'),
								'V06PAK' => __('DHL Express Taggleich', 'pr-shipping-dhl')
								);

		$austria_dom = array( 'V86PARCEL' => __('DHL Express Austria', 'pr-shipping-dhl') );

		$dhl_prod_dom = array();

		switch ($country_code) {
			case 'DE':
				$dhl_prod_dom = $germany_dom;
				break;
			case 'AT':
				$dhl_prod_dom = $austria_dom;
				break;
			default:
				$dhl_prod_dom = $germany_dom;
				break;
		}

		return $dhl_prod_dom;
	}

	public function get_dhl_duties() {
		$duties = parent::get_dhl_duties();

		$duties_paket = array(
					'DXV' => __('Delivery Duty Paid (excl. VAT )', 'pr-shipping-dhl'),
					'DDX' => __('Delivery Duty Paid (excl. Duties, taxes and VAT)', 'pr-shipping-dhl')
					);
		$duties += $duties_paket;

		return $duties;
	}
}
