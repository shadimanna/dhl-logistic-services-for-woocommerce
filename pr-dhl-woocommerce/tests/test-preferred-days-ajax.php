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
	class PR_DHL_Test_Json_Response extends \Error {
		public $success;
		public $data;

		public function __construct( $success, $data ) {
			parent::__construct( 'json' );
			$this->success = $success;
			$this->data    = $data;
		}
	}

	function wp_verify_nonce( $nonce, $action ) {
		return 'valid' === $nonce && 'pr_dhl_nonce' === $action;
	}
	function wp_unslash( $value ) {
		return $value;
	}
	function sanitize_text_field( $str ) {
		return is_array( $str ) ? '' : trim( (string) $str );
	}
	function wp_send_json_success( $data = null, $status = null ) {
		throw new PR_DHL_Test_Json_Response( true, $data );
	}
	function wp_send_json_error( $data = null, $status = null ) {
		throw new PR_DHL_Test_Json_Response( false, $data );
	}
	function wp_die() {}

	class PR_DHL_Test_Session {
		public $data = array();

		public function get( $key, $default = null ) {
			return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : $default;
		}

		public function set( $key, $value ) {
			$this->data[ $key ] = $value;
		}
	}

	class PR_DHL_Front_End_Paket {
		public function validate_extra_services_available( $check_day_transfer = false ) {
			return true;
		}
	}

	$pr_dhl_test_wc            = new stdClass();
	$pr_dhl_test_wc->session   = new PR_DHL_Test_Session();
	$pr_dhl_test_days_postcode = null;

	function WC() {
		global $pr_dhl_test_wc;
		return $pr_dhl_test_wc;
	}

	class PR_DHL_Test_Plugin {
		public function get_dhl_preferred_day_time( $postcode ) {
			global $pr_dhl_test_days_postcode;
			$pr_dhl_test_days_postcode = $postcode;
			return array( 'preferred_day' => array( '0' => 'none', '2026-11-10' => 'Tue' ) );
		}
	}

	function PR_DHL() {
		return new PR_DHL_Test_Plugin();
	}

	require __DIR__ . '/../includes/class-pr-dhl-blocks-integration.php';

	$failures = 0;

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
		'shipping_methods'  => array( 'flat_rate:1' ),
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
	pr_dhl_assert( 'Posted address: it is kept in the session', $pr_dhl_test_wc->session->get( 'pr_dhl_checkout_post_data' )['shipping_postcode'], '53113' );

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
	pr_dhl_assert( 'No posted address: falls back to the session', $response->success, true );
	pr_dhl_assert( 'No posted address: the session postcode is used', $pr_dhl_test_days_postcode, '10115' );

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
	pr_dhl_assert( 'Invalid nonce does not touch the session', $pr_dhl_test_wc->session->data, array() );

	echo $failures ? "\n{$failures} failure(s)\n" : "\nAll tests passed\n";
	exit( $failures ? 1 : 0 );
}
