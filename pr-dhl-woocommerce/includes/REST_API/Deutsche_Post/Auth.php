<?php

namespace PR\DHL\REST_API\Deutsche_Post;

use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;
use PR\DHL\REST_API\Request;
use PR\DHL\REST_API\URL_Utils;
use RuntimeException;

/**
 * The authorization controller for Deutsche Post.
 *
 * The Deutsche Post API requires that requests send an "Authorization: Bearer 123456" header, where  "123456" is an
 * access code. That access code is obtained from the REST API itself by sending the client ID and client secret,
 * encoded in a base64 string. The REST API should respond with a token, which will contain the code, its expiry,
 * type, etc.
 *
 * So the process for authorization involves first obtaining the token, storing it locally and then using it to
 * authorize regular REST API requests. This class stores the token in a transient with an expiry time that matches
 * the expiry time of the token as indicated by the Deutsche Post REST API.
 *
 * @since [*next-version*]
 *
 * @see https://api-qa.deutschepost.com/dpi-apidoc/#/reference/authentication/access-token/get-access-token
 */
class Auth implements API_Auth_Interface {
	/**
	 * The Deutsche POST route for obtaining an access token.
	 *
	 * @since [*next-version*]
	 */
	const AUTH_ROUTE = 'dpi/v1/auth/accesstoken';

	/**
	 * The Deutsche POST route for revoking an access token.
	 *
	 * @since [*next-version*]
	 */
	const REVOKE_ROUTE = 'dpi/v1/auth/accesstoken/revoke';

	/**
	 * The Deutsche POST header where the client ID and secret are included when fetching the access token.
	 *
	 * @since [*next-version*]
	 */
	const H_AUTH_CREDENTIALS = 'Authorization';

	/**
	 * The Deutsche POST authorization header where request credentials and access tokens are included.
	 *
	 * @since [*next-version*]
	 */
	const H_AUTH_TOKEN = 'Authorization';

	/**
	 * The Deutsche Post authorization header where the 3rd party vendor ID is included.
	 *
	 * @since [*next-version*]
	 */
	const H_3PV_ID = 'ThirdPartyVendor-ID';

	/**
	 * The Deutsche Post 3rd party vendor ID to include in the corresponding header.
	 *
	 * @since [*next-version*]
	 */
	const V_3PV_ID = '3pv_woocommerce';

