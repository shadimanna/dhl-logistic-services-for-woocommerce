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
		$this->driver = new JSON_API_Driver( new WP_API_Driver() );
		$this->auth   = new Auth(
			$this->driver,
			static::API_URL,
			$this->get_username(),
			$this->get_password(),
			$this->get_portokasse_id(),
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
		if ( '' === $this->get_username() || '' === $this->get_password() || '' === $this->get_portokasse_id() ) {
			throw new Exception( esc_html__( 'Please save the INTERNETMARKE username, password, and Portokasse ID before running this action.', 'dhl-for-woocommerce' ) );
		}
	}

	protected function get_settings() {
		return array(
			'internetmarke_api_user'     => get_option( 'pr_dhl_internetmarke_internetmarke_api_user', '' ),
			'internetmarke_api_password' => get_option( 'pr_dhl_internetmarke_internetmarke_api_password', '' ),
			'internetmarke_portokasse_id' => get_option( 'pr_dhl_internetmarke_internetmarke_portokasse_id', '' ),
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
