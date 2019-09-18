<?php

namespace PR\DHL\REST_API\Interfaces;

use PR\DHL\REST_API\Request;

/**
 * Interface for objects that represent REST API authorization handlers.
 *
 * Auth handlers are classes that can take a normal request and add authorization and/or authentication information
 * to it. As such, auth handlers are used by API clients as a "filter"; the client's request is given to the auth
 * handler, which returns a new request. The client can then give that new request to its driver.
 *
 * Typical usage of an auth handler from an API client class:
 * ```
 *  class Client {
 *      protected $driver;
 *      protected $auth;
 *
 *      public function lorem_ipsum() {
 *          $request = new Request( ... );
 *          $request = $this->auth->authorize( $request );
 *          $response = $this->driver->send( $request );
 *      }
 *  }
 * ```
 *
 * It's important to note that clients should ALWAYS use the request instance returned by the {@link authorize()}
 * method and should not assume that the auth handler will modify the request object given as argument. Auth handlers
 * MAY re-return the argument, but it's not guaranteed. Auth handler implementations are free to manipulate the request
 * however is necessary - add GET params, POST fields, cookies, headers, etc. - which may at times be easier done by
 * reusing the argument instance and at other times easier done by creating a brand new request instance.
 *
 * It may be the case that REST API authorization depends on some temporary token that is fetched from the REST
 * API itself. For these cases, auth handlers MAY use a driver to contact the REST API and receive said token(s).
 * This is best done in the {@link authorize()} method itself, by first checking if such a token needs to be
 * acquired and if so fetch it and save it.
 *
 * @since [*next-version*]
 *
 * @see Request
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
