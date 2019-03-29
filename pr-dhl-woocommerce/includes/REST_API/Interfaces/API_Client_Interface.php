<?php

namespace PR\DHL\REST_API\Interfaces;

use PR\DHL\REST_API\Response;
use RuntimeException;

/**
 * Interface for objects that represent a REST API client.
 *
 * @since [*next-version*]
 */
interface API_Client_Interface {
	/**
	 * Sends a GET request to the REST API.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $route   The request route, relative to the REST API's base URL.
	 * @param array  $params  Optional list of GET parameters to send.
	 * @param array  $headers Optional list of headers to send.
	 * @param array  $cookies Optional list of cookies to send.
	 *
	 * @return Response The response.
	 *
	 * @throws RuntimeException If an error occurred and the request could not be sent.
	 */
	public function get( $route, array $params = array(), array $headers = array(), array $cookies = array() );

	/**
	 * Sends a POST request to the REST API.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $route   The request route, relative to the REST API's base URL.
	 * @param mixed  $body    The body data to send.
	 * @param array  $headers Optional list of headers to send.
	 * @param array  $cookies Optional list of cookies to send.
	 *
	 * @return Response The response.
	 *
	 * @throws RuntimeException If an error occurred and the request could not be sent.
	 */
	public function post( $route, $body = '', array $headers = array(), array $cookies = array() );
}
