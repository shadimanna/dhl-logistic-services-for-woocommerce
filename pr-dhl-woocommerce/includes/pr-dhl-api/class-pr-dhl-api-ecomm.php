<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class PR_DHL_API_Ecomm extends PR_DHL_API {

	public function __construct( $country_code ) {
		$this->country_code = $country_code;
		try {
			$this->dhl_label = new PR_DHL_API_REST_Label();
		} catch ( Exception $e ) {
			throw $e;
		}
	}

	public function is_dhl_ecomm() {
		return true;
	}

	public function dhl_reset_connection() {
		return $this->dhl_label->delete_access_token();
	}

	public function get_dhl_products_international() {
		$country_code = $this->country_code;

		$americas_int = array(
			// 'PKD' => esc_html__( 'DHL GlobalMail Packet Standard', 'dhl-for-woocommerce' ),
			'PKY' => esc_html__( 'DHL Packet International', 'dhl-for-woocommerce' ),
			// 'PKT' => esc_html__( 'DHL GlobalMail Packet Plus', 'dhl-for-woocommerce' ),
			'PLY' => esc_html__( 'DHL Parcel International Standard', 'dhl-for-woocommerce' ),
			'PLT' => esc_html__( 'DHL Parcel International Direct', 'dhl-for-woocommerce' ),
								// 'PLX' => esc_html__( 'DHL Parcel International Expedited', 'dhl-for-woocommerce' ),
								/*
								'PID' => esc_html__( 'DHL Parcel International Direct Standard', 'dhl-for-woocommerce' ),
								'BMY' => esc_html__( 'DHL GobalMail Business Priority', 'dhl-for-woocommerce' ),
								'BMD' => esc_html__( 'DHL GobalMail Business Standard', 'dhl-for-woocommerce' ),
								'PIY' => esc_html__( 'DHL Parcel International Direct Priority', 'dhl-for-woocommerce' )
								'43' => esc_html__( 'DHL GM Business Canada Post Lettermail', 'dhl-for-woocommerce' ),
								'41' => esc_html__( 'DHL GM Business IPA', 'dhl-for-woocommerce' ),
								'42' => esc_html__( 'DHL GM Business ISAL', 'dhl-for-woocommerce' ),
								'46' => esc_html__( 'DHL GM Direct Canada Post Admail', 'dhl-for-woocommerce' ),
								'44' => esc_html__( 'Workshare DHL GM Business Priority', 'dhl-for-woocommerce' ),
								'45' => esc_html__( 'Workshare DHL GM Business Standard', 'dhl-for-woocommerce' ),
								'69' => esc_html__( 'DHL GM Other', 'dhl-for-woocommerce' ),
								'59' => esc_html__( 'DHL GM Parcel Canada Parcel Standard', 'dhl-for-woocommerce' ),
								'51' => esc_html__( 'DHL GM Publication Canada Publication', 'dhl-for-woocommerce' ),
								'47' => esc_html__( 'DHL GM Publication Priority', 'dhl-for-woocommerce' ),
								'48' => esc_html__( 'DHL GM Publication Standard', 'dhl-for-woocommerce' )*/
		);

		$asia_int = array(
			'PKG' => esc_html__( 'DHL Packet International Economy', 'dhl-for-woocommerce' ),
			'PKD' => esc_html__( 'DHL Packet International Standard', 'dhl-for-woocommerce' ),
			'PKM' => esc_html__( 'DHL Packet International Priority Manifest', 'dhl-for-woocommerce' ),
			'PPS' => esc_html__( 'DHL Packet Plus International Standard', 'dhl-for-woocommerce' ),
			'PPM' => esc_html__( 'DHL Packet Plus International Priority Manifest', 'dhl-for-woocommerce' ),
			'PLD' => esc_html__( 'DHL Parcel International Standard', 'dhl-for-woocommerce' ),
			'PLT' => esc_html__( 'DHL Parcel International Direct', 'dhl-for-woocommerce' ),
			'PLE' => esc_html__( 'DHL Parcel International Direct Expedited', 'dhl-for-woocommerce' ),
			// 'AP7' => esc_html__( 'GM Paket Pus Manifest Clearance', 'dhl-for-woocommerce' ),
			// 'PDP' => esc_html__( 'GM Parcel Direct Plus', 'dhl-for-woocommerce' )
		);

		$dhl_prod_int = array();

		switch ( $country_code ) {
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
			// '72' => esc_html__( 'DHL SM Flats Expedited', 'dhl-for-woocommerce' ),
			// '73' => esc_html__( 'DHL SM Flats Ground', 'dhl-for-woocommerce' ),
			'76'  => esc_html__( 'DHL SM BPM Expedited', 'dhl-for-woocommerce' ),
			'77'  => esc_html__( 'DHL SM BPM Ground', 'dhl-for-woocommerce' ),
			'36'  => esc_html__( 'DHL SM Parcel Plus Expedited', 'dhl-for-woocommerce' ),
			'83'  => esc_html__( 'DHL SM Parcel Plus Ground', 'dhl-for-woocommerce' ),
			'81'  => esc_html__( 'DHL SM Parcel Expedited', 'dhl-for-woocommerce' ),
			'82'  => esc_html__( 'DHL SM Parcel Ground', 'dhl-for-woocommerce' ),
			'631' => esc_html__( 'DHL SM Parcel Expedited Max', 'dhl-for-woocommerce' ),
								// '80' => esc_html__( 'DHL SM Media Mail Ground', 'dhl-for-woocommerce' ),
								// '284' => esc_html__( 'DHL SM Media Mail Expedited', 'dhl-for-woocommerce' ),
								// '761' => esc_html__( 'DHL Parcel Metro Sameday', 'dhl-for-woocommerce' ),
								// '531' => esc_html__( 'DHL Parcel return Light', 'dhl-for-woocommerce' ),
								// '491' => esc_html__( 'DHL Parcel return Plus', 'dhl-for-woocommerce' ),
								// '532' => esc_html__( 'DHL Parcel return Ground', 'dhl-for-woocommerce' )
								// '384' => esc_html__( 'SM Marketing Parcel Expedited', 'dhl-for-woocommerce' ),
								// '383' => esc_html__( 'SM Marketing Parcel Ground', 'dhl-for-woocommerce' ),
		);

		$asia_dom = array( 'PDO' => esc_html__( 'DHL Parcel Domestic', 'dhl-for-woocommerce' ) );

		$vietnam_dom = array( 'PDE' => esc_html__( 'DHL Parcel Domestic Expedited', 'dhl-for-woocommerce' ) );

		$dhl_prod_dom = array();

		switch ( $country_code ) {
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

	public function get_dhl_content_indicator() {
		$country_code = $this->country_code;

		$content_indicator = array(
			'01' => esc_html__( 'Lithium Metal Contained in Equipment', 'dhl-for-woocommerce' ),
			'04' => esc_html__( 'Lithium-Ion Contained in Equipment', 'dhl-for-woocommerce' ),
		);

		$content_indicator_us = array(
			'01' => esc_html__( 'Primary Contained in Equipment', 'dhl-for-woocommerce' ),
			'02' => esc_html__( 'Primary Packed with Equipment', 'dhl-for-woocommerce' ),
			'03' => esc_html__( 'Primary Stand-Alone', 'dhl-for-woocommerce' ),
			'04' => esc_html__( 'Secondary Contained in Equipment', 'dhl-for-woocommerce' ),
			'05' => esc_html__( 'Secondary Packed with Equipment', 'dhl-for-woocommerce' ),
			'06' => esc_html__( 'Secondary Stand-Alone', 'dhl-for-woocommerce' ),
			'08' => esc_html__( 'ORM-D (US domestic)', 'dhl-for-woocommerce' ),
			'09' => esc_html__( 'Small Quantity Provision (US domestic)', 'dhl-for-woocommerce' ),
			'40' => esc_html__( 'Limited quantities (destination Canada)', 'dhl-for-woocommerce' ),
		);

		$dhl_content_ind = array();

		switch ( $country_code ) {
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
