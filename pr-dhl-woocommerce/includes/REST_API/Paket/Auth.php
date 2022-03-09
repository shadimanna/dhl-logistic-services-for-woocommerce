<?php

namespace PR\DHL\REST_API\Paket;

use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;
use PR\DHL\REST_API\Request;
use PR\DHL\REST_API\URL_Utils;
use RuntimeException;

/**
 * The authorization controller for DHL Paket
 *
 * The DHL Packet requires that requests send an Basic HTTP Authorization, ie. "Authorization: Basic 123456" header, where  "123456" is an
 * base64 encoded username:password (or clientID: clientSecret).
 *
 * Also, for Pickup 360, a user/customer username and login must be passed in the header "DPDHL user authentication token" base64 encoded username:password
 *
 * @since [*next-version*]
 *
 * @see https://entwickler.dhl.de/en/group/ep/authentifizierung6
 */
class Auth implements API_Auth_Interface {

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
	 * The REST API base URL.
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
	 * @param string               $api_url       The REST API base URL.
	 * @param string               $client_id     The client's ID.
	 * @param string               $client_secret The authentication secret for the client.
	 */
	public function __construct( API_Driver_Interface $driver, $api_url, $client_id, $client_secret) {
		$this->driver = $driver;
		$this->api_url = $api_url;
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function authorize( Request $request ) {
		$request->headers[ 'Authorization' ] = 'Basic '.base64_encode( $this->client_id . ':' . $this->client_secret );
		return $request;
	}

}
