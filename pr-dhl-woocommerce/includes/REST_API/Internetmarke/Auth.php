<?php

namespace PR\DHL\REST_API\Internetmarke;

use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;
use PR\DHL\REST_API\Request;
use PR\DHL\REST_API\URL_Utils;
use RuntimeException;

/**
 * Authorization handler for Deutsche Post INTERNETMARKE.
 */
class Auth implements API_Auth_Interface {
	const AUTH_ROUTE = 'user';

	protected $driver;

	protected $api_url;

	protected $username;

	protected $password;

	protected $portokasse_id;

	protected $transient;

	protected $token;

	public function __construct( API_Driver_Interface $driver, $api_url, $username, $password, $portokasse_id, $transient ) {
		$this->driver        = $driver;
		$this->api_url       = $api_url;
		$this->username      = $username;
		$this->password      = $password;
		$this->portokasse_id = $portokasse_id;
		$this->transient     = $transient;

		$this->load_token();
	}

	public function authorize( Request $request ) {
		if ( empty( $this->token ) || empty( $this->token->access_token ) ) {
			$this->save_token( $this->request_token() );
		}

		$request->headers['Authorization'] = 'Bearer ' . $this->token->access_token;

		return $request;
	}

	public function test_connection() {
		return $this->request_token();
	}

	public function request_token() {
		$full_url = URL_Utils::merge_url_and_route( $this->api_url, static::AUTH_ROUTE );
		$body     = array_filter(
			array(
				'username'     => $this->username,
				'password'     => $this->password,
				'portokasseId' => $this->portokasse_id,
			)
		);

		$request = new Request(
			Request::TYPE_POST,
			$full_url,
			array(),
			$body,
			array( 'Accept' => 'application/json' )
		);

		$response = $this->driver->send( $request );

		if ( 200 !== (int) $response->status ) {
			$message = $this->extract_error_message( $response->body, $response->status );
			throw new RuntimeException( $message );
		}

		$token = is_object( $response->body ) ? $response->body : json_decode( (string) $response->body );

		if ( empty( $token ) || empty( $token->access_token ) ) {
			throw new RuntimeException( esc_html__( 'INTERNETMARKE authorization did not return an access token.', 'dhl-for-woocommerce' ) );
		}

		return $token;
	}

	public function save_token( $token ) {
		$this->token = $token;

		if ( empty( $token ) ) {
			delete_transient( $this->transient );
			return;
		}

		$expires_in = ! empty( $token->expires_in ) ? absint( $token->expires_in ) : HOUR_IN_SECONDS;
		set_transient( $this->transient, $token, $expires_in );
	}

	public function delete_token() {
		$this->token = null;
		delete_transient( $this->transient );
	}

	protected function load_token() {
		$this->token = get_transient( $this->transient );
	}

	protected function extract_error_message( $body, $status ) {
		if ( is_string( $body ) ) {
			$decoded = json_decode( $body );
		} else {
			$decoded = $body;
		}

		if ( ! empty( $decoded->detail ) ) {
			return sanitize_text_field( $decoded->detail );
		}

		if ( ! empty( $decoded->message ) ) {
			return sanitize_text_field( $decoded->message );
		}

		if ( 401 === (int) $status ) {
			return esc_html__( 'Authorization failed. Check the INTERNETMARKE credentials and confirm the business application in Portokasse if this is the first API use.', 'dhl-for-woocommerce' );
		}

		return sprintf(
			/* translators: %d: HTTP response status code. */
			esc_html__( 'INTERNETMARKE authorization failed with HTTP %d.', 'dhl-for-woocommerce' ),
			absint( $status )
		);
	}
}
