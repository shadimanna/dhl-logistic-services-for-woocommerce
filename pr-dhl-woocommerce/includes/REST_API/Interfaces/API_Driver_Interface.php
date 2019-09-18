<?php

namespace PR\DHL\REST_API\Interfaces;

use PR\DHL\REST_API\API_Client;
use PR\DHL\REST_API\Request;
use PR\DHL\REST_API\Response;
use RuntimeException;

/**
 * An interface for objects that act as REST API drivers.
 *
 * A driver is simply a class that knows HOW to send requests to a remote API. Given a full {@link Request} object,
 * it can transmit it over the network to the remote API and then translate the HTTP response into a {@link Response}
 * object.
 *
 * The purpose of this abstraction is to hide the technicalities of how a request is sent, be it via a low level
 * library such as cURL, or using a framework's API such as WordPress' functions. API client classes should be the
 * main consumers of this interface.
 *
 * Implementing a new driver involves simply implementing the {@link send()} method. The {@link Request} object that is
 * received as the argument should contain all the necessary information for making the request, such as the full
 * URL, params, body, headers and cookies. Once the request is sent and a response is received, a {@link Response}
 * object should be created, containing the response information, and returned. The created response object should also
 * have a reference to the original request.
 *
 * @since [*next-version*]
 *
 * @see Request
 * @see Response
 * @see API_Client
 */
interface API_Driver_Interface {
	/**
	 * Sends a generic request to the REST API.
	 *
	 * @since [*next-version*]
	 *
	 * @param Request $request The request to send.
	 *
	 * @return Response The response.
	 *
	 * @throws RuntimeException If an error occurred and the request could not be sent.
	 */
	public function send( Request $request );
}
