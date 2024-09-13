<?php

use PR\DHL\REST_API\Paket\Auth;
use PR\DHL\REST_API\Paket\Client;
use PR\DHL\REST_API\Paket\Pickup_Request_Info;
use PR\DHL\REST_API\Drivers\JSON_API_Driver;
use PR\DHL\REST_API\Drivers\Logging_Driver;
use PR\DHL\REST_API\Drivers\WP_API_Driver;
use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;

// Exit if accessed directly or class already exists
if ( ! defined( 'ABSPATH' ) || class_exists( 'PR_DHL_API_REST_Paket', false ) ) {
	return;
}

class PR_DHL_API_REST_Paket extends PR_DHL_API {
	/**
	 * The URL to the API.
	 *
	 * @since [*next-version*]
	 */
	const API_URL_PRODUCTION = 'https://api.dhl.com/parcel/de/transportation/pickup/v3';

	/**
	 * The URL to the sandbox API.
	 *
	 * @since [*next-version*]
	 */
	const API_URL_SANDBOX = 'https://api-sandbox.dhl.com/parcel/de/transportation/pickup/v3';

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
		} catch ( Exception $e ) {
			throw $e;
		}
	}

	/**
	 * Initializes the API client instance.
	 *
	 * @since [*next-version*]
	 *
	 * @return Client
	 *
	 * @throws Exception If failed to create the API client.
	 */
	protected function create_api_client() {
		// Create the API client, using this instance's driver and auth objects
		return new Client(
			$this->get_customer_portal_username(),
			$this->get_customer_portal_password(),
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
		// Use a standard WordPress-driven API driver to send requests using WordPress' functions
		$driver = new WP_API_Driver();

		// This will log requests given to the original driver and log responses returned from it
		$driver = new Logging_Driver( PR_DHL(), $driver );

		// This will prepare requests given to the previous driver for JSON content
		// and parse responses returned from it as JSON.
		$driver = new JSON_API_Driver( $driver );

		//, decorated using the JSON driver decorator class
		return $driver;
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
		$api_cred = $this->get_api_creds();
		$client_id = $api_cred['user'];
		$client_secret = $api_cred['password'];

		// Create the auth object using this instance's API driver and URL
		return new Auth(
			$this->api_driver,
			$this->get_api_url(),
			$client_id,
			$client_secret,
			$this->get_api_key(),
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function is_dhl_paket() {
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
		$api_cred = $this->get_api_creds();
		return $api_cred['auth_url'];
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
		$dhl_sandbox = $this->get_setting( 'dhl_sandbox' ) ? $this->get_setting( 'dhl_sandbox' ) : '';
		if ( $dhl_sandbox == 'yes' || ( defined( 'PR_DHL_SANDBOX' ) && PR_DHL_SANDBOX ) ) {

			$user = defined( 'PR_DHL_CIG_USR_QA' )? PR_DHL_CIG_USR_QA : '';
			$user = ( !$user )? $this->get_setting('dhl_api_sandbox_user') : $user;

			$pass = defined( 'PR_DHL_CIG_PWD_QA' )? PR_DHL_CIG_PWD_QA : '';
			$pass = ( !$pass )? $this->get_setting('dhl_api_sandbox_pwd') : $pass;

			$api_cred['user'] = $user;
			$api_cred['password'] = $pass;
			//$api_cred['auth_url'] = trailingslashit(str_ireplace('/soap', '/rest', PR_DHL_CIG_AUTH_QA));
			$api_cred['auth_url'] = self::API_URL_SANDBOX;
		} else {
			$api_cred['user'] = PR_DHL_CIG_USR;
			$api_cred['password'] = PR_DHL_CIG_PWD;
			//$api_cred['auth_url'] = trailingslashit(str_ireplace('/soap', '/rest', PR_DHL_CIG_AUTH));
			$api_cred['auth_url'] = self::API_URL_PRODUCTION;
		}
		return $api_cred;
	}

	/**
	 * Retrieves the Customer Portal login credentials.
	 *
	 * @since [*next-version*]
	 *
	 * @return array The customer username and password for Business portal API calls.
	 *
	 */
	public function get_customer_portal_login() {
		$is_sandbox = $this->get_setting( 'dhl_sandbox' );
		$is_sandbox = filter_var($is_sandbox, FILTER_VALIDATE_BOOLEAN);
		if ( $is_sandbox ) {
			$sandbox = $this->sandbox_info_customer_portal();
			return array(
				'username' => $sandbox['username'],
				'pass' => $sandbox['pass'],
			);
			// return array(
			// 	'username' => $this->get_setting('dhl_api_sandbox_user'),
			// 	'pass' => $this->get_setting('dhl_api_sandbox_pwd'),
			// );

		} else {
			return array(
				'username' => $this->get_setting( 'dhl_api_user' ),
				'pass' => $this->get_setting( 'dhl_api_pwd' ),
			);
		}
	}

	public function get_customer_portal_username() {
		$customer_login = $this->get_customer_portal_login();
		return $customer_login['username'];
	}

	public function get_customer_portal_password() {
		$customer_login = $this->get_customer_portal_login();
		return $customer_login['pass'];
	}

	/**
	 * Retrieves the Sandbox DHL Pickup Account Number
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 *
	 * @throws Exception If failed to retrieve the EKP from the settings.
	 */
	public function maybe_get_sandbox_account_number() {
		$is_sandbox = $this->get_setting( 'dhl_sandbox' );
		$is_sandbox = filter_var($is_sandbox, FILTER_VALIDATE_BOOLEAN);
		if ( $is_sandbox ) {
			$sandbox_info = $this->sandbox_info_customer_portal();
			return $sandbox_info['account_no'];
		}
		return null;
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
		return get_option( 'woocommerce_pr_dhl_paket_settings', array() );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function dhl_test_connection( $client_id, $client_secret ) {
		// try {
		// 	// Test the given ID and secret
		// 	$token = $this->api_auth->test_connection( $client_id, $client_secret );
		// 	// Save the token if successful
		// 	//$this->api_auth->save_token( $token );
		//
		// 	return $token;
		// } catch ( Exception $e ) {
		// 	//$this->api_auth->save_token( null );
		// 	throw $e;
		// }
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function dhl_reset_connection() {
		//return $this->api_auth->revoke();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	 public function get_dhl_products_domestic() {
 		$country_code = $this->country_code;

 		$germany_dom = array(
 								'V01PAK' => __('DHL Paket', 'dhl-for-woocommerce'),
 								'V01PRIO' => __('DHL Paket PRIO', 'dhl-for-woocommerce'),
 								'V62WP' => __('DHL Warenpost National', 'dhl-for-woocommerce'),
 								);

 		$dhl_prod_dom = array();

 		switch ($country_code) {
 			case 'DE':
 				$dhl_prod_dom = $germany_dom;
 				break;
 			default:
 				break;
 		}

         return apply_filters( 'pr_shipping_dhl_paket_products_domestic', $dhl_prod_dom );
 	}

	public function get_dhl_products_international() {
		$country_code = $this->country_code;

		$germany_int =  array(
								'V55PAK' => __('DHL Paket Connect', 'dhl-for-woocommerce'),
								'V54EPAK' => __('DHL Europaket (B2B)', 'dhl-for-woocommerce'),
								'V53WPAK' => __('DHL Paket International', 'dhl-for-woocommerce'),
								'V66WPI' => __('DHL Warenpost International', 'dhl-for-woocommerce'),
								);

		$dhl_prod_int = array();

		switch ($country_code) {
			case 'DE':
				$dhl_prod_int = $germany_int;
				break;
			default:
				break;
		}

        return apply_filters( 'pr_shipping_dhl_paket_products_international', $dhl_prod_int );
	}


	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function request_dhl_pickup ( $args, $forcePortalPickupAddressMatch = true ) {

		$uom 				= get_option( 'woocommerce_weight_unit' );

		// Maybe override account billing number here for Sandbox user
		if ( $this->maybe_get_sandbox_account_number() ) {
			$args['dhl_pickup_billing_number'] = $this->maybe_get_sandbox_account_number();
		}

		try {
			$request_pickup_info = new Pickup_Request_Info( $args, $uom );
		} catch (Exception $e) {
			throw $e;
		}

		// Verify pickup address with DHL portal pickup address first
		if ( $forcePortalPickupAddressMatch ) {

			$blnIncludeBillingNumber = false;

			$zipCode = $request_pickup_info->pickup_address['zip'];
			$localAddress = $request_pickup_info->pickup_address;

			try {
				$pickup_location_response = $this->api_client->get_pickup_location( $zipCode );

				$foundPickupLocMatch = false;
				foreach ( $pickup_location_response as $pickup_address ) {

					if ( $pickup_address && isset( $pickup_address->pickupLocation) ) {
						$portalAddress = ( isset($pickup_address->pickupLocation->pickupAddress->nativeAddress )) ? $pickup_address->pickupLocation->pickupAddress->nativeAddress : null;
						if ( strtolower(preg_replace("/[^A-Za-z0-9]/", '', $portalAddress->streetName)) == strtolower(preg_replace("/[^A-Za-z0-9]/", '', $localAddress['streetName']))
				            && strtolower(preg_replace("/[^A-Za-z0-9]/", '', $portalAddress->houseNumber)) == strtolower(preg_replace("/[^A-Za-z0-9]/", '', $localAddress['houseNumber']))
				            && strtolower(preg_replace("/[^A-Za-z0-9]/", '', $portalAddress->city)) == strtolower(preg_replace("/[^A-Za-z0-9]/", '', $localAddress['city']))
				            && strtolower(preg_replace("/[^A-Za-z0-9]/", '', $portalAddress->zip)) == strtolower(preg_replace("/[^A-Za-z0-9]/", '', $localAddress['zip']))
				            && strtolower(preg_replace("/[^A-Za-z0-9]/", '', $portalAddress->countryIso2Code)) == strtolower(preg_replace("/[^A-Za-z0-9]/", '', $localAddress['countryIso2Code'])) )
				        {
				            $foundPickupLocMatch = true;
				            break;
				        }
					}
				}

				if ( ! $foundPickupLocMatch ) {
					throw new Exception(
						__('Your Shipper Address must match a Pickup address on your DHL Portal.', 'dhl-for-woocommerce')
					);
				}

			} catch (Exception $e) {
				throw $e;
			}

		} else {
			//If we are not matching a Pickup address, then we need to include the billing number
			$blnIncludeBillingNumber = true;
		}

		// Create the shipping label
		try {
			$request_pickup_response = $this->api_client->create_pickup_request( $request_pickup_info, $blnIncludeBillingNumber );
		} catch (Exception $e) {
			throw $e;
		}

		return $request_pickup_response;
	}


	public function sandbox_info_customer_portal(){
		//
		return array(
			'username' 	=> 'user-valid',
			'pass' 		=> 'SandboxPasswort2023!',
			'account_no'=> '22222222220801',
		);
	}

	/**
	 * Retrieves the API KEY.
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 *
	 */
	public function get_api_key() {
		$api_key = defined( 'PR_DHL_GLOBAL_API' )? PR_DHL_GLOBAL_API : '';
		return $api_key;
	}
}
