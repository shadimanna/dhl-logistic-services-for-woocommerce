<?php

namespace PR\DHL\REST_API\Interfaces;

use PR\DHL\REST_API\Request;

/**
 * Interface for objects that represent REST API authorization handlers.
 *
 * @since [*next-version*]
 */
interface API_Auth_Interface {
	/**
	 * Adds authorization details to a given request.
	 *
	 * @since [*next-version*]
	 *
	 * @param Request $request The request to modify with authentication details.
	 *
	 * @return Request The new request.
	 */
	public function authorize( Request $request );
}
