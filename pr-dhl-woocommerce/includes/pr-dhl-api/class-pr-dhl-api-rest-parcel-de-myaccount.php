<?php

use PR\DHL\REST_API\Parcel_DE_MyAccount\Auth;
use PR\DHL\REST_API\Parcel_DE_MyAccount\Client;
use PR\DHL\REST_API\Parcel_DE_MyAccount\Item_Info;
use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;

// Exit if accessed directly or class already exists
if ( ! defined( 'ABSPATH' ) || class_exists( 'PR_DHL_API_Parcel_DE', false ) ) {
	return;
}

class PR_DHL_API_REST_Parcel_DE_MyAccount extends PR_DHL_API_REST_Parcel_DE {
	/**
	 * The URL to the API.
	 */
	const API_URL_PRODUCTION = 'https://api-eu.dhl.com/parcel/de/account/';

	/**
	 * The URL to the sandbox API.
	 */
	const API_URL_SANDBOX = 'https://api-sandbox.dhl.com/parcel/de/account/';

	/**
	 * The API driver instance.
	 *
	 * @var API_Driver_Interface
	 */
	public $api_driver;

	/**
	 * The API authorization instance.
	 *
	 * @var Auth
	 */
	public $api_auth;

	/**
	 * The API client instance.
	 *
	 * @var Client
	 */
	public $api_client;

	/**
	 * Constructor.
	 *
	 * @throws Exception If an error occurred while creating the API driver, auth or client.
	 */
	public function __construct() {
		try {
			$this->api_driver = $this->create_api_driver();
			$this->api_auth   = $this->create_api_auth();
			$this->api_client = $this->create_api_client();
		} catch ( Exception $e ) {
			throw $e;
		}
	}

	/**
	 * Initializes the API client instance.
	 *
	 * @return Client
	 *
	 * @throws Exception If failed to create the API client.
	 */
	protected function create_api_client() {
		// Create the API client, using this instance's driver and auth objects
		return new Client(
			$this->get_api_url(),
			$this->api_driver,
			$this->api_auth
		);
	}

	/**
	 * Initializes the API auth instance.
	 *
	 * @return API_Auth_Interface
	 *
	 * @throws Exception If failed to create the API auth.
	 */
	protected function create_api_auth() {
		// Get the saved DHL customer API credentials
		list( $username, $password ) = $this->get_api_creds();

		// Create the auth object using this instance's API driver and URL
		return new Auth(
			$this->api_driver,
			$this->get_api_url(),
			$this->get_api_key(),
			$this->get_api_secret(),
			$username,
			$password,
		);
	}

	/**
	 * Retrieves the API URL.
	 *
	 * @return string
	 *
	 * @throws Exception If failed to determine if using the sandbox API or not.
	 */
	public function get_api_url() {
		$is_sandbox = $this->get_setting( 'dhl_sandbox' );
		$is_sandbox = filter_var( $is_sandbox, FILTER_VALIDATE_BOOLEAN );
		$api_url    = ( $is_sandbox ) ? static::API_URL_SANDBOX : static::API_URL_PRODUCTION;

		return $api_url;
	}

	/**
	 * Retrieves the API credentials.
	 *
	 * @return array The client ID and client secret.
	 *
	 * @throws Exception If failed to retrieve the API credentials.
	 */
	public function get_api_creds() {
		$customer_portal_login = $this->get_customer_portal_login();

		return array(
			$customer_portal_login['username'],
			$customer_portal_login['pass'],
		);
	}

	/**
	 * API sandbox creds.
	 *
	 * @return array
	 */
	public function sandbox_info_customer_portal() {
		return array(
			'username' => 'user-valid',
			'pass'     => 'SandboxPasswort2023!',
			// 'account_no'=> '3333333333',
		);
	}

	/**
	 * Retrieves the API KEY.
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 */
	public function get_api_secret() {
		$api_key = defined( 'PR_DHL_GLOBAL_SECRET' ) ? PR_DHL_GLOBAL_SECRET : '';
		return $api_key;
	}


	/**
	 * Get user.
	 *
	 * @param $args
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_dhl_my_account() {

		return $this->api_client->get_user();

		// error_log(print_r($user_details, true));
	}
}
