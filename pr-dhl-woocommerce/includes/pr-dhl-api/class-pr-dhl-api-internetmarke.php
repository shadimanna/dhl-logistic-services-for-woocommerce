<?php

use PR\DHL\REST_API\Drivers\JSON_API_Driver;
use PR\DHL\REST_API\Drivers\WP_API_Driver;
use PR\DHL\REST_API\Internetmarke\Auth;
use PR\DHL\REST_API\Internetmarke\Client;

if ( ! defined( 'ABSPATH' ) || class_exists( 'PR_DHL_API_Internetmarke', false ) ) {
	return;
}

class PR_DHL_API_Internetmarke {
	const API_URL = 'https://api-eu.dhl.com/post/de/shipping/im/v1';

	const ACCESS_TOKEN_TRANSIENT = 'pr_dhl_internetmarke_access_token';

	protected $driver;

	protected $auth;

	protected $client;

	public function __construct() {
		$raw_driver   = new WP_API_Driver();
		$this->driver = new JSON_API_Driver( $raw_driver );

		$this->auth = new Auth(
			$raw_driver,
			static::API_URL,
			$this->get_app_client_id(),
			$this->get_app_client_secret(),
			$this->get_username(),
			$this->get_password(),
			static::ACCESS_TOKEN_TRANSIENT
		);
		$this->client = new Client( static::API_URL, $this->driver, $this->auth );
	}

	public function test_connection() {
		$this->validate_configuration();
		$this->log( 'Testing INTERNETMARKE connection.' );

		$token = $this->auth->test_connection();

		$this->auth->save_token( $token );
		$this->log( 'INTERNETMARKE connection test succeeded.' );

		return $token;
	}

	public function check_health() {
		$this->validate_configuration();
		$this->log( 'Checking INTERNETMARKE API health.' );

		$info = $this->client->get_api_info();

		$this->log( 'INTERNETMARKE API health check succeeded.' );

		return $info;
	}

	public function get_profile() {
		$this->validate_configuration();
		$this->log( 'Retrieving INTERNETMARKE profile.' );

		$profile = $this->client->get_profile();

		$this->log( 'INTERNETMARKE profile retrieval succeeded.' );

		return $profile;
	}

	public function get_profile_summary() {
		$profile        = $this->get_profile();
		$wallet_balance = $this->get_profile_value( $profile, array( 'walletBalance', 'wallet_balance' ) );
		$profile_id     = $this->get_profile_value( $profile, array( 'portokasseId', 'walletId', 'wallet_id', 'id' ) );
		$user_name      = $this->get_profile_value( $profile, array( 'userName', 'username', 'name', 'email' ) );

		if ( ! empty( $profile_id ) && (string) $profile_id !== (string) $this->get_portokasse_id() ) {
			throw new Exception( esc_html__( 'The INTERNETMARKE profile does not match the saved Portokasse ID.', 'dhl-for-woocommerce' ) );
		}

		$parts = array();

		if ( ! empty( $user_name ) ) {
			$parts[] = sprintf(
				/* translators: %s: masked username. */
				esc_html__( 'User %s verified', 'dhl-for-woocommerce' ),
				esc_html( $this->mask_value( $user_name, 2 ) )
			);
		}

		if ( ! empty( $profile_id ) ) {
			$parts[] = sprintf(
				/* translators: %s: masked Portokasse ID. */
				esc_html__( 'Portokasse %s confirmed', 'dhl-for-woocommerce' ),
				esc_html( $this->mask_value( $profile_id, 2 ) )
			);
		}

		if ( '' !== $wallet_balance && null !== $wallet_balance ) {
			$parts[] = sprintf(
				/* translators: %s: wallet balance in euro cents. */
				esc_html__( 'Wallet balance: %s cents', 'dhl-for-woocommerce' ),
				esc_html( (string) $wallet_balance )
			);
		}

		if ( empty( $parts ) ) {
			$parts[] = esc_html__( 'INTERNETMARKE profile verified.', 'dhl-for-woocommerce' );
		}

		return implode( ' — ', $parts );
	}

	public function validate_configuration() {
		if ( '' === $this->get_username() || '' === $this->get_password() ) {
			throw new Exception( esc_html__( 'Please save the INTERNETMARKE Portokasse username and password before running this action.', 'dhl-for-woocommerce' ) );
		}
	}

	protected function get_app_client_id() {
		return defined( 'PR_DHL_GLOBAL_API' ) ? PR_DHL_GLOBAL_API : '';
	}

	protected function get_app_client_secret() {
		return defined( 'PR_DHL_GLOBAL_SECRET' ) ? PR_DHL_GLOBAL_SECRET : '';
	}

	protected function get_settings() {
		$paket = get_option( 'woocommerce_pr_dhl_paket_settings', array() );
		return array(
			'internetmarke_api_user'      => isset( $paket['internetmarke_api_user'] ) ? $paket['internetmarke_api_user'] : '',
			'internetmarke_api_password'  => isset( $paket['internetmarke_api_password'] ) ? $paket['internetmarke_api_password'] : '',
			'internetmarke_portokasse_id' => isset( $paket['internetmarke_portokasse_id'] ) ? $paket['internetmarke_portokasse_id'] : '',
		);
	}

