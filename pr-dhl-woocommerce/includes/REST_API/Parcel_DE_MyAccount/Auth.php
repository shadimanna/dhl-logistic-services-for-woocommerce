<?php

namespace PR\DHL\REST_API\Parcel_DE_MyAccount;

use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;
use PR\DHL\REST_API\Request;
use PR\DHL\REST_API\URL_Utils;
use RuntimeException;

/**
 * The authorization controller for Paket My Account.
 *
 * The Paket My Account API requires that requests send an "Authorization: Bearer 123456" header, where  "123456" is an
 * access code. That access code is obtained from the REST API itself by sending the client ID and client secret,
 * encoded in a base64 string. The REST API should respond with a token, which will contain the code, its expiry,
 * type, etc.
 *
 * So the process for authorization involves first obtaining the token, storing it locally and then using it to
 * authorize regular REST API requests. This class stores the token in a transient with an expiry time that matches
 * the expiry time of the token as indicated by the Paket My Account REST API.
 *
 * @since [*next-version*]
 *
 * @see https://api-qa.deutschepost.com/dpi-apidoc/#/reference/authentication/access-token/get-access-token
 */
class Auth implements API_Auth_Interface {
	/**
	 * The Paket My Account route for obtaining an access token.
	 *
	 * @since [*next-version*]
	 */
	const AUTH_ROUTE = 'auth/ropc/v1/token';

	/**
	 * The Paket My Account header where the client ID and secret are included when fetching the access token.
	 *
	 * @since [*next-version*]
	 */
	const H_AUTH_CREDENTIALS = 'Authorization';

	/**
	 * The Paket My Account authorization header where request credentials and access tokens are included.
	 *
	 * @since [*next-version*]
	 */
	const H_AUTH_TOKEN = 'Authorization';

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
	 * The authentication username for the client.
	 *
	 * @var string
	 */
	protected $client_secret;
	/**
	 * The authentication password for the client.
	 *
	 * @var string
	 */
	protected $username;
	/**
	 * The client's ID.
	 *
	 * @var string
	 */
	protected $password;
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
	 * The Paket My Account REST API base URL.
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
	 * @param string               $api_url       The Paket My Account REST API base URL.
	 * @param string               $client_id     The client's ID.
	 * @param string               $client_secret The authentication secret for the client.
	 * @param string               $transient     The name of the transient to use for caching the access token.
	 */
	public function __construct( API_Driver_Interface $driver, $api_url, $client_id, $client_secret, $username, $password ) {
		$this->driver        = $driver;
		$this->api_url       = $api_url;
		$this->client_id     = $client_id;
		$this->client_secret = $client_secret;
		$this->username      = $username;
		$this->password      = $password;
		// $this->transient = $transient;

		// Load the token from the transient cache
		// $this->load_token();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function authorize( Request $request ) {
		// Check if we have a token - a token is ALWAYS needed
		// if ( empty( $this->token ) ) {
			// If not, request one from the REST API
			$token = $this->request_token();
			// error_log(print_r($token, true));
			// Cache it for subsequent requests
			// $this->save_token( $token );
		// }

		// $type = $this->token->token_type;
		// $code = $this->token->access_token;

		$request->headers[ static::H_AUTH_TOKEN ] = $token->token_type . ' ' . $token->access_token;
		// $request->headers[ static::H_3PV_ID ] = static::V_3PV_ID;

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
		// $auth_str_64 = base64_encode( $this->client_id . ':' . $this->client_secret );
		// $headers = array( static::H_AUTH_CREDENTIALS => 'Basic ' . $auth_str_64 );
		$headers = array( 'Content-Type' => 'application/x-www-form-urlencoded' );
		$body    = array(
			'client_id'     => $this->client_id,
			'client_secret' => $this->client_secret,
			'username'      => $this->username,
			'password'      => $this->password,
			'grant_type'    => 'password',
		);
		// error_log(print_r($body, true));

		$args = array(
			'headers' => $headers,
			'body'    => $body,
		);

		// $body = json_encode( $body );
		// error_log($body);
		// Prepare the full request URL
		$full_url = URL_Utils::merge_url_and_route( $this->api_url, static::AUTH_ROUTE );
		// error_log($this->api_url);
		// error_log($full_url);

		// Send the authorization request to obtain the access token
		// $request = new Request( Request::TYPE_POST, $full_url, array(), $body, $headers );
		// $response = $this->driver->send( $request );
		$response = wp_remote_post( $full_url, $args );

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );
		// error_log(print_r($response, true));

		// If the status code is not 200, throw an error with the raw response body
		if ( $response_code !== 200 ) {
			throw new RuntimeException( esc_html( $response->body->error_description ) );
		}

		return $response_body;
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
