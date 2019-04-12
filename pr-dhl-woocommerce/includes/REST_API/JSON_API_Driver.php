<?php

namespace PR\DHL\REST_API;

use PR\DHL\REST_API\Interfaces\API_Driver_Interface;

/**
 * A REST API driver decorator that automatically encodes/decodes JSON in POST requests/responses respectively.
 *
 * @since [*next-version*]
 */
class JSON_API_Driver implements API_Driver_Interface {
	/**
	 * The HTTP content type header.
	 *
	 * @since [*next-version*]
	 */
	const H_CONTENT_TYPE = 'Content-Type';

	/**
	 * The HTTP content type acceptance header.
	 *
	 * @since [*next-version*]
	 */
	const H_ACCEPT = 'Accept';

	/**
	 * The HTTP content length header.
	 *
	 * @since [*next-version*]
	 */
	const H_CONTENT_LENGTH = 'Content-Length';

	/**
	 * The JSON HTTP content type string.
	 *
	 * @since [*next-version*]
	 */
	const JSON_CONTENT_TYPE = 'application/json';

	/**
	 * The driver to decorate.
	 *
	 * @since [*next-version*]
	 *
	 * @var API_Driver_Interface
	 */
	protected $driver;

	/**
	 * Constructor.
	 *
	 * @since [*next-version*]
	 *
	 * @param API_Driver_Interface $driver
	 */
	public function __construct( API_Driver_Interface $driver ) {
		$this->driver = $driver;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 */
	public function send( Request $request ) {
		// Encode the request before sending it
		$request = $this->encode_request( $request );

		// Delegate the actual sending to the internal driver
		$response = $this->driver->send( $request );

		// Decode the response before returning it
		$response = $this->decode_response( $response );

		return $response;
	}

	/**
	 * Encodes the request body into a JSON string and ensures the request headers are correctly set.
	 *
	 * @since [*next-version*]
	 *
	 * @param Request $request The request to encode.
	 *
	 * @return Request The encoded request.
	 */
	protected function encode_request( Request $request ) {
		// Add the header that tells the remote that we accept JSON responses
		if (empty($request->headers[ static::H_ACCEPT ])) {
			$request->headers[ static::H_ACCEPT ] = static::JSON_CONTENT_TYPE;
		}

		// For POST requests, encode the body and set the content type and length
		if ( $request->type === Request::TYPE_POST ) {
			$request->body = json_encode( $request->body );
			$headers[ static::H_CONTENT_TYPE ] = static::JSON_CONTENT_TYPE;
			$headers[ static::H_CONTENT_LENGTH ] = strlen( $request->body );
		}

		return $request;
	}

	/**
	 * Ensures that the response is decoded from JSON.
	 *
	 * @since [*next-version*]
	 *
	 * @param Response $response The response.
	 *
	 * @return Response The decoded response.
	 */
	protected function decode_response( Response $response ) {
		// Get the content type header
		$content_type = isset( $response->headers[ static::H_CONTENT_TYPE ] )
			? $response->headers[ static::H_CONTENT_TYPE ]
			: '';

		// If the content type is JSON, decode the body
		if ( $content_type === static::JSON_CONTENT_TYPE ) {
			$response->body = json_decode( $response->body );
		}

		return $response;
	}
}