	protected function get_username() {
		$settings = $this->get_settings();
		return trim( (string) $settings['internetmarke_api_user'] );
	}

	protected function get_password() {
		$settings = $this->get_settings();
		return (string) $settings['internetmarke_api_password'];
	}

	protected function get_portokasse_id() {
		$settings = $this->get_settings();
		return trim( (string) $settings['internetmarke_portokasse_id'] );
	}

	/**
	 * Generate an INTERNETMARKE label for a WooCommerce order.
	 *
	 * @param int $order_id   WooCommerce order ID.
	 * @param int $product_id Resolved INTERNETMARKE product ID.
	 * @return array { label_url: string, tracking_number: string }
	 * @throws Exception
	 */
	public function generate_label( $order_id, $product_id ) {
		$this->validate_configuration();
		$this->log( 'Generating label for order ' . $order_id . ', product ID ' . $product_id . '.' );

		$order = wc_get_order( $order_id );
		if ( ! is_a( $order, 'WC_Order' ) ) {
			throw new Exception( esc_html__( 'Could not load the WooCommerce order for INTERNETMARKE label generation.', 'dhl-for-woocommerce' ) );
		}

		$payload = $this->build_label_payload( $order, $product_id );
		$result  = $this->client->create_label( $payload );

		$label_url       = '';
		$tracking_number = '';

		if ( is_object( $result ) ) {
			if ( ! empty( $result->link ) ) {
				$label_url = (string) $result->link;
			}
			// trackId is returned per position for registered products (EINSCHREIBEN).
			if ( ! empty( $result->positions ) && is_array( $result->positions ) ) {
				foreach ( $result->positions as $position ) {
					if ( ! empty( $position->trackId ) ) {
						$tracking_number = (string) $position->trackId;
						break;
					}
				}
			}
		}

		$this->log( 'Label generated for order ' . $order_id . '.' );

		// Omit tracking_number when empty — most products don't return a trackId.
		return array_filter(
			array(
				'label_url'       => $label_url,
				'tracking_number' => $tracking_number,
			),
			'strlen'
		);
	}

	/**
	 * Build the POST /orders payload.
	 *
	 * @param WC_Order $order
	 * @param int      $product_id
	 * @return array
	 */
	protected function build_label_payload( WC_Order $order, $product_id ) {
		return array(
			'pageFormat' => 2,
			'positions'  => array(
				array(
					'productId'     => (int) $product_id,
					'imageId'       => 0,
					'x'             => 1,
					'y'             => 1,
					'pageNo'        => 1,
					'duplexAddress' => array(
						'sender'   => $this->get_sender_address(),
						'receiver' => $this->get_recipient_address( $order ),
					),
				),
			),
		);
	}

	/**
	 * Build the sender address from Paket shipper settings with WC store address as fallback.
	 *
	 * @return array
	 */
	protected function get_sender_address() {
		$paket = get_option( 'woocommerce_pr_dhl_paket_settings', array() );

		$name   = ! empty( $paket['dhl_shipper_name'] ) ? $paket['dhl_shipper_name'] : get_bloginfo( 'name' );
		$street = ! empty( $paket['dhl_shipper_address'] ) ? $paket['dhl_shipper_address'] : '';
		$house  = ! empty( $paket['dhl_shipper_address_no'] ) ? $paket['dhl_shipper_address_no'] : '';
		$city   = ! empty( $paket['dhl_shipper_address_city'] ) ? $paket['dhl_shipper_address_city'] : '';
		$zip    = ! empty( $paket['dhl_shipper_address_zip'] ) ? $paket['dhl_shipper_address_zip'] : '';

		// Fall back to WooCommerce store address if Paket shipper fields are absent.
		if ( empty( $street ) ) {
			$parts  = $this->split_street_and_house_no( get_option( 'woocommerce_store_address', '' ) );
			$street = $parts['street'];
			$house  = $parts['house_no'];
		}

		if ( empty( $zip ) ) {
			$zip = get_option( 'woocommerce_store_postcode', '' );
		}

		if ( empty( $city ) ) {
			$city = get_option( 'woocommerce_store_city', '' );
		}

		$address = array(
			'name'    => (string) $name,
			'street'  => (string) $street,
			'houseNo' => (string) $house,
			'zip'     => (string) $zip,
			'city'    => (string) $city,
			'country' => 'DEU', // Sender is always in Germany for INTERNETMARKE.
		);

		return array_filter( $address, 'strlen' );
	}