	/**
	 * The driver to use for obtaining and revoking the access token.
	 *
	 * @since [*next-version*]
	 *
	 * @var API_Driver_Interface
	 */
	protected $driver;
	/**
	 * The client's ID.
	 *
	 * @var string
	 */
	protected $client_id;
	/**
	 * The authentication secret for the client.
	 *
	 * @var string
	 */
	protected $client_secret;
	/**
	 * The name of the transient to use for caching the access token.
	 *
	 * @var string
	 */
	protected $transient;
	/**
	 * The cached access token.
	 *
	 * @var object
	 */
	protected $token;
	/**
	 * The Deutsche Post REST API base URL.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	protected $api_url;

	/**
	 * Constructor.
	 *
	 * @since [*next-version*]
	 *
	 * @param API_Driver_Interface $driver        The driver to use for obtaining and revoking the access token.
	 * @param string               $api_url       The Deutsche Post REST API base URL.
	 * @param string               $client_id     The client's ID.
	 * @param string               $client_secret The authentication secret for the client.
	 * @param string               $transient     The name of the transient to use for caching the access token.
	 */
	public function __construct( API_Driver_Interface $driver, $api_url, $client_id, $client_secret, $transient ) {
		$this->driver        = $driver;
		$this->api_url       = $api_url;
		$this->client_id     = $client_id;
		$this->client_secret = $client_secret;
		$this->transient     = $transient;

		// Load the token from the transient cache
		$this->load_token();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function authorize( Request $request ) {
		// Check if we have a token - a token is ALWAYS needed
		if ( empty( $this->token ) ) {
			// If not, request one from the REST API
			$token = $this->request_token();
			// Cache it for subsequent requests
			$this->save_token( $token );
		}

		$type = $this->token->token_type;
		$code = $this->token->access_token;

		$request->headers[ static::H_AUTH_TOKEN ] = $type . ' ' . $code;
		$request->headers[ static::H_3PV_ID ]     = static::V_3PV_ID;

		return $request;
	}

	/**
	 * Requests an access token from the API.
	 *
	 * Sends the client ID and client secret to the REST API to obtain the access token. The access token is then
	 * used to authorize all other requests.
	 * See https://api-qa.deutschepost.com/dpi-apidoc/#/reference/authentication/access-token/get-access-token
	 *
	 * @since [*next-version*]
	 *
	 * @return object The token object.
	 *
	 * @throws RuntimeException If failed to retrieve the access token.
	 */
	public function request_token() {
		// Base64 encode the "<client_id>:<client_secret>" and send as the "Authorization" header
		$auth_str_64 = base64_encode( $this->client_id . ':' . $this->client_secret );
		$headers     = array( static::H_AUTH_CREDENTIALS => 'Basic ' . $auth_str_64 );

		// Prepare the full request URL
		$full_url = URL_Utils::merge_url_and_route( $this->api_url, static::AUTH_ROUTE );

		// Send the authorization request to obtain the access token
		$request  = new Request( Request::TYPE_GET, $full_url, array(), '', $headers );
		$response = $this->driver->send( $request );

		// If the status code is not 200, throw an error with the raw response body
		if ( $response->status !== 200 ) {
			$response_body = json_decode( $response->body );
			throw new RuntimeException( esc_html( $response_body->detail ) );
		}

		return $response->body;
	}

	/**
	 * Revokes the access token.
	 *
	 * @since [*next-version*]
	 *
	 * @return string The response body.
	 */
	public function revoke() {
		// Do nothing if we didn't already have a token
		if ( empty( $this->token ) || empty( $this->token->access_token ) ) {
			return '';
		}

		// Prepare the full request URL
		$full_url = URL_Utils::merge_url_and_route( $this->api_url, static::REVOKE_ROUTE );
		// Create the request
		$params  = array( 'token' => $this->token->access_token );
		$request = new Request( Request::TYPE_GET, $full_url, $params );

		// Send the request
		$response = $this->driver->send( $request );

		if ( $response->status !== 200 && $response->status !== 401 ) {
			throw new RuntimeException( 'Failed to revoke the Deutsche Post access token' );
		}

		// Delete the cached token
		$this->delete_token();

		return $response->body;
	}

	/**
	 * Tests the connection with a given client ID and secret.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $client_id     The client ID.
	 * @param string $client_secret The client secret.
	 *
	 * @return object
	 */
	public function test_connection( $client_id, $client_secret ) {
		// Backup the client credentials
		$backup_client_id     = $this->client_id;
		$backup_client_secret = $this->client_secret;

		// Set params as credentials
		$this->client_id     = $client_id;
		$this->client_secret = $client_secret;

		// Send the request
		$token = $this->request_token();

		// Restore the credentials
		$this->client_id     = $backup_client_id;
		$this->client_secret = $backup_client_secret;

		return $token;
	}

	/**
	 * Saves the access token.
	 *
	 * @param object $token The token to save.
	 */
	public function save_token( $token ) {
		$expires_in = isset( $token->expires_in )
			? $token->expires_in
			: time() + DAY_IN_SECONDS;

		set_transient( $this->transient, $token, $expires_in );

		$this->token = $token;
	}

	/**
	 * Retrieves the access token.
	 *
	 * @return object
	 */
	public function load_token() {
		return $this->token = get_transient( $this->transient );
	}

	/**
	 * Deletes the cached access token.
	 *
	 * @return array
	 */
	public function delete_token() {
		return delete_transient( $this->transient );
	}
}
