<?php
/**
 * Standalone tests for the block checkout preferred-day fee.
 *
 * Exercises PR_DHL_Extend_Block_core::store_api_callback(), add_preferred_day_fee()
 * and order_paid_preferred_day_fee() without a WordPress bootstrap. WC(), PR_DHL()
 * and the few WordPress functions the class touches are stubbed below; the class is
 * instantiated without its constructor so no hooks are registered.
 *
 * Run: php tests/test-preferred-day-fee.php
 */

namespace {
	if ( ! function_exists( 'esc_html__' ) ) {
		function esc_html__( $text, $domain = null ) {
			return $text;
		}
	}
	if ( ! function_exists( 'wc_format_decimal' ) ) {
		function wc_format_decimal( $number, $dp = false, $trim_zeros = false ) {
			return str_replace( ',', '.', trim( (string) $number ) );
		}
	}
	if ( ! function_exists( 'is_admin' ) ) {
		function is_admin() {
			return false;
		}
	}
	if ( ! function_exists( 'add_action' ) ) {
		function add_action() {}
	}
	// Only the pre-fix code calls this; stubbed so the test fails instead of
	// erroring when run against it.
	if ( ! function_exists( 'sanitize_text_field' ) ) {
		function sanitize_text_field( $str ) {
			return trim( (string) $str );
		}
	}
	if ( ! function_exists( 'wc_get_price_decimals' ) ) {
		function wc_get_price_decimals() {
			return 2;
		}
	}

	/**
	 * Minimal stand-in for WC_Session_Handler.
	 */
	if ( ! class_exists( 'PR_DHL_Test_Session' ) ) {
		class PR_DHL_Test_Session {
			public $data = array();

			public function get( $key, $default = null ) {
				return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : $default;
			}

			public function set( $key, $value ) {
				$this->data[ $key ] = $value;
			}

			public function __unset( $key ) {
				unset( $this->data[ $key ] );
			}
		}
	}

	/**
	 * Minimal stand-in for WC_Cart: just enough for add_preferred_day_fee().
	 */
	if ( ! class_exists( 'PR_DHL_Test_Cart' ) ) {
		class PR_DHL_Test_Cart {
			public $fees           = array();
			public $needs_shipping = true;

			public function needs_shipping() {
				return $this->needs_shipping;
			}

			public function get_fees() {
				return $this->fees;
			}

			public function fees_api() {
				return $this;
			}

			public function set_fees( $fees ) {
				$this->fees = $fees;
			}

			public function add_fee( $name, $amount, $taxable = false, $tax_class = '' ) {
				$this->fees[] = (object) array(
					'name'   => $name,
					'amount' => $amount,
				);
			}
		}
	}

	/**
	 * Minimal stand-in for WC_Order_Item_Fee.
	 */
	if ( ! class_exists( 'PR_DHL_Test_Order_Fee' ) ) {
		class PR_DHL_Test_Order_Fee {
			private $name;
			private $total;

			public function __construct( $name, $total ) {
				$this->name  = $name;
				$this->total = $total;
			}

			public function get_name() {
				return $this->name;
			}

			public function get_total() {
				return $this->total;
			}
		}
	}

	/**
	 * Minimal stand-in for WC_Order. Named WC_Order so the type hint on the
	 * production method accepts it.
	 */
	if ( ! class_exists( 'WC_Order' ) ) {
		class WC_Order {
			public $fees = array();

			public function get_fees() {
				return $this->fees;
			}
		}
	}

	$pr_dhl_test_session  = new PR_DHL_Test_Session();
	$pr_dhl_test_settings = array( 'dhl_preferred_day_cost' => '5' );

	if ( ! function_exists( 'WC' ) ) {
		function WC() {
			global $pr_dhl_test_session;
			return (object) array( 'session' => $pr_dhl_test_session );
		}
	}

	if ( ! function_exists( 'PR_DHL' ) ) {
		function PR_DHL() {
			return new class() {
				public function get_shipping_dhl_settings() {
					global $pr_dhl_test_settings;
					return $pr_dhl_test_settings;
				}
			};
		}
	}

