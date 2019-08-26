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
	 * This function ensures that the concatenation of the base URL and the route does not result in duplicate
	 * forward slashes in the final URL. It does so by trimming any trailing forward slashes in the base URL and
	 * any leading or trailing forward slashes in the route URL before concatenating them with a forward slash between
	 * them. This also means that the resulting URL will never have a trailing forward slash.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $base_url The base URL.
	 * @param string $route    The relative route.
	 *
	 * @return string The combined URL.
	 */
	public static function merge_url_and_route( $base_url, $route ) {
		return rtrim( $base_url, '/' ) . '/' . trim( $route, '/' );
	}
}
