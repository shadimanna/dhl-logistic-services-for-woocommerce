<?php

namespace PR\DHL\REST_API\Parcel_DE;

use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;
use PR\DHL\REST_API\Request;

/**
 * The authorization controller for DHL Paket
 *
 * In additional to API Key header "dhl-api-key: ${KEY}", The Parcel DE Shipping API requires that requests send a Basic HTTP Authorization
 * ie "Authorization: Basic 123456" header, where  "123456" is an base64 encoded username:password (or clientID: clientSecret).
 *
 * @since [*next-version*]
 *
 * @see https://developer.dhl.com/api-reference/parcel-de-shipping-post-parcel-germany-v2#get-started-section/user-guide--authentication
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
	 * API key provided by DHL.
	 *
	 * @var string
	 */
	protected $api_key;
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
	 * @param string               $api_key       The API key provided by DHL.
	 */
	public function __construct( API_Driver_Interface $driver, $api_url, $client_id, $client_secret, $api_key ) {
		$this->driver        = $driver;
		$this->api_url       = $api_url;
		$this->client_id     = $client_id;
		$this->client_secret = $client_secret;
		$this->api_key       = $api_key;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function authorize( Request $request ) {
		$request->headers['Authorization'] = 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret );
		$request->headers['dhl-api-key']   = $this->api_key;
		return $request;
	}
}
