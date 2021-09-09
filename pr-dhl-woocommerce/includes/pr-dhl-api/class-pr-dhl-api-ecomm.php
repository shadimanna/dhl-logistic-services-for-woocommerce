<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class PR_DHL_API_Ecomm extends PR_DHL_API {

	public function __construct( $country_code ) {
		$this->country_code = $country_code;
		try {
			$this->dhl_label = new PR_DHL_API_REST_Label(  );
		} catch (Exception $e) {
			throw $e;	
		}
	}

	public function is_dhl_ecomm( ) {
		return true;
	}
	
	public function dhl_reset_connection( ) {
		return $this->dhl_label->delete_access_token( );
	}

	public function get_dhl_products_international() {
		$country_code = $this->country_code;

		$americas_int =  array( 
								// 'PKD' => __('DHL GlobalMail Packet Standard', 'dhl-for-woocommerce'),
								'PKY' => __('DHL Packet International', 'dhl-for-woocommerce'),
								// 'PKT' => __('DHL GlobalMail Packet Plus', 'dhl-for-woocommerce'),
								'PLY' => __('DHL Parcel International Standard', 'dhl-for-woocommerce'),
								'PLT' => __('DHL Parcel International Direct', 'dhl-for-woocommerce'),
								// 'PLX' => __('DHL Parcel International Expedited', 'dhl-for-woocommerce'),
								/*'PID' => __('DHL Parcel International Direct Standard', 'dhl-for-woocommerce'),
								'BMY' => __('DHL GobalMail Business Priority', 'dhl-for-woocommerce'),
								'BMD' => __('DHL GobalMail Business Standard', 'dhl-for-woocommerce'),
								'PIY' => __('DHL Parcel International Direct Priority', 'dhl-for-woocommerce')
								'43' => __('DHL GM Business Canada Post Lettermail', 'dhl-for-woocommerce'),
								'41' => __('DHL GM Business IPA', 'dhl-for-woocommerce'),
								'42' => __('DHL GM Business ISAL', 'dhl-for-woocommerce'),
								'46' => __('DHL GM Direct Canada Post Admail', 'dhl-for-woocommerce'),
								'44' => __('Workshare DHL GM Business Priority', 'dhl-for-woocommerce'),
								'45' => __('Workshare DHL GM Business Standard', 'dhl-for-woocommerce'),
								'69' => __('DHL GM Other', 'dhl-for-woocommerce'),
								'59' => __('DHL GM Parcel Canada Parcel Standard', 'dhl-for-woocommerce'),
								'51' => __('DHL GM Publication Canada Publication', 'dhl-for-woocommerce'),
								'47' => __('DHL GM Publication Priority', 'dhl-for-woocommerce'),
								'48' => __('DHL GM Publication Standard', 'dhl-for-woocommerce')*/
								);

		$asia_int = array(  
							'PKG' => __('DHL Packet International Economy', 'dhl-for-woocommerce'),
							'PKD' => __('DHL Packet International Standard', 'dhl-for-woocommerce'),
							'PKM' => __('DHL Packet International Priority Manifest', 'dhl-for-woocommerce'),
							'PPS' => __('DHL Packet Plus International Standard', 'dhl-for-woocommerce'),
							'PPM' => __('DHL Packet Plus International Priority Manifest', 'dhl-for-woocommerce'),
							'PLD' => __('DHL Parcel International Standard', 'dhl-for-woocommerce'),
							'PLT' => __('DHL Parcel International Direct', 'dhl-for-woocommerce'),
							'PLE' => __('DHL Parcel International Direct Expedited', 'dhl-for-woocommerce')
							// 'AP7' => __('GM Paket Pus Manifest Clearance', 'dhl-for-woocommerce'),
							// 'PDP' => __('GM Parcel Direct Plus', 'dhl-for-woocommerce')
							);

		$dhl_prod_int = array();

		switch ($country_code) {
			case 'US':
			case 'GU':
			case 'AS':
			case 'PR':
			case 'UM':
			case 'VI':
			case 'CA':
				$dhl_prod_int = $americas_int;
				break;
			case 'SG':
			case 'HK':
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
				$dhl_prod_int = $asia_int;
				break;
			case 'CL':
			case 'TH':
			default:
				break;
		}

		return $dhl_prod_int;
	}

	public function get_dhl_products_domestic() {
		$country_code = $this->country_code;

		$americas_dom = array(  
								// '72' => __('DHL SM Flats Expedited', 'dhl-for-woocommerce'),
								// '73' => __('DHL SM Flats Ground', 'dhl-for-woocommerce'),
								'76' => __('DHL SM BPM Expedited', 'dhl-for-woocommerce'),
								'77' => __('DHL SM BPM Ground', 'dhl-for-woocommerce'),
								'36' => __('DHL SM Parcel Plus Expedited', 'dhl-for-woocommerce'),
								'83' => __('DHL SM Parcel Plus Ground', 'dhl-for-woocommerce'),
								'81' => __('DHL SM Parcel Expedited', 'dhl-for-woocommerce'),
								'82' => __('DHL SM Parcel Ground', 'dhl-for-woocommerce'),
								'631' => __('DHL SM Parcel Expedited Max', 'dhl-for-woocommerce'),
								// '80' => __('DHL SM Media Mail Ground', 'dhl-for-woocommerce'),
								// '284' => __('DHL SM Media Mail Expedited', 'dhl-for-woocommerce'),
								// '761' => __('DHL Parcel Metro Sameday', 'dhl-for-woocommerce'),
								// '531' => __('DHL Parcel return Light', 'dhl-for-woocommerce'),
								// '491' => __('DHL Parcel return Plus', 'dhl-for-woocommerce'),
								// '532' => __('DHL Parcel return Ground', 'dhl-for-woocommerce')
								// '384' => __('SM Marketing Parcel Expedited', 'dhl-for-woocommerce'),
								// '383' => __('SM Marketing Parcel Ground', 'dhl-for-woocommerce'),
								);

		$asia_dom = array( 'PDO' => __('DHL Parcel Domestic', 'dhl-for-woocommerce') );

		$vietnam_dom = array( 'PDE' => __('DHL Parcel Domestic Expedited', 'dhl-for-woocommerce') );

		$dhl_prod_dom = array();

		switch ($country_code) {
			case 'US':
			case 'GU':
			case 'AS':
			case 'PR':
			case 'UM':
			case 'VI':
				$dhl_prod_dom = $americas_dom;
				break;
			case 'CL':
				$dhl_prod_dom = $asia_dom;
				break;
			case 'TH':
			case 'MY':
				$dhl_prod_dom = $asia_dom;
				break;
			case 'VN':
				$dhl_prod_dom = $asia_dom + $vietnam_dom;
				break;
			case 'SG':
			case 'HK':
			case 'JP':
			case 'CN':
			case 'AU':
			case 'IL':
			case 'NZ':
			case 'TW':
			case 'KR':
			case 'PH':
			default:
				break;
		}

		return $dhl_prod_dom;
	}

	public function get_dhl_content_indicator( ) {
		$country_code = $this->country_code;

		$content_indicator = array( 
									'01'=> __('Lithium Metal Contained in Equipment', 'dhl-for-woocommerce'),
									'04'=> __('Lithium-Ion Contained in Equipment', 'dhl-for-woocommerce')
								);

		$content_indicator_us = array(
										'01'=> __('Primary Contained in Equipment', 'dhl-for-woocommerce'),
										'02'=> __('Primary Packed with Equipment', 'dhl-for-woocommerce'),
										'03'=> __('Primary Stand-Alone', 'dhl-for-woocommerce'),
										'04'=> __('Secondary Contained in Equipment', 'dhl-for-woocommerce'),
										'05'=> __('Secondary Packed with Equipment', 'dhl-for-woocommerce'),
										'06'=> __('Secondary Stand-Alone', 'dhl-for-woocommerce'),
									 	'08'=> __('ORM-D (US domestic)', 'dhl-for-woocommerce'),
										'09'=> __('Small Quantity Provision (US domestic)', 'dhl-for-woocommerce'),
										'40'=> __('Limited quantities (destination Canada)', 'dhl-for-woocommerce')
								);

		$dhl_content_ind = array();

		switch ($country_code) {
			case 'US':
			case 'GU':
			case 'AS':
			case 'PR':
			case 'UM':
			case 'VI':
				$dhl_content_ind = $content_indicator_us; // Merge ('+') the arrays
				break;
			case 'CL':
			case 'TH':
			case 'SG':
			case 'HK':
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
			default:
				$dhl_content_ind = $content_indicator;
				break;
		}

		return $dhl_content_ind;
	}
	
}
