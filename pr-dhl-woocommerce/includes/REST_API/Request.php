<?php

namespace PR\DHL\REST_API;

/**
 * A class that represents a REST API request.
 *
 * @since [*next-version*]
 */
class Request {
	/**
	 * Constant for GET requests.
	 *
	 * @since [*next-version*]
	 *
	 * @var int
	 */
	const TYPE_GET = 0;

	/**
	 * Constant for POST requests.
	 *
	 * @since [*next-version*]
	 *
	 * @var int
	 */
	const TYPE_POST = 1;

	/**
	 * Constant for DELETE requests.
	 *
	 * @since [*next-version*]
	 *
	 * @var int
	 */
	const TYPE_DELETE = 2;

	/**
	 * The type of the request.
	 *
	 * @since [*next-version*]
	 *
	 * @see Request::TYPE_GET
	 * @see Request::TYPE_POST
	 * @see Request::TYPE_DELETE
	 *
	 * @var int
	 */
	public $type;
	/**
	 * The URL where the request will be made.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	public $url;
	/**
	 * The GET params for this request.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $params;
	/**
	 * The body of the request.
	 *
	 * @since [*next-version*]
	 *
	 * @var mixed|null
	 */
	public $body;
	/**
	 * The request headers.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $headers;
	/**
	 * The request headers.
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
	 * @param int        $type    The request type.
	 * @param string     $url     The base URL where the request will be made.
	 * @param array      $params  The GET params for this request.
	 * @param mixed|null $body    The body of the request.
	 * @param array      $headers The request headers to send.
	 * @param array      $cookies The request cookies to send.
	 */
	public function __construct(
		$type,
		$url,
		array $params = array(),
		$body = null,
		array $headers = array(),
		array $cookies = array()
	) {
		$this->type    = $type;
		$this->url     = $url;
		$this->params  = $params;
		$this->body    = $body;
		$this->headers = $headers;
		$this->cookies = $cookies;
	}
}
