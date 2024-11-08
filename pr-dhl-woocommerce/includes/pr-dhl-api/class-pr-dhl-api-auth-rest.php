<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


// Singleton API connection class
class PR_DHL_API_Auth_REST {


	/**
	 * define Auth API endpoint
	 */
	const PR_DHL_REST_AUTH_END_POINT = '/account/v1/auth/accesstoken';

	/**
	 * @var string
	 */
	protected $access_token;

	/**
	 * @var time
	 */
	protected $token_expires = 0;

	/**
	 * @var string
	 */
	protected $token_type;

	/**
	 * @var string
	 */
	protected $token_scope;

	/**
	 * @var string
	 */
	private $client_id;

	/**
	 * @var string
	 */
	private $client_secret;

	/**
	 * @var PR_DHL_API_Auth_REST
	 */
	private static $_instance; // The single instance


	/**
	 * constructor.
	 */
	private function __construct() { }

	// Magic method clone is empty to prevent duplication of connection
	private function __clone() { }

	// Stopping unserialize of object
	public function __wakeup() { }

	public static function get_instance() {

		if ( ( ! self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * @return string
	 */
	public function get_access_token( $client_id, $client_secret ) {

		// Load transient token
		$this->get_saved_token( $client_id, $client_secret );

		if ( $this->is_access_token_empty() ) {
			try {

				$this->request_access_token( $client_id, $client_secret );

			} catch ( Exception $e ) {
				throw $e;
			}
		}

		return $this->access_token;
	}

	public function delete_access_token() {
		PR_DHL()->log_msg( 'Delete Transient - Access Token' );
		delete_transient( '_dhl_auth_token_rest' );
	}

	/**
	 * @param string $access_token
	 */
	protected function set_access_token( $access_token, $expires_in = 0, $token_type = '', $token_scope = '' ) {

		if ( ! empty( $expires_in ) ) {
			// $token_expires = time() + $expires_in;
			set_transient( '_dhl_auth_token_rest', $access_token, $expires_in );
			PR_DHL()->log_msg( 'Set Transient - Access Token' );
		}

		$this->access_token = $access_token;
		$this->token_type   = $token_type;
		$this->token_scope  = $token_scope;
	}


	private function get_saved_token( $client_id, $client_secret ) {

		// if( $this->is_key_match($client_id, $client_secret) ) {
			// TRANSIENT MIGHT BE BEING USED IF KEY AND SECRET ARE NEW, EDGE CASE SO MIGHT BE OK!
			$transient_token = get_transient( '_dhl_auth_token_rest' );
			PR_DHL()->log_msg( 'Get Transient - Access Token' );
			$this->set_access_token( $transient_token );

		// }
	}

	/**
	 * @return bool
	 */
	protected function is_access_token_empty() {
		return empty( $this->access_token );
	}

	/**
	 * Get the signed URL.
	 * The signed URL is fetched by doing an OAuth request.
	 *
	 * @throws Exception
	 *
	 * @return String
	 */
	protected function request_access_token( $client_id, $client_secret ) {
		$this->client_id     = $client_id;
		$this->client_secret = $client_secret;

		if ( empty( $this->client_id ) || empty( $this->client_secret ) ) {
			throw new Exception( esc_html__( 'The "Cliend Id" or "Client Secret" is empty.', 'dhl-for-woocommerce' ) );
		}

		PR_DHL()->log_msg( 'Authorize User - Client ID: ' . $this->client_id );

		$wp_request_headers = array(
			'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
		);

		$wp_request_url = PR_DHL()->get_api_url() . self::PR_DHL_REST_AUTH_END_POINT;

		PR_DHL()->log_msg( 'Authorization URL: ' . $wp_request_url );

		$wp_auth_response = wp_remote_get(
			$wp_request_url,
			array( 'headers' => $wp_request_headers )
		);

		$response_code = wp_remote_retrieve_response_code( $wp_auth_response );
		$response_body = json_decode( wp_remote_retrieve_body( $wp_auth_response ) );

		PR_DHL()->log_msg( 'Authorization Response: ' . $response_code );

		switch ( $response_code ) {
			case '200':
				$this->set_access_token( $response_body->access_token, $response_body->expires_in, $response_body->token_type, $response_body->scope );
				break;
			case '401':
			default:
				throw new Exception( esc_html__( 'Authentication failed: Please, check client ID and secret in the DHL shipping settings', 'dhl-for-woocommerce' ) );
				break;
		}
	}

	public function is_key_match( $client_id, $client_secret ) {
		if ( ( $this->client_id == $client_id ) && ( $this->client_secret == $client_secret ) ) {
			return true;
		} else {
			return false;
		}
	}
}
