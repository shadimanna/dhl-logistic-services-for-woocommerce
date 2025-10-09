<?php

namespace PR\DHL\REST_API\Drivers;

use PR\DHL\REST_API\Interfaces\API_Driver_Interface;
use PR\DHL\REST_API\Request;
use PR\DHL\REST_API\Response;
use PR_DHL_WC;

/**
 * A REST API driver decorator that automatically logs requests and responses.
 *
 * This is a REST API driver DECORATOR class, which means that it is not a standalone driver but instead decorates
 * another driver. It does so to log the parameters and return values of the "inner" driver.
 *
 * @since [*next-version*]
 *
 * @see API_Driver_Interface
 */
class Logging_Driver implements API_Driver_Interface {
	/**
	 * The plugin instance.
	 *
	 * @since [*next-version*]
	 *
	 * @var PR_DHL_WC
	 */
	protected $plugin;

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
	 * @param PR_DHL_WC            $plugin The plugin instance to use for logging.
	 * @param API_Driver_Interface $driver The driver instance to decorate.
	 */
	public function __construct( PR_DHL_WC $plugin, API_Driver_Interface $driver ) {
		$this->plugin = $plugin;
		$this->driver = $driver;
	}

	/**
	 * {@inheritdoc}
	 *
	 * Before invoking the inner driver's {@link send()} method, it logs the request data.
	 * The response returned from the inner driver is also logged, after which it is returned.
	 *
	 * @since [*next-version*]
	 */
	public function send( Request $request ) {
		// Log the request
		$this->log_request( 'Request:', $request );

		// Send the request using the inner driver
		$response = $this->driver->send( $request );

		// Log the response
		$this->log_response( 'Response:', $response );

		// Return the response from the inner driver
		return $response;
	}

	/**
	 * Logs a request.
	 *
	 * @since [*next-version*]
	 *
	 * @param string  $message Prefix message to include in the log.
	 * @param Request $request The request to log.
	 */
	protected function log_request( string $message, Request $request ) {
		$request_info = array(
			'type'    => $this->get_request_type_name( $request->type ),
			'url'     => $request->url,
			'params'  => $request->params,
			'body'    => empty( $request->body ) ? '' : json_decode( $request->body, true ),
		);

		$this->plugin->log_msg( sprintf( '%s %s', $message, print_r( $request_info, true ) ) );
	}

	/**
	 * Logs a response.
	 *
	 * @since [*next-version*]
	 *
	 * @param string   $message Prefix message to include in the log.
	 * @param Response $response The response to log.
	 */
	protected function log_response( string $message, Response $response ) {
		if ( isset( $response->headers['Content-Type'] ) && $response->headers['Content-Type'] === 'application/pdf' ) {
			$body = '[PDF data]';
		} else {
			$decoded = json_decode( $response->body, true );
			$body    = $decoded ?: $response->body;

			// Remove b64 data from response body for cleaner logs.
			if ( is_array( $body ) ) {
				$body = $this->remove_b64_data( $body );
			}
		}

		$response_info = array(
			'status'  => $response->status,
			'body'    => $body,
		);

		$this->plugin->log_msg( sprintf( '%s %s', $message, print_r( $response_info, true ) ) );
	}

	/**
	 * Retrieves the name for a request type.
	 *
	 * @since [*next-version*]
	 *
	 * @param int $type The request type. See the constants in the {@link Request} class.
	 *
	 * @return string|int The name of the request type, or the parameter if the request type is unknown.
	 */
	protected function get_request_type_name( $type ) {
		if ( $type === Request::TYPE_GET ) {
			return 'GET';
		}

		if ( $type === Request::TYPE_POST ) {
			return 'POST';
		}

		if ( $type === Request::TYPE_DELETE ) {
			return 'DELETE';
		}

		return $type;
	}

	/**
	 * Removes b64 data from response body to keep logs clean.
	 *
	 * @param array $data The response body array.
	 *
	 * @return array The array with b64 data removed.
	 */
	protected function remove_b64_data( array $data ): array {
		if ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
			foreach ( $data['items'] as $item_key => $item ) {
				if ( isset( $item['label']['b64'] ) ) {
					$data['items'][ $item_key ]['label']['b64'] = '[Base64 PDF data removed from log]';
				}
			}
		}

		return $data;
	}
}
