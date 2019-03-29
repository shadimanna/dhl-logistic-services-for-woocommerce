<?php

namespace PR\DHL\REST_API;

/**
 * Utility functions for dealing with URLs.
 *
 * @since [*next-version*]
 */
class URL_Utils {
	/**
	 * Merges a base URL with a relative route.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $base_url The base URL.
	 * @param string $route    The relative route.
	 *
	 * @return string The combined URL.
	 */
	public static function merge_url_and_route( $base_url, $route ) {
		return rtrim( $base_url, '/' ) . '/' . ltrim( $route, '/' );
	}
}
