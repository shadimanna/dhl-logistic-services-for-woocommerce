<?php

namespace PR\DHL\REST_API\Interfaces;

use PR\DHL\REST_API\Request;

/**
 * Interface for objects that represent REST API authorization handlers.
 *
 * The purpose of this interface is to give REST API clients a uniform way of authorizing requests before they are
 * handed over to the REST API driver. As such, objects that implement this interface are typically called by a
 * {@link API_Client_Interface} instance, specifically one that has already created a {@link Request} object and is
 * ready to pass it to a driver. The auth object is given the chance to modify the request to make sure that the remote
 * resource will correctly authenticate the client.
 *
 * @since [*next-version*]
 */
interface API_Auth_Interface {
	/**
	 * Adds authentication details to a given request.
	 *
	 * @since [*next-version*]
	 *
	 * @param Request $request The request to modify with authentication details.
	 *
	 * @return Request The new request.
	 */
	public function authorize( Request $request );
}
