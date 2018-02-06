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
								// 'PKD' => __('DHL GlobalMail Packet Standard', 'pr-shipping-dhl'),
								'PKY' => __('DHL Packet International', 'pr-shipping-dhl'),
								// 'PKT' => __('DHL GlobalMail Packet Plus', 'pr-shipping-dhl'),
								'PLY' => __('DHL Parcel International Standard', 'pr-shipping-dhl'),
								'PLT' => __('DHL Parcel International Direct', 'pr-shipping-dhl'),
								// 'PLX' => __('DHL Parcel International Expedited', 'pr-shipping-dhl'),
								/*'PID' => __('DHL Parcel International Direct Standard', 'pr-shipping-dhl'),
								'BMY' => __('DHL GobalMail Business Priority', 'pr-shipping-dhl'),
								'BMD' => __('DHL GobalMail Business Standard', 'pr-shipping-dhl'),
								'PIY' => __('DHL Parcel International Direct Priority', 'pr-shipping-dhl')
								'43' => __('DHL GM Business Canada Post Lettermail', 'pr-shipping-dhl'),
								'41' => __('DHL GM Business IPA', 'pr-shipping-dhl'),
								'42' => __('DHL GM Business ISAL', 'pr-shipping-dhl'),
								'46' => __('DHL GM Direct Canada Post Admail', 'pr-shipping-dhl'),
								'44' => __('Workshare DHL GM Business Priority', 'pr-shipping-dhl'),
								'45' => __('Workshare DHL GM Business Standard', 'pr-shipping-dhl'),
								'69' => __('DHL GM Other', 'pr-shipping-dhl'),
								'59' => __('DHL GM Parcel Canada Parcel Standard', 'pr-shipping-dhl'),
								'51' => __('DHL GM Publication Canada Publication', 'pr-shipping-dhl'),
								'47' => __('DHL GM Publication Priority', 'pr-shipping-dhl'),
								'48' => __('DHL GM Publication Standard', 'pr-shipping-dhl')*/
								);

		$asia_int = array(  
							'PKG' => __('DHL Packet International Economy', 'pr-shipping-dhl'),
							'PKD' => __('DHL Packet International Standard', 'pr-shipping-dhl'),
							'PKM' => __('DHL Packet International Priority Manifest', 'pr-shipping-dhl'),
							'PPS' => __('DHL Packet Plus International Standard', 'pr-shipping-dhl'),
							'PPM' => __('DHL Packet Plus International Priority Manifest', 'pr-shipping-dhl'),
							'PLD' => __('DHL Parcel International Standard', 'pr-shipping-dhl'),
							'PLT' => __('DHL Parcel International Direct', 'pr-shipping-dhl'),
							'PLE' => __('DHL Parcel International Direct Expedited', 'pr-shipping-dhl')
							// 'AP7' => __('GM Paket Pus Manifest Clearance', 'pr-shipping-dhl'),
							// 'PDP' => __('GM Parcel Direct Plus', 'pr-shipping-dhl')
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
								// '72' => __('DHL SM Flats Expedited', 'pr-shipping-dhl'),
								// '73' => __('DHL SM Flats Ground', 'pr-shipping-dhl'),
								'76' => __('DHL SM BPM Expedited', 'pr-shipping-dhl'),
								'77' => __('DHL SM BPM Ground', 'pr-shipping-dhl'),
								'36' => __('DHL SM Parcel Plus Expedited', 'pr-shipping-dhl'),
								'83' => __('DHL SM Parcel Plus Ground', 'pr-shipping-dhl'),
								'81' => __('DHL SM Parcel Expedited', 'pr-shipping-dhl'),
								'82' => __('DHL SM Parcel Ground', 'pr-shipping-dhl'),
								'631' => __('DHL SM Parcel Expedited Max', 'pr-shipping-dhl'),
								// '80' => __('DHL SM Media Mail Ground', 'pr-shipping-dhl'),
								// '284' => __('DHL SM Media Mail Expedited', 'pr-shipping-dhl'),
								// '761' => __('DHL Parcel Metro Sameday', 'pr-shipping-dhl'),
								// '531' => __('DHL Parcel return Light', 'pr-shipping-dhl'),
								// '491' => __('DHL Parcel return Plus', 'pr-shipping-dhl'),
								// '532' => __('DHL Parcel return Ground', 'pr-shipping-dhl')
								// '384' => __('SM Marketing Parcel Expedited', 'pr-shipping-dhl'),
								// '383' => __('SM Marketing Parcel Ground', 'pr-shipping-dhl'),
								);

		$asia_dom = array( 'PDO' => __('DHL Parcel Domestic', 'pr-shipping-dhl') );

		$vietnam_dom = array( 'PDE' => __('DHL Parcel Domestic Expedited', 'pr-shipping-dhl') );

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
									'01'=> __('Lithium Metal Contained in Equipment', 'pr-shipping-dhl'),
									'04'=> __('Lithium-Ion Contained in Equipment', 'pr-shipping-dhl')
								);

		$content_indicator_us = array(
										'01'=> __('Primary Contained in Equipment', 'pr-shipping-dhl'),
										'02'=> __('Primary Packed with Equipment', 'pr-shipping-dhl'),
										'03'=> __('Primary Stand-Alone', 'pr-shipping-dhl'),
										'04'=> __('Secondary Contained in Equipment', 'pr-shipping-dhl'),
										'05'=> __('Secondary Packed with Equipment', 'pr-shipping-dhl'),
										'06'=> __('Secondary Stand-Alone', 'pr-shipping-dhl'),
									 	'08'=> __('ORM-D (US domestic)', 'pr-shipping-dhl'),
										'09'=> __('Small Quantity Provision (US domestic)', 'pr-shipping-dhl'),
										'40'=> __('Limited quantities (destination Canada)', 'pr-shipping-dhl')
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
