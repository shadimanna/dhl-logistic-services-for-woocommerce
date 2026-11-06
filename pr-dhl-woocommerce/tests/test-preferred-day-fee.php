<?php
/**
 * Standalone tests for the block checkout preferred-day fee.
 *
 * Exercises PR_DHL_Extend_Block_core (the Store API callback, the cart fee and the
 * checks when the order is placed) and the cost PR_DHL_Blocks_Integration hands to
 * the block, without a WordPress bootstrap. WC(), PR_DHL() and the few WordPress
 * functions the classes touch are stubbed below. add_action() records callbacks so
 * the checkout tests can fire the Store API hooks in the order WooCommerce does.
 *
 * Run: php tests/test-preferred-day-fee.php
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
	if ( ! function_exists( 'esc_html__' ) ) {
		function esc_html__( $text, $domain = null ) {
			return $text;
		}
	}
	$pr_dhl_test_decimal_separator = '.';

	/**
	 * Like WooCommerce's wc_format_decimal() without $dp: the shop's decimal separator
	 * becomes a dot, then everything but digits, minus and the last dot is dropped.
	 */
	if ( ! function_exists( 'wc_format_decimal' ) ) {
		function wc_format_decimal( $number, $dp = false, $trim_zeros = false ) {
			global $pr_dhl_test_decimal_separator;
			$number = str_replace( $pr_dhl_test_decimal_separator, '.', trim( (string) $number ) );
			return preg_replace( '/\.(?![^.]+$)|[^0-9.-]/', '', $number );
		}
	}
	if ( ! function_exists( 'is_admin' ) ) {
		function is_admin() {
			return false;
		}
	}
	$pr_dhl_test_hooks = array();

	if ( ! function_exists( 'add_action' ) ) {
		function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
			global $pr_dhl_test_hooks;
			$pr_dhl_test_hooks[ $hook ][ $priority ][] = array( $callback, $accepted_args );
		}
	}
	if ( ! function_exists( 'do_action' ) ) {
		function do_action( $hook, ...$args ) {
			global $pr_dhl_test_hooks;
			if ( empty( $pr_dhl_test_hooks[ $hook ] ) ) {
				return;
			}
			ksort( $pr_dhl_test_hooks[ $hook ] );
			foreach ( $pr_dhl_test_hooks[ $hook ] as $callbacks ) {
				foreach ( $callbacks as $callback ) {
					call_user_func_array( $callback[0], array_slice( $args, 0, $callback[1] ) );
				}
			}
		}
	}
	if ( ! function_exists( 'wc_clean' ) ) {
		function wc_clean( $value ) {
			return is_scalar( $value ) ? trim( (string) $value ) : $value;
		}
	}

	$pr_dhl_test_page = 'checkout';

	if ( ! function_exists( 'is_singular' ) ) {
		/**
		 * Whether the test request shows a single post or page.
		 *
		 * @return bool
		 */
		function is_singular() {
			global $pr_dhl_test_page;
			return 'cart' !== $pr_dhl_test_page;
		}
	}
	if ( ! function_exists( 'has_block' ) ) {
		/**
		 * Whether the test page contains the given block.
		 *
		 * @param string $block_name Block name.
		 * @return bool
		 */
		function has_block( $block_name ) {
			global $pr_dhl_test_page;
			return 'woocommerce/checkout' === $block_name && 'block-page' === $pr_dhl_test_page;
		}
	}
	if ( ! function_exists( 'is_checkout' ) ) {
		/**
		 * Whether the test request is a checkout page, endpoints included.
		 *
		 * @return bool
		 */
		function is_checkout() {
			global $pr_dhl_test_page;
			return in_array( $pr_dhl_test_page, array( 'checkout', 'order-received', 'order-pay' ), true );
		}
	}
	if ( ! function_exists( 'is_wc_endpoint_url' ) ) {
		/**
		 * Whether the test request is a WooCommerce endpoint such as order-received.
		 *
		 * @param string|false $endpoint Endpoint to check, unused here.
		 * @return bool
		 */
		function is_wc_endpoint_url( $endpoint = false ) {
			global $pr_dhl_test_page;
			return in_array( $pr_dhl_test_page, array( 'order-received', 'order-pay' ), true );
		}
	}
	$pr_dhl_test_done_actions = array();

	if ( ! function_exists( 'did_action' ) ) {
		/**
		 * How often the test marked an action as fired.
		 *
		 * @param string $hook Action name.
		 * @return int
		 */
		function did_action( $hook ) {
			global $pr_dhl_test_done_actions;
			return isset( $pr_dhl_test_done_actions[ $hook ] ) ? $pr_dhl_test_done_actions[ $hook ] : 0;
		}
	}
	if ( ! function_exists( '__' ) ) {
		function __( $text, $domain = null ) {
			return $text;
		}
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
			public $calculations   = 0;

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

			/**
			 * Recalculate the fees the way WC_Cart::calculate_totals() does.
			 *
			 * @return void
			 */
			public function calculate_totals() {
				++$this->calculations;
				$this->fees = array();
				do_action( 'woocommerce_cart_calculate_fees', $this );
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
	 * production method accepts it. Like WC_Data, meta changes stay in memory until
	 * save() or save_meta_data() writes the changed keys to $saved.
	 */
	if ( ! class_exists( 'WC_Order' ) ) {
		class WC_Order {
			public $id                 = 0;
			public $fees               = array();
			public $meta               = array();
			public $saved              = array();
			public $shipping_address_1 = 'Teststrasse 1';
			private $changed           = array();

			public function __clone() {
				$this->changed = array();
			}

			public function get_id() {
				return $this->id;
			}

			public function get_fees() {
				return $this->fees;
			}

			public function get_shipping_address_1() {
				return $this->shipping_address_1;
			}

			public function get_meta( $key ) {
				return isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : '';
			}

			public function update_meta_data( $key, $value ) {
				$this->meta[ $key ]    = $value;
				$this->changed[ $key ] = true;
			}

			public function delete_meta_data( $key ) {
				unset( $this->meta[ $key ] );
				$this->changed[ $key ] = true;
			}

			public function save_meta_data() {
				foreach ( array_keys( $this->changed ) as $key ) {
					if ( array_key_exists( $key, $this->meta ) ) {
						$this->saved[ $key ] = $this->meta[ $key ];
					} else {
						unset( $this->saved[ $key ] );
					}
				}
				$this->changed = array();
			}

			public function save() {
				$this->save_meta_data();
				return $this->id;
			}
		}
	}

	/**
	 * Minimal stand-in for WP_REST_Request: array access to the request params.
	 */
	if ( ! class_exists( 'WP_REST_Request' ) ) {
		class WP_REST_Request implements ArrayAccess {
			private $params;

			public function __construct( array $params = array() ) {
				$this->params = $params;
			}

			public function offsetExists( $offset ): bool {
				return isset( $this->params[ $offset ] );
			}

			#[\ReturnTypeWillChange]
			public function offsetGet( $offset ) {
				return isset( $this->params[ $offset ] ) ? $this->params[ $offset ] : null;
			}

			public function offsetSet( $offset, $value ): void {
				$this->params[ $offset ] = $value;
			}

			public function offsetUnset( $offset ): void {
				unset( $this->params[ $offset ] );
			}
		}
	}

	/**
	 * Load an order the way wc_get_order() does: a new object that holds what is
	 * saved, with no pending changes, and saves into the same storage.
	 */
	if ( ! function_exists( 'wc_get_order' ) ) {
		function wc_get_order( $order_id ) {
			global $pr_dhl_test_orders;
			if ( ! isset( $pr_dhl_test_orders[ $order_id ] ) ) {
				return false;
			}
			$stored       = $pr_dhl_test_orders[ $order_id ];
			$loaded       = clone $stored;
			$loaded->saved = &$stored->saved;
			$loaded->meta = $stored->saved;
			return $loaded;
		}
	}

	/**
	 * Stand-in for PR_DHL_WC_Order::save_dhl_label_items(), with the same steps: load
	 * the order again, merge into the saved items, save. It never sees meta that is
	 * still unsaved on the order object the Store API passes around.
	 */
	if ( ! class_exists( 'PR_DHL_Test_Label_Store' ) ) {
		class PR_DHL_Test_Label_Store {
			public function save_dhl_label_items( $order_id, $items ) {
				$order = wc_get_order( $order_id );
				$saved = $order->get_meta( '_pr_shipment_dhl_label_items' );

				$order->update_meta_data( '_pr_shipment_dhl_label_items', is_array( $saved ) ? array_merge( $saved, $items ) : $items );
				$order->save();
			}
		}
	}

	/**
	 * Stand-in for the PR_DHL() plugin instance.
	 */
	if ( ! class_exists( 'PR_DHL_Test_Plugin' ) ) {
		class PR_DHL_Test_Plugin {
			public function get_shipping_dhl_settings() {
				global $pr_dhl_test_settings;
				return $pr_dhl_test_settings;
			}

			public function get_pr_dhl_wc_order() {
				return new PR_DHL_Test_Label_Store();
			}

			public function is_droppoint( $address_type, $address_1 ) {
				return false;
			}
		}
	}

	$pr_dhl_test_session  = new PR_DHL_Test_Session();
	$pr_dhl_test_settings = array( 'dhl_preferred_day_cost' => '5' );
	$pr_dhl_test_orders   = array();
	$pr_dhl_test_cart     = null;

	if ( ! function_exists( 'WC' ) ) {
		function WC() {
			global $pr_dhl_test_session, $pr_dhl_test_cart;
			return (object) array(
				'session' => $pr_dhl_test_session,
				'cart'    => $pr_dhl_test_cart,
			);
		}
	}

	if ( ! function_exists( 'PR_DHL' ) ) {
		function PR_DHL() {
			return new PR_DHL_Test_Plugin();
		}
	}

	require __DIR__ . '/../includes/class-pr-dhl-extend-block-core.php';
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
	 *
	 * @param array $payload        Data the client sends to cart/extensions.
	 * @param bool  $start_selected Whether a day is already selected in the session.
	 * @return array
	 */
	function pr_dhl_fees_after_callback( $payload, $start_selected = false ) {
		global $pr_dhl_test_session;
		$pr_dhl_test_session->data = $start_selected ? array( 'pr_dhl_preferred_day_selected' => true ) : array();

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
		),
		true
	);
	pr_dhl_assert( 'Deselecting the preferred day charges no fee', $fees, array() );

	// A payload for another action clears the selection too.
	$fees = pr_dhl_fees_after_callback( array( 'action' => 'something_else' ), true );
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
	$pr_dhl_test_hooks         = array();
	$core                      = new PR_DHL_Extend_Block_core();
	$core->store_api_callback(
		array(
			'action' => 'update_preferred_day_fee',
			'price'  => 5,
			'label'  => 'DHL Delivery Day',
		)
	);
	do_action( 'woocommerce_cart_emptied', true );
	$cart = new PR_DHL_Test_Cart();
	$core->add_preferred_day_fee( $cart );
	pr_dhl_assert( 'Selection is dropped when the cart is emptied', pr_dhl_fee_pairs( $cart ), array() );

	// The block starts with no day selected on every page load, so the fee must not
	// outlive a reload of the checkout.
	$charged    = array( array( 'DHL Delivery Day', 5.0 ) );
	$page_loads = array(
		'checkout'                       => array( 'checkout', array() ),
		'checkout block on another page' => array( 'block-page', array() ),
		'order-received'                 => array( 'order-received', $charged ),
		'order-pay'                      => array( 'order-pay', $charged ),
		'cart'                           => array( 'cart', $charged ),
	);
	foreach ( $page_loads as $label => $page_load ) {
		list( $page, $want ) = $page_load;

		$pr_dhl_test_page          = $page;
		$pr_dhl_test_session->data = array();
		$pr_dhl_test_hooks         = array();
		$core                      = new PR_DHL_Extend_Block_core();
		$core->store_api_callback(
			array(
				'action' => 'update_preferred_day_fee',
				'price'  => 5,
				'label'  => 'DHL Delivery Day',
			)
		);
		do_action( 'template_redirect' );
		$cart = new PR_DHL_Test_Cart();
		$core->add_preferred_day_fee( $cart );
		pr_dhl_assert( "Loading the {$label} after a day was selected", pr_dhl_fee_pairs( $cart ), $want );
	}
	$pr_dhl_test_page = 'checkout';

	// Totals not calculated yet are left to the checkout block's own preload.
	$pr_dhl_test_session->data = array();
	$pr_dhl_test_hooks         = array();
	$core                      = new PR_DHL_Extend_Block_core();
	$pr_dhl_test_cart          = new PR_DHL_Test_Cart();
	$core->store_api_callback(
		array(
			'action' => 'update_preferred_day_fee',
			'price'  => 5,
			'label'  => 'DHL Delivery Day',
		)
	);
	do_action( 'template_redirect' );
	pr_dhl_assert( 'Checkout load does not calculate totals that were not calculated yet', $pr_dhl_test_cart->calculations, 0 );
	$pr_dhl_test_cart->calculate_totals();
	pr_dhl_assert( 'The block preload then calculates them without the fee', pr_dhl_fee_pairs( $pr_dhl_test_cart ), array() );
	$pr_dhl_test_cart = null;

	// The cart session can calculate totals before template_redirect and the checkout
	// block then reuses them, so they must be calculated again without the fee.
	$pr_dhl_test_session->data = array();
	$pr_dhl_test_hooks         = array();
	$core                      = new PR_DHL_Extend_Block_core();
	$pr_dhl_test_cart          = new PR_DHL_Test_Cart();
	$core->store_api_callback(
		array(
			'action' => 'update_preferred_day_fee',
			'price'  => 5,
			'label'  => 'DHL Delivery Day',
		)
	);
	$pr_dhl_test_cart->calculate_totals();
	$pr_dhl_test_done_actions['woocommerce_after_calculate_totals'] = 1;
	do_action( 'template_redirect' );
	pr_dhl_assert( 'Totals calculated before the checkout loads drop the fee', pr_dhl_fee_pairs( $pr_dhl_test_cart ), array() );
	$pr_dhl_test_done_actions = array();
	$pr_dhl_test_cart         = null;

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

	/**
	 * Place an order the way the Store API checkout does: the order is loaded as it
	 * was saved, the draft hook fires when the checkout creates or reuses its draft,
	 * then the request data is applied. Paying for an existing order skips the draft
	 * hook.
	 *
	 * @param WC_Order    $order         Order as an earlier attempt saved it, if any.
	 * @param string|null $preferred_day extensions.pr-dhl.preferredDay, null to send no DHL data.
	 * @param bool        $from_cart     False for the pay-for-order route.
	 * @param bool        $new_process   False when an earlier call in the same PHP process,
	 *                                   such as a Store API batch, left this order object
	 *                                   in memory instead of loading it again.
	 * @return string|null The preferred day saved on the order.
	 */
	function pr_dhl_place_order( WC_Order $order, $preferred_day, $from_cart = true, $new_process = true ) {
		pr_dhl_start_request( $order, $new_process );

		$extensions = null === $preferred_day ? array() : array( 'pr-dhl' => array( 'preferredDay' => $preferred_day ) );
		$request    = new WP_REST_Request( array( 'extensions' => $extensions ) );

		if ( $from_cart ) {
			do_action( 'woocommerce_store_api_checkout_update_order_meta', $order );
		}
		do_action( 'woocommerce_store_api_checkout_update_order_from_request', $order, $request );

		return pr_dhl_saved_day( $order );
	}

	/**
	 * Load the checkout the way WooCommerce does when a draft, pending or failed order
	 * is in the session: the order is re-synced from the cart and only the draft hook
	 * fires, without any request data. PUT requests without extensions do the same.
	 *
	 * @param WC_Order $order Order as an earlier attempt saved it.
	 * @return string|null The preferred day saved on the order.
	 */
	function pr_dhl_load_checkout( WC_Order $order ) {
		pr_dhl_start_request( $order );
		do_action( 'woocommerce_store_api_checkout_update_order_meta', $order );

		return pr_dhl_saved_day( $order );
	}

	/**
	 * Start a Store API request: register the class hooks and, in a new PHP process,
	 * load the order as saved.
	 *
	 * @param WC_Order $order       The order the request works on.
	 * @param bool     $new_process Whether the order object is loaded again.
	 */
	function pr_dhl_start_request( WC_Order $order, $new_process = true ) {
		global $pr_dhl_test_hooks, $pr_dhl_test_orders;

		$pr_dhl_test_hooks = array();
		new \PR_DHL_Extend_Block_core();

		$pr_dhl_test_orders[ $order->get_id() ] = $order;
		if ( $new_process ) {
			$order->meta = $order->saved;
		}
	}

	/**
	 * The preferred day saved on the order.
	 *
	 * @param WC_Order $order The order.
	 * @return string|null
	 */
	function pr_dhl_saved_day( WC_Order $order ) {
		$items = isset( $order->saved['_pr_shipment_dhl_label_items'] ) ? $order->saved['_pr_shipment_dhl_label_items'] : array();
		return isset( $items['pr_dhl_preferred_day'] ) ? $items['pr_dhl_preferred_day'] : null;
	}

	/**
	 * A test order with the given fee lines and saved DHL label items.
	 *
	 * @param array $fees        Fee lines as [ name, total ] pairs.
	 * @param array $label_items Label items an earlier attempt saved.
	 * @return WC_Order
	 */
	function pr_dhl_test_order( array $fees, array $label_items = array() ) {
		static $next_id = 500;

		$test_order     = new WC_Order();
		$test_order->id = ++$next_id;
		foreach ( $fees as $fee ) {
			$test_order->fees[] = new PR_DHL_Test_Order_Fee( $fee[0], $fee[1] );
		}
		if ( $label_items ) {
			$test_order->saved['_pr_shipment_dhl_label_items'] = $label_items;
		}
		return $test_order;
	}

	$with_fee = array( array( 'DHL Delivery Day', '5.00' ) );

	pr_dhl_assert( 'Checkout with the fee saves the requested day', pr_dhl_place_order( pr_dhl_test_order( $with_fee ), '2026-11-10' ), '2026-11-10' );
	pr_dhl_assert( 'Checkout without the fee does not save the requested day', pr_dhl_place_order( pr_dhl_test_order( array() ), '2026-11-10' ), null );

	$pr_dhl_test_settings = array( 'dhl_preferred_day_cost' => '' );
	pr_dhl_assert( 'Free preferred day is saved without a fee', pr_dhl_place_order( pr_dhl_test_order( array() ), '2026-11-10' ), '2026-11-10' );
	$pr_dhl_test_settings = array( 'dhl_preferred_day_cost' => '5' );

	// A failed attempt, such as a validation error raised after the DHL data was
	// saved, leaves the day on the draft, and the retry reuses that draft.
	$first_attempt = array(
		'pr_dhl_preferred_day' => '2026-11-10',
		'pr_dhl_cdp_delivery'  => 'no',
	);
	$retry_order   = pr_dhl_test_order( array(), $first_attempt );
	pr_dhl_assert( 'Retry without the fee drops the day saved by the failed attempt', pr_dhl_place_order( $retry_order, '2026-11-10' ), null );
	pr_dhl_assert( 'Retry keeps the other saved DHL options', $retry_order->saved['_pr_shipment_dhl_label_items'], array( 'pr_dhl_cdp_delivery' => 'no' ) );
	pr_dhl_assert( 'Retry with "none" and no fee drops the saved day', pr_dhl_place_order( pr_dhl_test_order( array(), $first_attempt ), '0' ), null );
	pr_dhl_assert( 'Retry with the fee saves the day again', pr_dhl_place_order( pr_dhl_test_order( $with_fee, $first_attempt ), '2026-11-10' ), '2026-11-10' );
	pr_dhl_assert( 'Retry with the fee and another day saves the new day', pr_dhl_place_order( pr_dhl_test_order( $with_fee, $first_attempt ), '2026-11-12' ), '2026-11-12' );
	pr_dhl_assert( 'Retry with the fee but no day in the request keeps the paid day', pr_dhl_place_order( pr_dhl_test_order( $with_fee, $first_attempt ), '' ), '2026-11-10' );

	// A Store API batch runs both attempts in one PHP process, so the checkout keeps the
	// order object of the failed attempt, which never loaded the items saved through
	// save_dhl_label_items().
	pr_dhl_assert( 'Retry in the same batch request without the fee drops the saved day', pr_dhl_place_order( pr_dhl_test_order( array(), $first_attempt ), '2026-11-10', true, false ), null );
	pr_dhl_assert( 'Retry in the same batch request with the fee saves the day', pr_dhl_place_order( pr_dhl_test_order( $with_fee, $first_attempt ), '2026-11-12', true, false ), '2026-11-12' );

	// Loading the checkout, for example after a declined card or when coming back from
	// a payment page, re-syncs the order from the cart before it is paid.
	pr_dhl_assert( 'Loading the checkout keeps a day the order still pays for', pr_dhl_load_checkout( pr_dhl_test_order( $with_fee, $first_attempt ) ), '2026-11-10' );
	pr_dhl_assert( 'Loading the checkout drops the day once the fee is gone', pr_dhl_load_checkout( pr_dhl_test_order( array(), $first_attempt ) ), null );

	// Paying for an existing order fires the request hook without the draft hook.
	pr_dhl_assert( 'Paying for an existing order keeps its preferred day', pr_dhl_place_order( pr_dhl_test_order( array(), array( 'pr_dhl_preferred_day' => '2026-11-10' ) ), null, false ), '2026-11-10' );
	pr_dhl_assert( 'Paying for an order without the fee cannot add a day', pr_dhl_place_order( pr_dhl_test_order( array() ), '2026-11-10', false ), null );

	// A shop with a decimal comma saves the cost as "1,20".
	$pr_dhl_test_decimal_separator = ',';
	$pr_dhl_test_settings          = array( 'dhl_preferred_day_cost' => '1,20' );
	pr_dhl_assert(
		'Decimal comma cost is charged as 1.20',
		pr_dhl_fees_after_callback(
			array(
				'action' => 'update_preferred_day_fee',
				'price'  => 1.2,
				'label'  => 'DHL Delivery Day',
			)
		),
		array( array( 'DHL Delivery Day', 1.2 ) )
	);
	pr_dhl_assert( 'Decimal comma cost: the order with the fee keeps the day', pr_dhl_place_order( pr_dhl_test_order( array( array( 'DHL Delivery Day', '1.2' ) ) ), '2026-11-10' ), '2026-11-10' );

	$integration  = ( new \ReflectionClass( \PR_DHL_Blocks_Integration::class ) )->newInstanceWithoutConstructor();
	$get_settings = new \ReflectionMethod( $integration, 'get_dhl_settings' );
	$get_settings->setAccessible( true );
	$cost_cases = array(
		array( ',', '1,20', '1.20' ),
		array( ',', '1.234,50', '1234.50' ),
		array( '.', '1.20', '1.20' ),
		array( '.', '5', '5' ),
		array( '.', '', false ),
	);
	foreach ( $cost_cases as $cost_case ) {
		$pr_dhl_test_decimal_separator = $cost_case[0];
		$pr_dhl_test_settings          = array( 'dhl_preferred_day_cost' => $cost_case[1] );
		$block_settings                = $get_settings->invoke( $integration );
		pr_dhl_assert( "Block gets a usable cost for '{$cost_case[1]}' with '{$cost_case[0]}' as decimal separator", $block_settings['preferred_day_cost'], $cost_case[2] );
	}
	$pr_dhl_test_decimal_separator = '.';
	$pr_dhl_test_settings          = array( 'dhl_preferred_day_cost' => '5' );

	echo $failures ? "\n{$failures} failure(s)\n" : "\nAll tests passed\n";
	exit( $failures ? 1 : 0 );
}
