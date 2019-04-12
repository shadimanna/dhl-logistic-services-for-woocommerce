<?php

namespace PR\DHL\Utils;

use Exception;

/**
 * A utility class for parsing arguments.
 *
 * @since [*next-version*]
 */
class Args_Parser {
	/**
	 * Parsers a given array of arguments using a specific scheme.
	 *
	 * The scheme is a `key => array` associative array, where the `key` represents the argument key and the `array`
	 * represents the scheme for that single argument. Each scheme may have the following:
	 * * `default` - the default value to use if the arg is not given
	 * * `error` - the message of the exception if the arg is not given and no `default` is in the scheme
	 * * `validate` - a validation callback that receives the arg, the args array and the scheme as arguments.
	 * * `sanitize` - a sanitization callback similar to `validate` but should return the sanitized value.
	 * * `rename` - an optional new name for the argument key.
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args   The arguments to parse.
	 * @param array $scheme The scheme to parse with.
	 *
	 * @return array The parsed arguments.
	 *
	 * @throws Exception If an argument does not exist in $args and has no `default` in the $scheme.
	 */
	public static function parse_args( $args, $scheme ) {
		$final_args = array();

		foreach ( $scheme as $key => $s_scheme ) {
			// Rename the key if "rename" was specified
			$new_key = empty( $s_scheme['rename'] ) ? $key : $s_scheme['rename'];

			// Recurse for array values and nested schemes
			if ( ! empty( $args[ $key ] ) && isset( $s_scheme[0] ) && is_array( $s_scheme[0] ) ) {
				$final_args[ $new_key ] = static::parse_args( $args[ $key ], $s_scheme );
				continue;
			}

			// If the key is not set in the args
			if ( ! isset( $args[ $key ] ) ) {
				// If no default value is given, throw
				if ( ! isset( $s_scheme['default'] ) ) {
					// If no default value is specified, throw an exception
					$message = ! isset( $s_scheme['error'] )
						? sprintf( __( 'Please specify a "%s" argument', 'pr-shipping-dhl' ), $key )
						: $s_scheme['error'];

					throw new Exception( $message );
				}
				// If a default value is specified, use that as the value
				$value = $s_scheme['default'];
			} else {
				$value = $args[ $key ];
			}

			// Call the validation function
			if ( ! empty( $s_scheme['validate'] ) && is_callable( $s_scheme['validate'] ) ) {
				call_user_func_array( $s_scheme['validate'], array( $value, $args, $scheme ) );
			}

			// Call the sanitization function and get the sanitized value
			if ( ! empty( $s_scheme['sanitize'] ) && is_callable( $s_scheme['sanitize'] ) ) {
				$value = call_user_func_array( $s_scheme['sanitize'], array( $value, $args, $scheme ) );
			}

			$final_args[ $new_key ] = $value;
		}

		return $final_args;
	}
}
