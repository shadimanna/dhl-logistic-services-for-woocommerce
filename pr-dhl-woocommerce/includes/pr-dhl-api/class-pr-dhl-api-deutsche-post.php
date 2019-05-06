<?php

use PR\DHL\REST_API\Deutsche_Post\Auth;
use PR\DHL\REST_API\Deutsche_Post\Client;
use PR\DHL\REST_API\Deutsche_Post\Item_Info;
use PR\DHL\REST_API\Drivers\JSON_API_Driver;
use PR\DHL\REST_API\Drivers\WP_API_Driver;
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
	const API_URL_PRODUCTION = 'https://api-qa.deutschepost.com/v1/';

	/**
	 * The URL to the sandbox API.
	 *
	 * @since [*next-version*]
	 */
	const API_URL_SANDBOX = 'https://api-qa.deutschepost.com/v1/';

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
	protected $api_driver;
	/**
	 * The API authorization instance.
	 *
	 * @since [*next-version*]
	 *
	 * @var Auth
	 */
	protected $api_auth;
	/**
	 * The API client instance.
	 *
	 * @since [*next-version*]
	 *
	 * @var Client
	 */
	protected $api_client;

	/**
	 * Constructor.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $country_code The country code.
	 *
	 * @throws Exception
	 */
	public function __construct( $country_code ) {
		$this->country_code = $country_code;

		try {
			$this->init_api();
			$this->dhl_label = new PR_DHL_API_REST_Label();
		} catch ( Exception $e ) {
			throw $e;
		}
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
	 * Initializes the API client instance.
	 *
	 * @since [*next-version*]
	 *
	 * @throws Exception
	 */
	public function init_api() {
		if ( $this->api_client !== null ) {
			return;
		}

		$ekp = $this->get_ekp();
		$api_url = $this->get_api_url();
		list( $client_id, $client_secret ) = $this->get_api_creds();

		$this->api_driver = new JSON_API_Driver( new WP_API_Driver() );
		$this->api_auth = new Auth(
			$this->api_driver,
			$api_url,
			$client_id,
			$client_secret,
			static::ACCESS_TOKEN_TRANSIENT
		);

		$this->api_client = new Client( $ekp, $api_url, $this->api_driver, $this->api_auth );
	}

	/**
	 * Retrieves the API URL.
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 * @throws Exception
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
	 * @throws Exception
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
	 * @throws Exception
	 */
	public function get_ekp() {
		return $this->get_setting( 'dhl_pickup' );
	}

	public function get_setting( $key, $default = '' ) {
		$settings = $this->get_settings();

		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	public function get_settings() {
		return get_option( 'woocommerce_pr_dhl_dp_settings', array() );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function dhl_test_connection( $client_id, $client_secret ) {
		$this->init_api();

		return $this->api_auth->test_connection( $client_id, $client_secret );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function dhl_reset_connection() {
		$this->init_api();

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

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function get_dhl_label( $args ) {
		$this->init_api();

		$item_info = new Item_Info( $args );

		$item_response = $this->api_client->create_item( $item_info );
		$item_barcode = $item_response->barcode;

		$label_response = $this->api_client->get_label( $item_barcode );

		return $label_response;
	}
}
