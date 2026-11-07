<?php
/**
 * Standalone tests for the block checkout preferred-days AJAX handler.
 *
 * Covers PR_DHL_Blocks_Integration::handle_get_preferred_days() without a WordPress
 * bootstrap. The JSON responders throw so each call stops where WordPress would die.
 *
 * Run: php tests/test-preferred-days-ajax.php
 */

namespace Automattic\WooCommerce\Blocks\Integrations {
	if ( ! interface_exists( __NAMESPACE__ . '\IntegrationInterface' ) ) {
		interface IntegrationInterface {}
	}
}

namespace {
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}

	/**
	 * Carries a JSON response out of the handler. Extends Error so the handler's
	 * catch ( Exception ) block does not swallow it.
	 */
	if ( ! class_exists( 'PR_DHL_Test_Json_Response' ) ) {
		class PR_DHL_Test_Json_Response extends \Error {
			/** @var bool|null Whether the response was a success. */
			public $success;

			/** @var mixed The response payload. */
			public $data;

			/**
			 * Stores the response.
			 *
			 * @param bool|null $success Whether the response was a success.
			 * @param mixed     $data    The response payload.
			 */
			public function __construct( $success, $data ) {
				parent::__construct( 'json' );
				$this->success = $success;
				$this->data    = $data;
			}
		}
	}

	if ( ! function_exists( 'wp_verify_nonce' ) ) {
		function wp_verify_nonce( $nonce, $action ) {
			return 'valid' === $nonce && 'pr_dhl_nonce' === $action;
		}
	}
	if ( ! function_exists( 'wp_unslash' ) ) {
		function wp_unslash( $value ) {
			return $value;
		}
	}
	if ( ! function_exists( 'sanitize_text_field' ) ) {
		function sanitize_text_field( $str ) {
			return is_array( $str ) ? '' : trim( (string) $str );
		}
	}
	if ( ! function_exists( 'wp_send_json_success' ) ) {
		function wp_send_json_success( $data = null, $status = null ) {
			throw new PR_DHL_Test_Json_Response( true, $data );
		}
	}
	if ( ! function_exists( 'wp_send_json_error' ) ) {
		function wp_send_json_error( $data = null, $status = null ) {
			throw new PR_DHL_Test_Json_Response( false, $data );
		}
	}
	if ( ! function_exists( 'wp_die' ) ) {
		function wp_die() {}
	}

	/**
	 * In-memory stand-in for WC_Session_Handler.
	 */
	if ( ! class_exists( 'PR_DHL_Test_Session' ) ) {
		class PR_DHL_Test_Session {
			/** @var array Session values. */
			public $data = array();

			/**
			 * Returns a session value.
			 *
			 * @param string $key     Session key.
			 * @param mixed  $default Value when the key is not set.
			 * @return mixed
			 */
			public function get( $key, $default = null ) {
				return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : $default;
			}

			/**
			 * Sets a session value.
			 *
			 * @param string $key   Session key.
			 * @param mixed  $value Value.
			 */
			public function set( $key, $value ) {
				$this->data[ $key ] = $value;
			}
		}
	}

	/**
	 * Stand-in for the front-end class: preferred services are always available.
	 */
	if ( ! class_exists( 'PR_DHL_Front_End_Paket' ) ) {
		class PR_DHL_Front_End_Paket {
			/**
			 * Reports preferred services as available.
			 *
			 * @param bool $check_day_transfer Unused.
			 * @return bool
			 */
			public function validate_extra_services_available( $check_day_transfer = false ) {
				return true;
			}
		}
	}

	/**
	 * Stand-in for PR_DHL(): records the postcode it is asked about.
	 */
	if ( ! class_exists( 'PR_DHL_Test_Plugin' ) ) {
		class PR_DHL_Test_Plugin {
			/**
			 * Returns fixed preferred days and records the postcode.
			 *
			 * @param string $postcode Shipping postcode.
			 * @return array
			 */
			public function get_dhl_preferred_day_time( $postcode ) {
				global $pr_dhl_test_days_postcode;
				$pr_dhl_test_days_postcode = $postcode;
				return array( 'preferred_day' => array( '0' => 'none', '2026-11-10' => 'Tue' ) );
			}
		}
	}

	$pr_dhl_test_wc            = new stdClass();
	$pr_dhl_test_wc->session   = new PR_DHL_Test_Session();
	$pr_dhl_test_days_postcode = null;

	if ( ! function_exists( 'WC' ) ) {
		function WC() {
			global $pr_dhl_test_wc;
			return $pr_dhl_test_wc;
		}
	}
	if ( ! function_exists( 'PR_DHL' ) ) {
		function PR_DHL() {
			return new PR_DHL_Test_Plugin();
		}
	}

	require __DIR__ . '/../includes/class-pr-dhl-blocks-integration.php';

	$failures = 0;

	/**
	 * Assert equality and report the outcome.
	 *
	 * @param string $label Human readable test name.
	 * @param mixed  $got   Actual value.
	 * @param mixed  $want  Expected value.
	 */
	function pr_dhl_assert( $label, $got, $want ) {
		global $failures;
		if ( $got === $want ) {
			echo "PASS  {$label}\n";
			return;
		}
		++$failures;
		echo "FAIL  {$label}\n      got:  " . var_export( $got, true ) . "\n      want: " . var_export( $want, true ) . "\n";
	}

	/**
	 * Runs the handler with the given POST body and returns the JSON response.
	 *
	 * @param array $post POST fields.
	 * @return PR_DHL_Test_Json_Response
	 */
	function pr_dhl_get_preferred_days( array $post ) {
		$_POST       = $post;
		$integration = ( new \ReflectionClass( \PR_DHL_Blocks_Integration::class ) )->newInstanceWithoutConstructor();
		try {
			$integration->handle_get_preferred_days();
		} catch ( PR_DHL_Test_Json_Response $response ) {
			return $response;
		}
		return new PR_DHL_Test_Json_Response( null, 'no response' );
	}

	$address = array(
		'shipping_country'  => 'DE',
		'shipping_postcode' => ' 53113 ',
		'shipping_city'     => 'Bonn',
	);

	// A concurrent Store API request saved the session without the DHL address.
	$pr_dhl_test_wc->session->data = array();
	$response                      = pr_dhl_get_preferred_days(
		array(
			'nonce' => 'valid',
			'data'  => $address,
		)
	);
	pr_dhl_assert( 'Posted address: days load even when the session has no checkout data', $response->success, true );
	pr_dhl_assert( 'Posted address: the days come back to the block', $response->data['preferredDays'], array( '0' => 'none', '2026-11-10' => 'Tue' ) );
	pr_dhl_assert( 'Posted address: the sanitized postcode is used', $pr_dhl_test_days_postcode, '53113' );
	pr_dhl_assert( 'Posted address: the session is not written, so concurrent session saves are not overwritten', $pr_dhl_test_wc->session->data, array() );

	$pr_dhl_test_wc->session->data = array( 'pr_dhl_checkout_post_data' => array( 'shipping_postcode' => '99999' ) );
	pr_dhl_get_preferred_days(
		array(
			'nonce' => 'valid',
			'data'  => $address,
		)
	);
	pr_dhl_assert( 'Posted address wins over an older session address', $pr_dhl_test_days_postcode, '53113' );

	$pr_dhl_test_wc->session->data = array(
		'pr_dhl_checkout_post_data' => array(
			'shipping_country'  => 'DE',
			'shipping_postcode' => '10115',
		),
	);
	$response                      = pr_dhl_get_preferred_days( array( 'nonce' => 'valid' ) );
	pr_dhl_assert( 'No posted address (cached old script): falls back to the session', $response->success, true );
	pr_dhl_assert( 'No posted address (cached old script): the session postcode is used', $pr_dhl_test_days_postcode, '10115' );

	$pr_dhl_test_wc->session->data = array();
	$response                      = pr_dhl_get_preferred_days( array( 'nonce' => 'valid' ) );
	pr_dhl_assert( 'No posted address and no session data: error', $response->data['message'] ?? null, 'No checkout data found.' );

	$response = pr_dhl_get_preferred_days(
		array(
			'nonce' => 'bad',
			'data'  => $address,
		)
	);
	pr_dhl_assert( 'Invalid nonce is rejected', $response->data['message'] ?? null, 'Invalid nonce' );

	echo $failures ? "\n{$failures} failure(s)\n" : "\nAll tests passed\n";
	exit( $failures ? 1 : 0 );
}
