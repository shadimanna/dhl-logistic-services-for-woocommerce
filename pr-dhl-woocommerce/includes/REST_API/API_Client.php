<?php

namespace PR\DHL\REST_API;

use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Client_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;

/**
 * A simple and generic REST API client implementation that uses an internally-known base URL for all requests.
 *
 * @since [*next-version*]
 */
class API_Client {
	/**
	 * The base URL for the REST API.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	protected $base_url;
	/**
	 * The REST API driver to use.
	 *
	 * @since [*next-version*]
	 *
	 * @var API_Driver_Interface
	 */
	protected $driver;
	/**
	 * The authorization driver to use.
	 *
	 * @since [*next-version*]
	 *
	 * @var API_Auth_Interface|null
	 */
	private $auth;

	/**
	 * Constructor.
	 *
	 * @since [*next-version*]
	 *
	 * @param string                  $base_url The base URL for the REST API.
	 * @param API_Driver_Interface    $driver   The REST API driver to use.
	 * @param API_Auth_Interface|null $auth     Optional authorization driver to use.
	 */
	public function __construct( $base_url, API_Driver_Interface $driver, API_Auth_Interface $auth = null ) {
		$this->base_url = $base_url;
		$this->driver = $driver;
		$this->auth = $auth;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	protected function get( $route, array $params = array(), array $headers = array(), array $cookies = array() ) {
		return $this->send_request( Request::TYPE_GET, $route, $params, '', $headers, $cookies );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	protected function post( $route, $body = '', array $headers = array(), array $cookies = array() ) {
		return $this->send_request( Request::TYPE_POST, $route, array(), $body, $headers, $cookies );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	protected function delete( $route, $body = '', array $headers = array(), array $cookies = array() ) {
		return $this->send_request( Request::TYPE_DELETE, $route, array(), $body, $headers, $cookies );
	}

	/**
	 * Sends a request using the internal driver.
	 *
	 * @since [*next-version*]
	 *
	 * @param int        $type    The request type, either {@link TYPE_GET} or {@link TYPE_POST}.
	 * @param string     $route   The request route, relative to the REST API's base URL.
	 * @param array      $params  The GET params for this request.
	 * @param mixed|null $body    The body of the request.
	 * @param array      $headers The request headers to send.
	 * @param array      $cookies The request cookies to send.
	 *
	 * @return Response The response.
	 */
	protected function send_request(
		$type,
		$route,
		array $params = array(),
		$body = '',
		array $headers = array(),
		array $cookies = array()
	) {
		// Generate the full URL and the request object
		$full_url = URL_Utils::merge_url_and_route($this->base_url, $route);
		$request = new Request( $type, $full_url, $params, $body, $headers, $cookies );

		// If we have an authorization driver, authorize the request
		if ($this->auth !== null) {
			$request = $this->auth->authorize($request);
		}

		// Send the request using the driver and obtain the response
		$response = $this->driver->send( $request );

		return $response;
	}

	/**
	 * Prepares a request URL by combining the base URL of the REST API with the request route.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $route The request route.
	 *
	 * @return string The prepared URL string.
	 */
	protected function prepare_url( $route ) {
		return $this->base_url . '/' . ltrim( $route, '/' );
	}
}
