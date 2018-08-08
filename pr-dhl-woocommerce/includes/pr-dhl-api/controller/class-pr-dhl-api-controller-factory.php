<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class PR_DHL_API_Controller_Factory {

	const DHL_API_PATH_ABSTRACT = '/includes/pr-dhl-api/abstract/';
	const DHL_API_PATH_INTERFACE = '/includes/pr-dhl-api/interface/';

	public static function init() {
		// Load interfaces
		include_once( PR_DHL_PLUGIN_DIR_PATH . self::DHL_API_PATH_INTERFACE . 'interface-pr-dhl-api-base.php' );
		include_once( PR_DHL_PLUGIN_DIR_PATH . self::DHL_API_PATH_INTERFACE . 'interface-pr-dhl-api-label.php' );
		include_once( PR_DHL_PLUGIN_DIR_PATH . self::DHL_API_PATH_INTERFACE . 'interface-pr-dhl-api-rate.php' );
		
		// Load abstract classes
		include_once( PR_DHL_PLUGIN_DIR_PATH . self::DHL_API_PATH_ABSTRACT . 'abstract-pr-dhl-api-rest.php' );
		include_once( PR_DHL_PLUGIN_DIR_PATH . self::DHL_API_PATH_ABSTRACT . 'abstract-pr-dhl-api-auth-soap.php' );
		include_once( PR_DHL_PLUGIN_DIR_PATH . self::DHL_API_PATH_ABSTRACT . 'abstract-pr-dhl-api-soap.php' );
		include_once( PR_DHL_PLUGIN_DIR_PATH . self::DHL_API_PATH_ABSTRACT . 'abstract-pr-dhl-api-soap-wsse.php' );
		include_once( PR_DHL_PLUGIN_DIR_PATH . self::DHL_API_PATH_ABSTRACT . 'abstract-pr-dhl-api.php' );
	}

	public static function make_dhl( $country_code ) {
		PR_DHL_API_Controller_Factory::init();

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
					$dhl_obj = new PR_DHL_API_Controller_Ecomm( $country_code);
					break;
				case 'DE':
				case 'AT':
					$dhl_obj = new PR_DHL_API_Controller_Paket( $country_code );
					break;
				default:
					throw new Exception( __('The DHL plugin is not supported in your store\'s "Base Location"', 'pr-shipping-dhl') );
			}
		} catch (Exception $e) {
			throw $e;
		}

		return $dhl_obj;
	}

	public static function make_dhl_express( $country_code ) {
		PR_DHL_API_Controller_Factory::init();

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
				case 'DE':
				case 'AT':
				default:
					$dhl_obj = new PR_DHL_API_Controller_Express( $country_code );
					break;
			}
		} catch (Exception $e) {
			throw $e;
		}

		return $dhl_obj;
	}
}
