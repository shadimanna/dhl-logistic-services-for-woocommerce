<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class PR_DHL_API_Factory {

	public static function init() {
		// Load abstract classes
		include_once( 'abstract-pr-dhl-api-rest.php' );
		include_once( 'abstract-pr-dhl-api-soap.php' );
		include_once( 'abstract-pr-dhl-api.php' );

		// Load interfaces
		include_once( 'interface-pr-dhl-api-label.php' );
	}

	public static function make_dhl( $country_code ) {
		PR_DHL_API_Factory::init();

		$dhl_obj = null;

		try {
			switch ($country_code) {
				case 'US':
				case 'GU':
				case 'AS':
				case 'PR':
				case 'UM':
				case 'VI':
				case 'CL':
				case 'CA':
				case 'SG':
				case 'HK':
				case 'TH':
				case 'JP':
				case 'CN':
				case 'MY':
				case 'VN':
				case 'AU':
				case 'IL':
				case 'NZ':
				case 'TW':
				case 'KR':
				case 'PH':
				case 'IN':
					$dhl_obj = new PR_DHL_API_Ecomm( $country_code);
					break;
				case 'DE':
				case 'AT':
					$dhl_obj = new PR_DHL_API_Paket( $country_code );
					break;
				default:
					throw new Exception( __('The DHL plugin is not supported in your store\'s "Base Location"', 'pr-shipping-dhl') );
			}
		} catch (Exception $e) {
			throw $e;
		}

		return $dhl_obj;
	}
}
