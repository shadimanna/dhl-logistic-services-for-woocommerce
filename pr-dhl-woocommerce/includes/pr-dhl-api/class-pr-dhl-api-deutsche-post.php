<?php

use PR\DHL\REST_API\Deutsche_Post\Auth;
use PR\DHL\REST_API\Deutsche_Post\Client;
use PR\DHL\REST_API\Drivers\JSON_API_Driver;
use PR\DHL\REST_API\Drivers\WP_API_Driver;
use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Client_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;

// Exit if accessed directly or class already exists
if ( ! defined( 'ABSPATH' ) || class_exists( 'PR_DHL_API_Deutsche_Post', false ) ) {
	return;
}

class PR_DHL_API_Deutsche_Post extends PR_DHL_API {
	/**
	 * The URL to the API.
	 *
	 * @since [*next-version*]
	 */
	const API_URL_PRODUCTION = 'https://api-qa.deutschepost.com/';

	/**
	 * The URL to the sandbox API.
	 *
	 * @since [*next-version*]
	 */
	const API_URL_SANDBOX = 'https://api-qa.deutschepost.com/';

	/**
	 * The transient name where the API access token is stored.
	 *
	 * @since [*next-version*]
	 */
	const ACCESS_TOKEN_TRANSIENT = 'pr_dhl_deutsche_post_access_token';

	/**
	 * The API driver instance.
	 *
	 * @since [*next-version*]
	 *
	 * @var API_Driver_Interface
	 */
	public $api_driver;
	/**
	 * The API authorization instance.
	 *
	 * @since [*next-version*]
	 *
	 * @var Auth
	 */
	public $api_auth;
	/**
	 * The API client instance.
	 *
	 * @since [*next-version*]
	 *
	 * @var Client
	 */
	public $api_client;

	/**
	 * Constructor.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $country_code The country code.
	 *
	 * @throws Exception If an error occurred while creating the API driver, auth or client.
	 */
	public function __construct( $country_code ) {
		$this->country_code = $country_code;

		try {
			$this->api_driver = $this->create_api_driver();
			$this->api_auth = $this->create_api_auth();
			$this->api_client = $this->create_api_client();

			$this->dhl_label = new PR_DHL_API_DP_Label( $this->api_client );
		} catch ( Exception $e ) {
			throw $e;
		}
	}

	/**
	 * Initializes the API client instance.
	 *
	 * @since [*next-version*]
	 *
	 * @return API_Client_Interface
	 *
	 * @throws Exception If failed to create the API client.
	 */
	protected function create_api_client() {
		// Get the saved DHL customer EKP
		$ekp = $this->get_ekp();

		// Create the API client, using this instance's driver and auth objects
		return new Client(
			$ekp,
			$this->get_api_url(),
			$this->api_driver,
			$this->api_auth
		);
	}

	/**
	 * Initializes the API driver instance.
	 *
	 * @since [*next-version*]
	 *
	 * @return API_Driver_Interface
	 *
	 * @throws Exception If failed to create the API driver.
	 */
	protected function create_api_driver() {
		// Use a standard WordPress-driven API driver, decorated using the JSON driver decorator class
		return new JSON_API_Driver( new WP_API_Driver() );
	}

	/**
	 * Initializes the API auth instance.
	 *
	 * @since [*next-version*]
	 *
	 * @return API_Auth_Interface
	 *
	 * @throws Exception If failed to create the API auth.
	 */
	protected function create_api_auth() {
		// Get the saved DHL customer API credentials
		list( $client_id, $client_secret ) = $this->get_api_creds();

		// Create the auth object using this instance's API driver and URL
		return new Auth(
			$this->api_driver,
			$this->get_api_url(),
			$client_id,
			$client_secret,
			static::ACCESS_TOKEN_TRANSIENT
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function is_dhl_deutsche_post() {
		return true;
	}

	/**
	 * Retrieves the API URL.
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 *
	 * @throws Exception If failed to determine if using the sandbox API or not.
	 */
	public function get_api_url() {
		$is_sandbox = $this->get_setting( 'dhl_sandbox' );
		$api_url = ( $is_sandbox ) ? static::API_URL_SANDBOX : static::API_URL_PRODUCTION;

		return $api_url;
	}

	/**
	 * Retrieves the API credentials.
	 *
	 * @since [*next-version*]
	 *
	 * @return array The client ID and client secret.
	 *
	 * @throws Exception If failed to retrieve the API credentials.
	 */
	public function get_api_creds() {
		return array(
			$this->get_setting( 'dhl_api_key' ),
			$this->get_setting( 'dhl_api_secret' ),
		);
	}

	/**
	 * Retrieves the DHL account number, or "EKP".
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 *
	 * @throws Exception If failed to retrieve the EKP from the settings.
	 */
	public function get_ekp() {
		return $this->get_setting( 'dhl_pickup' );
	}

	/**
	 * Retrieves a single setting.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $key     The key of the setting to retrieve.
	 * @param string $default The value to return if the setting is not saved.
	 *
	 * @return mixed The setting value.
	 */
	public function get_setting( $key, $default = '' ) {
		$settings = $this->get_settings();

		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Retrieves all of the Deutsche Post settings.
	 *
	 * @since [*next-version*]
	 *
	 * @return array An associative array of the settings keys mapping to their values.
	 */
	public function get_settings() {
		return get_option( 'woocommerce_pr_dhl_dp_settings', array() );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function dhl_test_connection( $client_id, $client_secret ) {
		try {
			// Test the given ID and secret
			$token = $this->api_auth->test_connection( $client_id, $client_secret );
			// Save the token if successful
			$this->api_auth->save_token($token);

			return $token;
		} catch (Exception $e) {
			$this->api_auth->save_token(null);
			throw $e;
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function dhl_reset_connection() {
		return $this->api_auth->revoke();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function get_dhl_products_international() {
		return array(
			'GMP' => __( 'Packet', 'pr-shipping-dhl' ),
			'GPP' => __( 'Packet Plus', 'pr-shipping-dhl' ),
			'GPT' => __( 'Packet Tracked', 'pr-shipping-dhl' ),
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function get_dhl_products_domestic() {
		return $this->get_dhl_products_international();
	}
}