	/**
	 * Build the recipient address from the WC order shipping address.
	 *
	 * @param WC_Order $order
	 * @return array
	 */
	protected function get_recipient_address( WC_Order $order ) {
		$shipping = $order->get_address( 'shipping' );
		$billing  = $order->get_address();

		$first_name = ! empty( $shipping['first_name'] ) ? $shipping['first_name'] : ( isset( $billing['first_name'] ) ? $billing['first_name'] : '' );
		$last_name  = ! empty( $shipping['last_name'] ) ? $shipping['last_name'] : ( isset( $billing['last_name'] ) ? $billing['last_name'] : '' );
		$company    = ! empty( $shipping['company'] ) ? $shipping['company'] : '';
		// Use company as the name field when present; otherwise use full personal name.
		$name       = ! empty( $company ) ? $company : trim( $first_name . ' ' . $last_name );

		$address_1 = ! empty( $shipping['address_1'] ) ? $shipping['address_1'] : ( isset( $billing['address_1'] ) ? $billing['address_1'] : '' );
		$address_2 = ! empty( $shipping['address_2'] ) ? $shipping['address_2'] : '';
		$postcode  = ! empty( $shipping['postcode'] ) ? $shipping['postcode'] : ( isset( $billing['postcode'] ) ? $billing['postcode'] : '' );
		$city      = ! empty( $shipping['city'] ) ? $shipping['city'] : ( isset( $billing['city'] ) ? $billing['city'] : '' );
		$country   = ! empty( $shipping['country'] ) ? $shipping['country'] : ( isset( $billing['country'] ) ? $billing['country'] : 'DE' );

		$parts = $this->split_street_and_house_no( $address_1 );

		$address = array(
			'name'       => $name,
			'street'     => $parts['street'],
			'houseNo'    => $parts['house_no'],
			'additional' => $address_2,
			'zip'        => $postcode,
			'city'       => $city,
			'country'    => $this->country_iso2_to_iso3( $country ),
		);

		return array_filter( $address, 'strlen' );
	}

	/**
	 * Split a combined address line (e.g. "Musterstraße 12a") into street and house number.
	 *
	 * @param  string $address_line
	 * @return array { street: string, house_no: string }
	 */
	protected function split_street_and_house_no( $address_line ) {
		$address_line = trim( (string) $address_line );

		if ( preg_match( '/^(.+?)\s+(\d+\s*[a-zA-Z]?)$/', $address_line, $m ) ) {
			return array( 'street' => trim( $m[1] ), 'house_no' => trim( $m[2] ) );
		}

		return array( 'street' => $address_line, 'house_no' => '' );
	}

	/**
	 * Convert ISO 3166-1 alpha-2 to alpha-3 (required by INTERNETMARKE API).
	 *
	 * @param  string $iso2 Two-letter country code.
	 * @return string       Three-letter country code, or original value if unknown.
	 */
	protected function country_iso2_to_iso3( $iso2 ) {
		static $map = array(
			'DE' => 'DEU', 'AT' => 'AUT', 'CH' => 'CHE', 'FR' => 'FRA', 'IT' => 'ITA',
			'ES' => 'ESP', 'NL' => 'NLD', 'BE' => 'BEL', 'LU' => 'LUX', 'PL' => 'POL',
			'CZ' => 'CZE', 'SK' => 'SVK', 'HU' => 'HUN', 'RO' => 'ROU', 'BG' => 'BGR',
			'HR' => 'HRV', 'SI' => 'SVN', 'GR' => 'GRC', 'PT' => 'PRT', 'DK' => 'DNK',
			'SE' => 'SWE', 'NO' => 'NOR', 'FI' => 'FIN', 'IE' => 'IRL', 'GB' => 'GBR',
			'US' => 'USA', 'CA' => 'CAN', 'AU' => 'AUS', 'NZ' => 'NZL', 'JP' => 'JPN',
			'CN' => 'CHN', 'IN' => 'IND', 'BR' => 'BRA', 'MX' => 'MEX', 'ZA' => 'ZAF',
			'TR' => 'TUR', 'RU' => 'RUS', 'UA' => 'UKR', 'EE' => 'EST', 'LV' => 'LVA',
			'LT' => 'LTU', 'RS' => 'SRB', 'BA' => 'BIH', 'IS' => 'ISL', 'LI' => 'LIE',
			'MT' => 'MLT', 'CY' => 'CYP', 'AL' => 'ALB', 'ME' => 'MNE', 'MK' => 'MKD',
		);

		$iso2 = strtoupper( (string) $iso2 );
		return isset( $map[ $iso2 ] ) ? $map[ $iso2 ] : $iso2;
	}

	protected function get_profile_value( $profile, array $keys ) {
		foreach ( $keys as $key ) {
			if ( is_object( $profile ) && isset( $profile->{$key} ) ) {
				return $profile->{$key};
			}

			if ( is_array( $profile ) && isset( $profile[ $key ] ) ) {
				return $profile[ $key ];
			}
		}

		return null;
	}

	protected function log( $message ) {
		PR_DHL()->log_msg( '[INTERNETMARKE] ' . $message );
	}

	protected function mask_value( $value, $visible = 2 ) {
		$value = (string) $value;
		$len   = strlen( $value );

		if ( $len <= $visible * 2 ) {
			return str_repeat( '*', $len );
		}

		return substr( $value, 0, $visible ) . str_repeat( '*', $len - ( $visible * 2 ) ) . substr( $value, -1 * $visible );
	}
}
