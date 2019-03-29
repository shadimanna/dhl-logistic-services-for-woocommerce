<?php

namespace PR\DHL\REST_API\Interfaces;

use PR\DHL\REST_API\Request;
use PR\DHL\REST_API\Response;
use RuntimeException;

/**
 * An interface for objects that act as REST API drivers.
 *
 * @since [*next-version*]
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