	require __DIR__ . '/../includes/class-pr-dhl-extend-block-core.php';

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
			echo "PASS: {$label}\n";
			return;
		}
		++$failures;
		echo "FAIL: {$label}\n";
		echo '      want: ' . var_export( $want, true ) . "\n";
		echo '      got:  ' . var_export( $got, true ) . "\n";
	}

	function pr_dhl_new_block_core() {
		$ref = new \ReflectionClass( \PR_DHL_Extend_Block_core::class );
		return $ref->newInstanceWithoutConstructor();
	}

	/**
	 * Run the Store API callback with the given client payload, then calculate
	 * cart fees. Returns the resulting fees as [ name, amount ] pairs.
	 */
	function pr_dhl_fees_after_callback( $payload ) {
		global $pr_dhl_test_session;
		$pr_dhl_test_session->data = array();

		$core = pr_dhl_new_block_core();
		$core->store_api_callback( $payload );

		$cart = new PR_DHL_Test_Cart();
		$core->add_preferred_day_fee( $cart );

		return pr_dhl_fee_pairs( $cart );
	}

	function pr_dhl_fee_pairs( PR_DHL_Test_Cart $cart ) {
		$pairs = array();
		foreach ( $cart->get_fees() as $fee ) {
			$pairs[] = array( $fee->name, (float) $fee->amount );
		}
		return $pairs;
	}

	// The client only gets to say that a day was selected. The amount and the
	// label come from the merchant setting, whatever price the request carries.
	foreach ( array( 0.01, 0.0001, 99 ) as $client_price ) {
		$fees = pr_dhl_fees_after_callback(
			array(
				'action' => 'update_preferred_day_fee',
				'price'  => $client_price,
				'label'  => 'x',
			)
		);
		pr_dhl_assert( "Client price {$client_price} still charges the configured fee", $fees, array( array( 'DHL Delivery Day', 5.0 ) ) );
	}

	// Deselecting (the block JS sends price 0 and an empty label) removes the fee.
	$fees = pr_dhl_fees_after_callback(
		array(
			'action' => 'update_preferred_day_fee',
			'price'  => 0,
			'label'  => '',
		)
	);
	pr_dhl_assert( 'Deselecting the preferred day charges no fee', $fees, array() );

	// A payload for another action clears the selection too.
	$fees = pr_dhl_fees_after_callback( array( 'action' => 'something_else' ) );
	pr_dhl_assert( 'Unrelated action charges no fee', $fees, array() );

	// Sessions written by the previous version hold a client-chosen price. Those
	// keys must be ignored, and cleared, so an upgrade cannot carry the bug over.
	$pr_dhl_test_session->data = array(
		'pr_dhl_preferred_day_fee'   => 0.01,
		'pr_dhl_preferred_day_label' => 'DHL Delivery Day',
	);
	$core = pr_dhl_new_block_core();
	$cart = new PR_DHL_Test_Cart();
	$core->add_preferred_day_fee( $cart );
	pr_dhl_assert( 'Stale session price from the old version is not charged', pr_dhl_fee_pairs( $cart ), array() );

	$core->store_api_callback(
		array(
			'action' => 'update_preferred_day_fee',
			'price'  => 0.01,
			'label'  => 'x',
		)
	);
	pr_dhl_assert( 'Callback clears the old session fee key', array_key_exists( 'pr_dhl_preferred_day_fee', $pr_dhl_test_session->data ), false );
	pr_dhl_assert( 'Callback clears the old session label key', array_key_exists( 'pr_dhl_preferred_day_label', $pr_dhl_test_session->data ), false );

	// A cart that no longer needs shipping drops the selection.
	$cart                 = new PR_DHL_Test_Cart();
	$cart->needs_shipping = false;
	$core->add_preferred_day_fee( $cart );
	pr_dhl_assert( 'Cart without shipping charges no fee', pr_dhl_fee_pairs( $cart ), array() );
	$cart = new PR_DHL_Test_Cart();
	$core->add_preferred_day_fee( $cart );
	pr_dhl_assert( 'Selection is forgotten once the cart stopped needing shipping', pr_dhl_fee_pairs( $cart ), array() );

	// Placing an order empties the cart; the selection must not survive into the
	// next cart of the same session.
	$pr_dhl_test_session->data = array();
	$core->store_api_callback(
		array(
			'action' => 'update_preferred_day_fee',
			'price'  => 5,
			'label'  => 'DHL Delivery Day',
		)
	);
	if ( method_exists( $core, 'clear_preferred_day_selection' ) ) {
		$core->clear_preferred_day_selection();
		$cart = new PR_DHL_Test_Cart();
		$core->add_preferred_day_fee( $cart );
		pr_dhl_assert( 'Selection is dropped when the cart is emptied', pr_dhl_fee_pairs( $cart ), array() );
	} else {
		++$failures;
		echo "FAIL: clear_preferred_day_selection() does not exist\n";
	}

	// An existing DHL fee is replaced, other fees are kept.
	$pr_dhl_test_session->data = array();
	$core->store_api_callback(
		array(
			'action' => 'update_preferred_day_fee',
			'price'  => 5,
			'label'  => 'DHL Delivery Day',
		)
	);
	$cart       = new PR_DHL_Test_Cart();
	$cart->fees = array(
		(object) array( 'name' => 'DHL Delivery Day', 'amount' => 0.01 ),
		(object) array( 'name' => 'Gift wrap', 'amount' => 2 ),
	);
	$core->add_preferred_day_fee( $cart );
	pr_dhl_assert( 'Existing DHL fee is replaced and other fees are kept', pr_dhl_fee_pairs( $cart ), array( array( 'Gift wrap', 2.0 ), array( 'DHL Delivery Day', 5.0 ) ) );

	// An empty cost setting means the service is free.
	$pr_dhl_test_settings = array( 'dhl_preferred_day_cost' => '' );
	$fees = pr_dhl_fees_after_callback(
		array(
			'action' => 'update_preferred_day_fee',
			'price'  => 99,
			'label'  => 'x',
		)
	);
	pr_dhl_assert( 'Empty cost setting charges no fee even when the client sends a price', $fees, array() );
	$pr_dhl_test_settings = array( 'dhl_preferred_day_cost' => '5' );

	// order_paid_preferred_day_fee() guards the order meta: the preferred day is only
	// saved when the order carries the configured fee.
	$ref = new \ReflectionClass( \PR_DHL_Extend_Block_core::class );
	if ( ! $ref->hasMethod( 'order_paid_preferred_day_fee' ) ) {
		++$failures;
		echo "FAIL: order_paid_preferred_day_fee() does not exist\n";
	} else {
		$method = $ref->getMethod( 'order_paid_preferred_day_fee' );
		$method->setAccessible( true );
		$core = pr_dhl_new_block_core();

		$order = new WC_Order();
		pr_dhl_assert( 'Order without the fee did not pay for the preferred day', $method->invoke( $core, $order ), false );

		$order->fees = array( new PR_DHL_Test_Order_Fee( 'DHL Delivery Day', '0.01' ) );
		pr_dhl_assert( 'Order with a fee below the configured cost did not pay', $method->invoke( $core, $order ), false );

		$order->fees = array( new PR_DHL_Test_Order_Fee( 'DHL Delivery Day', '5.00' ) );
		pr_dhl_assert( 'Order with the configured fee paid for the preferred day', $method->invoke( $core, $order ), true );

		// The check compares at the store's price decimals: a cost with more decimals
		// than the store shows must match the fee line whether WooCommerce kept the
		// extra digits on the line or rounded them away.
		$pr_dhl_test_settings = array( 'dhl_preferred_day_cost' => '5.556' );
		$order->fees          = array( new PR_DHL_Test_Order_Fee( 'DHL Delivery Day', '5.556' ) );
		pr_dhl_assert( 'Fee line keeping extra decimals counts as paid', $method->invoke( $core, $order ), true );
		$pr_dhl_test_settings = array( 'dhl_preferred_day_cost' => '5.554' );
		$order->fees          = array( new PR_DHL_Test_Order_Fee( 'DHL Delivery Day', '5.55' ) );
		pr_dhl_assert( 'Fee line rounded to price decimals counts as paid', $method->invoke( $core, $order ), true );

		$pr_dhl_test_settings = array( 'dhl_preferred_day_cost' => '' );
		$order->fees          = array();
		pr_dhl_assert( 'Free preferred day counts as paid', $method->invoke( $core, $order ), true );
		$pr_dhl_test_settings = array( 'dhl_preferred_day_cost' => '5' );
	}

	echo $failures ? "\n{$failures} failure(s)\n" : "\nAll tests passed\n";
	exit( $failures ? 1 : 0 );
}
