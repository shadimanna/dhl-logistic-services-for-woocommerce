<?php

namespace PR\DHL\REST_API\Interfaces;

use PR\DHL\REST_API\Response;
use RuntimeException;

/**
 * Interface for objects that represent a REST API client.
 *
 * The purpose of this interface is to create a unified API for REST API clients. In this context, a "client" merely
 * represents the local object that can programmatically interact with the remote resource, in this case a REST API.
 * Ideally, a client object will use a {@link API_Driver_Interface} instance under the hood to send the actual requests.
 * This allows the client to focus solely of what to send, whereas the driver would be concerned with how to send it.
 *
 * API clients are intended to be domain-specific and have context. When calling methods on a client instance, it should
 * be assumed that the client already has some information about the recipient remote resource, such as the base URL,
 * what headers should always be sent, what authentication to use, etc.
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

	/**
	 * Sends a DELETE request to the REST API.
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
	public function delete( $route, $body = '', array $headers = array(), array $cookies = array() );
}
