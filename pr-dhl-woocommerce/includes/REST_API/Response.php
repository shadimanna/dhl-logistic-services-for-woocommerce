<?php

namespace PR\DHL\REST_API;

use stdClass;

/**
 * A class that represents a REST API response.
 *
 * @since [*next-version*]
 */
class Response {
	/**
	 * The request that was sent.
	 *
	 * @since [*next-version*]
	 *
	 * @var Request
	 */
	public $request;
	/**
	 * The status code of the response.
	 *
	 * @since [*next-version*]
	 *
	 * @var int
	 */
	public $status;
	/**
	 * The body of the response, as a raw string or parsed object.
	 *
	 * @since [*next-version*]
	 *
	 * @var string|stdClass
	 */
	public $body;
	/**
	 * The response headers.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $headers;
	/**
	 * The response cookies.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $cookies;

	/**
	 * Constructor.
	 *
	 * @since [*next-version*]
	 *
	 * @param Request $request The request that was sent.
	 * @param int     $status  The status code of the response.
	 * @param string  $body    The body of the response.
	 * @param array   $headers The response headers.
	 * @param array   $cookies The response cookies.
	 */
	public function __construct(
		Request $request,
		$status,
		$body,
		array $headers = array(),
		array $cookies = array()
	) {
		$this->request = $request;
		$this->status  = (int) $status;
		$this->body    = $body;
		$this->headers = $headers;
		$this->cookies = $cookies;
	}
}
