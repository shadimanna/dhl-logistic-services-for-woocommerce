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

			/**
			 * Add a fee like WC_Cart::add_fee(), or like WC_Cart_Fees::add_fee() when
			 * given an array of fee props.
			 *
			 * @param string|array $name      Fee name, or the fee props.
			 * @param float        $amount    Fee amount.
			 * @param bool         $taxable   Whether the fee is taxable.
			 * @param string       $tax_class Tax class.
			 * @return object The fee.
			 */
			public function add_fee( $name, $amount = 0, $taxable = false, $tax_class = '' ) {
				$props = is_array( $name ) ? $name : array(
					'name'      => $name,
					'amount'    => $amount,
					'taxable'   => $taxable,
					'tax_class' => $tax_class,
				);
				$fee   = (object) array_merge(
					array(
						'taxable'   => false,
						'tax_class' => '',
					),
					$props
				);

				$fee->id = strtolower( str_replace( ' ', '-', $fee->name ) );

				if ( isset( $this->fees[ $fee->id ] ) ) {
					return new WP_Error();
				}

				$this->fees[ $fee->id ] = $fee;

				return $fee;
			}

			/**
			 * The test customer.
			 *
			 * @return PR_DHL_Test_Customer
			 */
			public function get_customer() {
				global $pr_dhl_test_no_customer;
				return empty( $pr_dhl_test_no_customer ) ? new PR_DHL_Test_Customer() : null;
			}

			/**
			 * Tax classes of the taxable cart items.
			 *
			 * @return array
			 */
			public function get_cart_item_tax_classes_for_shipping() {
				global $pr_dhl_test_tax;
				return $pr_dhl_test_tax['item_classes'];
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

			/**
			 * Tax on the fee line.
			 *
			 * @var string
			 */
			private $total_tax;

			/**
			 * Create a fee line.
			 *
			 * @param string $name      Fee name.
			 * @param string $total     Net amount.
			 * @param string $total_tax Tax on the line.
			 */
			public function __construct( $name, $total, $total_tax = '0' ) {
				$this->name      = $name;
				$this->total     = $total;
				$this->total_tax = $total_tax;
			}

			public function get_name() {
				return $this->name;
			}

			public function get_total() {
				return $this->total;
			}

			/**
			 * The tax on the fee line.
			 *
			 * @return string
			 */
			public function get_total_tax() {
				return $this->total_tax;
			}
		}
	}

	/**
	 * Shop tax setup for the tax tests: rates per country and tax class in percent.
	 */
	$pr_dhl_test_tax = array(
		'enabled'            => false,
		'country'            => 'DE',
		'vat_exempt'         => false,
		'shipping_tax_class' => 'inherit',
		'item_classes'       => array( '' ),
		'not_for_shipping'   => array(),
		'rates'              => array(
			'DE' => array(
				''             => 19,
				'reduced-rate' => 7,
			),
			'AT' => array(
				''             => 20,
				'reduced-rate' => 10,
			),
		),
	);

	if ( ! class_exists( 'PR_DHL_Test_Customer' ) ) {
		/**
		 * Minimal stand-in for WC_Customer.
		 */
		class PR_DHL_Test_Customer {
			/**
			 * Whether the customer is VAT exempt.
			 *
			 * @return bool
			 */
			public function get_is_vat_exempt() {
				global $pr_dhl_test_tax;
				return $pr_dhl_test_tax['vat_exempt'];
			}
		}
	}

	if ( ! class_exists( 'WC_Tax' ) ) {
		/**
		 * Minimal stand-in for WC_Tax with simple, non-compound rates.
		 */
		class WC_Tax {
			/**
			 * Rates of a tax class in a country.
			 *
			 * @param array $args Country and tax class.
			 * @return array
			 */
			public static function find_rates( $args = array() ) {
				global $pr_dhl_test_tax;
				$country   = $args['country'];
				$tax_class = isset( $args['tax_class'] ) ? $args['tax_class'] : '';

				if ( ! isset( $pr_dhl_test_tax['rates'][ $country ][ $tax_class ] ) ) {
					return array();
				}

				$rates = array();
				foreach ( (array) $pr_dhl_test_tax['rates'][ $country ][ $tax_class ] as $index => $percent ) {
					$rates[ 7 + $index ] = array(
						'rate'     => $percent,
						'label'    => 'VAT',
						'shipping' => in_array( $tax_class, $pr_dhl_test_tax['not_for_shipping'], true ) ? 'no' : 'yes',
						'compound' => 'no',
					);
				}
				return $rates;
			}

			/**
			 * Rates of a tax class in a country that apply to shipping.
			 *
			 * @param array $args Country and tax class.
			 * @return array
			 */
			public static function find_shipping_rates( $args = array() ) {
				return array_filter(
					self::find_rates( $args ),
					function ( $rate ) {
						return 'yes' === $rate['shipping'];
					}
				);
			}

			/**
			 * Rates of a tax class for the test customer's country, used by the cart for fees.
			 *
			 * @param string $tax_class Tax class.
			 * @param object $customer  Customer.
			 * @return array
			 */
			public static function get_rates( $tax_class = '', $customer = null ) {
				global $pr_dhl_test_tax;
				return self::find_rates(
					array(
						'country'   => $pr_dhl_test_tax['country'],
						'tax_class' => $tax_class,
					)
				);
			}

			/**
			 * The test customer's taxable address.
			 *
			 * @param string $tax_class Tax class.
			 * @param object $customer  Customer.
			 * @return array
			 */
			public static function get_tax_location( $tax_class = '', $customer = null ) {
				global $pr_dhl_test_tax;
				return array( $pr_dhl_test_tax['country'], '', '10115', 'Berlin' );
			}

			/**
			 * Tax on a price, keyed by rate ID. Included tax is split over the combined
			 * rate, as WC_Tax::calc_inclusive_tax() does for non-compound rates.
			 *
			 * @param float $price              Price.
			 * @param array $rates              Rates.
			 * @param bool  $price_includes_tax Whether the price includes the tax.
			 * @return array
			 */
			public static function calc_tax( $price, $rates, $price_includes_tax = false ) {
				$combined = 1 + array_sum( array_column( $rates, 'rate' ) ) / 100;
				$taxes    = array();
				foreach ( $rates as $id => $rate ) {
					$taxes[ $id ] = $price_includes_tax ? $price * ( $rate['rate'] / 100 ) / $combined : $price * $rate['rate'] / 100;
				}
				return $taxes;
			}

			/**
			 * Tax class slugs in settings order.
			 *
			 * @return array
			 */
			public static function get_tax_class_slugs() {
				return array( 'reduced-rate', 'zero-rate' );
			}
		}
	}
	if ( ! function_exists( 'wc_tax_enabled' ) ) {
		/**
		 * Whether the test shop charges tax.
		 *
		 * @return bool
		 */
		function wc_tax_enabled() {
			global $pr_dhl_test_tax;
			return $pr_dhl_test_tax['enabled'];
		}
	}
	if ( ! function_exists( 'get_option' ) ) {
		/**
		 * Test option values: only the shipping tax class is set.
		 *
		 * @param string $option        Option name.
		 * @param mixed  $default_value Value when the option is not set.
		 * @return mixed
		 */
		function get_option( $option, $default_value = false ) {
			global $pr_dhl_test_tax;
			return 'woocommerce_shipping_tax_class' === $option ? $pr_dhl_test_tax['shipping_tax_class'] : $default_value;
		}
	}
	if ( ! function_exists( 'wc_round_tax_total' ) ) {
		/**
		 * Round a tax amount to the store's two decimals.
		 *
		 * @param float    $value     Tax amount.
		 * @param int|null $precision Decimals.
		 * @return float
		 */
		function wc_round_tax_total( $value, $precision = null ) {
			return round( $value, null === $precision ? 2 : $precision );
		}
	}
	if ( ! function_exists( 'wc_add_number_precision_deep' ) ) {
		/**
		 * Convert amounts to cents, like WooCommerce does for a two-decimal store.
		 *
		 * @param float|array $value Amount or amounts.
		 * @return float|array
		 */
		function wc_add_number_precision_deep( $value ) {
			return is_array( $value ) ? array_map( 'wc_add_number_precision_deep', $value ) : round( $value * 100, 4 );
		}
	}
	if ( ! function_exists( 'is_wp_error' ) ) {
		/**
		 * Whether the value is a WP_Error.
		 *
		 * @param mixed $thing Value to check.
		 * @return bool
		 */
		function is_wp_error( $thing ) {
			return $thing instanceof WP_Error;
		}
	}
	if ( ! class_exists( 'WP_Error' ) ) {
		/**
		 * Minimal stand-in for WP_Error.
		 */
		class WP_Error {}
	}
	if ( ! class_exists( 'WC_Order_Item_Fee' ) ) {
		/**
		 * Minimal stand-in for WC_Order_Item_Fee, with WC_Order_Item::calculate_taxes().
		 */
		class WC_Order_Item_Fee {
			/**
			 * Line props as WooCommerce stores them.
			 *
			 * @var array
			 */
			public $props = array(
				'total'      => '0',
				'taxes'      => array(),
				'tax_class'  => '',
				'tax_status' => 'taxable',
			);

			/**
			 * Line meta.
			 *
			 * @var array
			 */
			public $meta = array();

			/**
			 * Net amount.
			 *
			 * @return string
			 */
			public function get_total() {
				return $this->props['total'];
			}

			/**
			 * Set the net amount.
			 *
			 * @param string $total Net amount.
			 */
			public function set_total( $total ) {
				$this->props['total'] = wc_format_decimal( $total );
			}

			/**
			 * Taxes per rate ID.
			 *
			 * @return array
			 */
			public function get_taxes() {
				return $this->props['taxes'];
			}

			/**
			 * Set the taxes; false removes them.
			 *
			 * @param array|false $taxes Taxes with a 'total' key.
			 */
			public function set_taxes( $taxes ) {
				$this->props['taxes'] = false === $taxes ? array() : array_map( 'wc_round_tax_total', $taxes['total'] );
			}

			/**
			 * Sum of the taxes.
			 *
			 * @return float
			 */
			public function get_total_tax() {
				return round( array_sum( $this->props['taxes'] ), 2 );
			}

			/**
			 * Tax class.
			 *
			 * @return string
			 */
			public function get_tax_class() {
				return $this->props['tax_class'];
			}

			/**
			 * Tax status.
			 *
			 * @return string
			 */
			public function get_tax_status() {
				return $this->props['tax_status'];
			}

			/**
			 * ID of the order the line belongs to.
			 *
			 * @return int
			 */
			public function get_order_id() {
				return 77;
			}

			/**
			 * Meta value, or an empty string.
			 *
			 * @param string $key Meta key.
			 * @return mixed
			 */
			public function get_meta( $key ) {
				return isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : '';
			}

			/**
			 * Set a meta value.
			 *
			 * @param string $key   Meta key.
			 * @param mixed  $value Meta value.
			 */
			public function update_meta_data( $key, $value ) {
				$this->meta[ $key ] = $value;
			}

			/**
			 * Recalculate the tax from the net amount like WC_Order_Item::calculate_taxes().
			 *
			 * @param array $calculate_tax_for Location.
			 */
			public function calculate_taxes( $calculate_tax_for ) {
				if ( '0' !== $this->get_tax_class() && 'taxable' === $this->get_tax_status() && wc_tax_enabled() ) {
					$calculate_tax_for['tax_class'] = $this->get_tax_class();
					$this->set_taxes( array( 'total' => WC_Tax::calc_tax( $this->get_total(), WC_Tax::find_rates( $calculate_tax_for ), false ) ) );
				} else {
					$this->set_taxes( false );
				}

				do_action( 'woocommerce_order_item_after_calculate_taxes', $this, $calculate_tax_for );
			}
		}
	}
	if ( ! function_exists( 'add_filter' ) ) {
		/**
		 * Record a filter callback.
		 *
		 * @param string   $hook          Filter name.
		 * @param callable $callback      Callback.
		 * @param int      $priority      Priority.
		 * @param int      $accepted_args Number of arguments the callback takes.
		 */
		function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
			add_action( $hook, $callback, $priority, $accepted_args );
		}
	}
	if ( ! function_exists( 'apply_filters' ) ) {
		/**
		 * Run the recorded callbacks of a filter.
		 *
		 * @param string $hook    Filter name.
		 * @param mixed  $value   Value to filter.
		 * @param mixed  ...$args Extra arguments.
		 * @return mixed
		 */
		function apply_filters( $hook, $value, ...$args ) {
			global $pr_dhl_test_hooks;
			if ( empty( $pr_dhl_test_hooks[ $hook ] ) ) {
				return $value;
			}
			ksort( $pr_dhl_test_hooks[ $hook ] );
			foreach ( $pr_dhl_test_hooks[ $hook ] as $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$value = call_user_func_array( $callback[0], array_slice( array_merge( array( $value ), $args ), 0, $callback[1] ) );
				}
			}
			return $value;
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

			/**
			 * Order total, as WC_Abstract_Order::calculate_totals() sets it.
			 *
			 * @var float
			 */
			public $total = 0.0;

			/**
			 * Tax location used by calculate_totals().
			 *
			 * @var array
			 */
			public $tax_location = array();

			/**
			 * Order total.
			 *
			 * @return float
			 */
			public function get_total() {
				return $this->total;
			}

			/**
			 * Set the order total.
			 *
			 * @param string|float $total Order total.
			 */
			public function set_total( $total ) {
				$this->total = (float) $total;
			}

			/**
			 * Calculate the fee line taxes like WC_Abstract_Order::calculate_taxes().
			 */
			public function calculate_taxes() {
				do_action( 'woocommerce_order_before_calculate_taxes', array(), $this );
				foreach ( $this->fees as $fee ) {
					if ( 'yes' === $this->get_meta( 'is_vat_exempt' ) ) {
						$fee->set_taxes( false );
					} else {
						$fee->calculate_taxes( $this->tax_location );
					}
				}
			}

			/**
			 * Calculate the totals of the fee lines like WC_Abstract_Order::calculate_totals():
			 * fees are added up first, then taxes are calculated, then the total is set,
			 * then the after action fires, then the order is saved.
			 *
			 * @param bool $and_taxes Whether to calculate taxes.
			 * @return float
			 */
			public function calculate_totals( $and_taxes = true ) {
				do_action( 'woocommerce_order_before_calculate_totals', $and_taxes, $this );

				$fees_total = 0.0;
				foreach ( $this->fees as $fee ) {
					$fees_total += (float) $fee->get_total();
				}

				if ( $and_taxes ) {
					$this->calculate_taxes();
				}

				$taxes = 0.0;
				foreach ( $this->fees as $fee ) {
					$taxes += $fee->get_total_tax();
				}
				$this->total = round( $fees_total + $taxes, 2 );

				do_action( 'woocommerce_order_after_calculate_totals', $and_taxes, $this );

				return $this->total;
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

	require __DIR__ . '/../includes/class-pr-dhl-preferred-day-fee.php';
	require __DIR__ . '/../includes/class-pr-dhl-extend-block-core.php';
	require __DIR__ . '/../includes/front-end/class-pr-dhl-front-end-paket.php';
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
			$test_order->fees[] = new PR_DHL_Test_Order_Fee( $fee[0], $fee[1], isset( $fee[2] ) ? $fee[2] : '0' );
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

	/**
	 * Calculate the cart fees the way WC_Cart_Totals::get_fees_from_cart() does and
	 * return each fee as [ name, net, tax, gross ] in the store currency.
	 *
	 * @param PR_DHL_Test_Cart $cart The cart.
	 * @return array
	 */
	function pr_dhl_fee_totals( PR_DHL_Test_Cart $cart ) {
		global $pr_dhl_test_tax;

		PR_DHL_Preferred_Day_Fee::init();

		$calculate_tax = $pr_dhl_test_tax['enabled'] && ! $pr_dhl_test_tax['vat_exempt'];
		$totals        = array();

		foreach ( $cart->get_fees() as $fee_object ) {
			$fee = (object) array(
				'object'    => $fee_object,
				'tax_class' => $fee_object->tax_class,
				'taxable'   => $fee_object->taxable,
				'total'     => wc_add_number_precision_deep( (float) $fee_object->amount ),
				'taxes'     => array(),
			);

			if ( $calculate_tax && $fee->taxable ) {
				$fee->taxes = WC_Tax::calc_tax( $fee->total, WC_Tax::get_rates( $fee->tax_class, $cart->get_customer() ), false );
			}

			$fee->taxes = apply_filters( 'woocommerce_cart_totals_get_fees_from_cart_taxes', $fee->taxes, $fee, null );
			$tax        = array_sum( array_map( 'round', $fee->taxes ) );

			$fee_object->total    = $fee->total / 100;
			$fee_object->tax_data = array_map(
				function ( $cents ) {
					return $cents / 100;
				},
				$fee->taxes
			);

			$totals[] = array( $fee_object->name, round( $fee->total / 100, 2 ), round( $tax / 100, 2 ), round( ( $fee->total + $tax ) / 100, 2 ) );
		}

		return $totals;
	}

	/**
	 * Select a day in the block checkout and return the fee totals.
	 *
	 * @return array
	 */
	function pr_dhl_block_fee_totals() {
		global $pr_dhl_test_session;
		$pr_dhl_test_session->data = array( 'pr_dhl_preferred_day_selected' => true );

		$cart = new PR_DHL_Test_Cart();
		pr_dhl_new_block_core()->add_preferred_day_fee( $cart );

		return pr_dhl_fee_totals( $cart );
	}

	/**
	 * Post a day in the classic checkout and return the fee totals.
	 *
	 * @return array
	 */
	function pr_dhl_classic_fee_totals() {
		$cart = new PR_DHL_Test_Cart();
		pr_dhl_classic_add_fee( $cart );

		return pr_dhl_fee_totals( $cart );
	}

	/**
	 * Run the classic checkout fee hook with a day posted.
	 *
	 * @param PR_DHL_Test_Cart $cart The cart.
	 */
	function pr_dhl_classic_add_fee( PR_DHL_Test_Cart $cart ) {
		global $pr_dhl_test_settings;

		$front_end = ( new \ReflectionClass( \PR_DHL_Front_End_Paket::class ) )->newInstanceWithoutConstructor();
		$settings  = new \ReflectionProperty( $front_end, 'shipping_dhl_settings' );
		$settings->setAccessible( true );
		$settings->setValue( $front_end, $pr_dhl_test_settings );

		$_POST = array( 'post_data' => 'pr_dhl_preferred_day=2026-11-10' );
		$front_end->add_cart_fees( $cart );
		$_POST = array();
	}

	/**
	 * Place a block checkout order with the fee and return its fee line, created the
	 * way WC_Checkout::create_order_fee_lines() does.
	 *
	 * @return WC_Order_Item_Fee
	 */
	function pr_dhl_order_fee_line() {
		global $pr_dhl_test_session;
		$pr_dhl_test_session->data = array( 'pr_dhl_preferred_day_selected' => true );

		$cart = new PR_DHL_Test_Cart();
		pr_dhl_new_block_core()->add_preferred_day_fee( $cart );
		pr_dhl_fee_totals( $cart );

		$fee  = current( $cart->get_fees() );
		$item = new WC_Order_Item_Fee();
		$item->set_total( $fee->total );
		$item->set_taxes( array( 'total' => $fee->tax_data ) );
		$item->props['tax_class']  = $fee->tax_class;
		$item->props['tax_status'] = $fee->taxable ? 'taxable' : 'none';
		do_action( 'woocommerce_checkout_create_order_fee_item', $item, $fee->id, $fee, null );

		return $item;
	}

	/**
	 * The net amount and tax of an order fee line.
	 *
	 * @param WC_Order_Item_Fee $item The fee line.
	 * @return array
	 */
	function pr_dhl_line_amounts( WC_Order_Item_Fee $item ) {
		return array( (float) $item->get_total(), $item->get_total_tax() );
	}

	/**
	 * Reset the shop tax setup to German VAT with standard-rate items, then apply changes.
	 *
	 * @param array $changes Settings to change.
	 */
	function pr_dhl_set_tax( array $changes = array() ) {
		global $pr_dhl_test_tax;
		$pr_dhl_test_tax = array_merge(
			$pr_dhl_test_tax,
			array(
				'enabled'            => true,
				'country'            => 'DE',
				'vat_exempt'         => false,
				'shipping_tax_class' => 'inherit',
				'item_classes'       => array( '' ),
				'not_for_shipping'   => array(),
			),
			$changes
		);
	}

	// The cost setting is a gross price: both checkouts charge exactly that amount,
	// with the VAT of the shipping tax class included in it. Fee amounts do not depend
	// on whether the shop enters prices incl. or excl. tax.
	$tax_cases = array(
		'Taxes disabled'                           => array( array( 'enabled' => false ), '5', array( 5.0, 0.0, 5.0 ) ),
		'Standard rate 19%'                        => array( array(), '5', array( 4.2, 0.8, 5.0 ) ),
		'Reduced-rate items only (7%)'             => array( array( 'item_classes' => array( 'reduced-rate' ) ), '5', array( 4.67, 0.33, 5.0 ) ),
		'Mixed cart follows the standard rate'     => array( array( 'item_classes' => array( 'reduced-rate', '' ) ), '5', array( 4.2, 0.8, 5.0 ) ),
		'Mixed reduced and zero rate cart'         => array( array( 'item_classes' => array( 'zero-rate', 'reduced-rate' ) ), '5', array( 4.67, 0.33, 5.0 ) ),
		'Shipping tax class set to reduced rate'   => array( array( 'shipping_tax_class' => 'reduced-rate' ), '5', array( 4.67, 0.33, 5.0 ) ),
		'Only non-taxable items'                   => array( array( 'item_classes' => array() ), '5', array( 5.0, 0.0, 5.0 ) ),
		'VAT exempt customer'                      => array( array( 'vat_exempt' => true ), '5', array( 5.0, 0.0, 5.0 ) ),
		'Customer in Austria (20%)'                => array( array( 'country' => 'AT' ), '5', array( 4.17, 0.83, 5.0 ) ),
		'Customer in a country without a tax rate' => array( array( 'country' => 'CH' ), '5', array( 5.0, 0.0, 5.0 ) ),
		'Cost 1.20 at 19%'                         => array( array(), '1.20', array( 1.01, 0.19, 1.2 ) ),
		'Cost 5.35 at 7%'                          => array( array( 'item_classes' => array( 'reduced-rate' ) ), '5.35', array( 5.0, 0.35, 5.35 ) ),
	);
	foreach ( $tax_cases as $label => $tax_case ) {
		list( $tax_changes, $cost, $want ) = $tax_case;

		pr_dhl_set_tax( $tax_changes );
		$pr_dhl_test_settings = array( 'dhl_preferred_day_cost' => $cost );
		$want_fees            = array( array_merge( array( 'DHL Delivery Day' ), $want ) );

		pr_dhl_assert( "Block checkout fee: {$label}", pr_dhl_block_fee_totals(), $want_fees );
		pr_dhl_assert( "Classic checkout fee: {$label}", pr_dhl_classic_fee_totals(), $want_fees );
	}
	$pr_dhl_test_settings = array( 'dhl_preferred_day_cost' => '5' );
	$five_incl_19         = array( array( 'DHL Delivery Day', 4.2, 0.8, 5.0 ) );

	// A rate not ticked for shipping does not apply to the fee, as it does not to shipping.
	pr_dhl_set_tax( array( 'not_for_shipping' => array( '' ) ) );
	pr_dhl_assert( 'Block checkout fee: rate not used for shipping', pr_dhl_block_fee_totals(), array( array( 'DHL Delivery Day', 5.0, 0.0, 5.0 ) ) );

	// The woocommerce_shipping_tax_class filter (WooCommerce 10.5+) changes the class for the fee too.
	pr_dhl_set_tax();
	$pr_dhl_test_session->data = array( 'pr_dhl_preferred_day_selected' => true );
	add_filter(
		'woocommerce_shipping_tax_class',
		function () {
			return 'reduced-rate';
		}
	);
	$cart = new PR_DHL_Test_Cart();
	pr_dhl_new_block_core()->add_preferred_day_fee( $cart );
	pr_dhl_assert( 'Shipping tax class filter: fee amounts', pr_dhl_fee_totals( $cart ), array( array( 'DHL Delivery Day', 4.67, 0.33, 5.0 ) ) );
	pr_dhl_assert( 'Shipping tax class filter: fee tax class', current( $cart->get_fees() )->tax_class, 'reduced-rate' );
	unset( $pr_dhl_test_hooks['woocommerce_shipping_tax_class'] );

	// The block and the classic hook both add the fee in one calculation: the second add is refused.
	$cart = new PR_DHL_Test_Cart();
	pr_dhl_new_block_core()->add_preferred_day_fee( $cart );
	pr_dhl_classic_add_fee( $cart );
	pr_dhl_assert( 'Block and classic hooks together charge the fee once', pr_dhl_fee_totals( $cart ), $five_incl_19 );

	// The order fee line keeps the gross amount when an admin recalculates the order's taxes.
	$germany              = array(
		'country'  => 'DE',
		'state'    => '',
		'postcode' => '10115',
		'city'     => 'Berlin',
	);
	$pr_dhl_test_settings = array( 'dhl_preferred_day_cost' => '2.10' );
	$line                 = pr_dhl_order_fee_line();
	pr_dhl_assert( 'Order fee line: 2.10 is saved as net plus tax', pr_dhl_line_amounts( $line ), array( 1.76, 0.34 ) );
	$line->calculate_taxes( $germany );
	pr_dhl_assert( 'Recalculate keeps 2.10 as 1.76 + 0.34', pr_dhl_line_amounts( $line ), array( 1.76, 0.34 ) );
	$line->calculate_taxes( array_merge( $germany, array( 'country' => 'CH' ) ) );
	pr_dhl_assert( 'Recalculate for an address without tax keeps 2.10', pr_dhl_line_amounts( $line ), array( 2.1, 0.0 ) );
	$line->calculate_taxes( $germany );
	pr_dhl_assert( 'Recalculate back in Germany splits 2.10 again', pr_dhl_line_amounts( $line ), array( 1.76, 0.34 ) );
	$line->set_total( '3.00' );
	$line->calculate_taxes( $germany );
	pr_dhl_assert( 'Recalculate leaves a fee line the admin edited to WooCommerce', pr_dhl_line_amounts( $line ), array( 3.0, 0.57 ) );

	$old_line = new WC_Order_Item_Fee();
	$old_line->set_total( '5' );
	$old_line->calculate_taxes( $germany );
	pr_dhl_assert( 'Recalculate on a fee line from before this change is unchanged', pr_dhl_line_amounts( $old_line ), array( 5.0, 0.95 ) );

	pr_dhl_set_tax( array( 'enabled' => false ) );
	$pr_dhl_test_settings = array( 'dhl_preferred_day_cost' => '5' );
	$untaxed_line         = pr_dhl_order_fee_line();
	pr_dhl_set_tax();
	$untaxed_line->calculate_taxes( $germany );
	pr_dhl_assert( 'Fee charged while taxes were off stays 5.00 after taxes are turned on', pr_dhl_line_amounts( $untaxed_line ), array( 5.0, 0.0 ) );

	// WooCommerce adds up the fees before it recalculates taxes, so the order total
	// must follow when the fee's net amount changes, here because the address moved.
	$pr_dhl_test_settings = array( 'dhl_preferred_day_cost' => '5' );
	$moved_order          = new WC_Order();
	$moved_order->id      = 77;
	$moved_order->fees    = array( pr_dhl_order_fee_line() );
	PR_DHL_Preferred_Day_Fee::init();
	$moved_order->tax_location = $germany;
	pr_dhl_assert( 'Order total with the fee in Germany', $moved_order->calculate_totals(), 5.0 );
	$moved_order->tax_location = array_merge( $germany, array( 'country' => 'CH' ) );
	$moved_order->calculate_totals();
	pr_dhl_assert( 'Order total after the address moved to a country without tax', $moved_order->total, 5.0 );
	$moved_order->tax_location = $germany;
	$moved_order->calculate_totals();
	pr_dhl_assert( 'Order total after the address moved back to Germany', array( $moved_order->total, pr_dhl_line_amounts( $moved_order->fees[0] ) ), array( 5.0, array( 4.2, 0.8 ) ) );

	// An order marked VAT exempt after checkout drops the fee tax without firing the item action.
	$moved_order->update_meta_data( 'is_vat_exempt', 'yes' );
	$moved_order->calculate_totals();
	pr_dhl_assert( 'VAT exempt order keeps the whole fee as net', array( $moved_order->total, pr_dhl_line_amounts( $moved_order->fees[0] ) ), array( 5.0, array( 5.0, 0.0 ) ) );
	$moved_order->delete_meta_data( 'is_vat_exempt' );
	$moved_order->calculate_totals();

	// Admin "Recalculate": taxes first, then the totals without taxes. The fees are
	// added up after the change, so the total must not be corrected a second time.
	$moved_order->tax_location = array_merge( $germany, array( 'country' => 'CH' ) );
	$moved_order->calculate_taxes();
	$moved_order->calculate_totals( false );
	pr_dhl_assert( 'Admin Recalculate after a move to Switzerland', array( $moved_order->total, pr_dhl_line_amounts( $moved_order->fees[0] ) ), array( 5.0, array( 5.0, 0.0 ) ) );
	$moved_order->tax_location = $germany;
	$moved_order->calculate_taxes();
	$moved_order->calculate_totals( false );
	pr_dhl_assert( 'Admin Recalculate back in Germany', array( $moved_order->total, pr_dhl_line_amounts( $moved_order->fees[0] ) ), array( 5.0, array( 4.2, 0.8 ) ) );

	// A refund copies the line meta onto a negative line; it must not be split.
	$refund_line = new WC_Order_Item_Fee();
	$refund_line->set_total( '-4.20' );
	$refund_line->meta = array(
		PR_DHL_Preferred_Day_Fee::GROSS_META => '5',
		PR_DHL_Preferred_Day_Fee::NET_META   => '4.20',
	);
	$refund_line->calculate_taxes( $germany );
	pr_dhl_assert( 'Refund fee line is left to WooCommerce', (float) $refund_line->get_total(), -4.2 );

	// Two rates on the same class: each is rounded on its own and net + tax still equals the cost.
	pr_dhl_set_tax();
	$pr_dhl_test_tax['rates']['DE'][''] = array( 19, 1.5 );
	pr_dhl_assert( 'Two rates: net plus both taxes is exactly 5.00', pr_dhl_block_fee_totals(), array( array( 'DHL Delivery Day', 4.15, 0.85, 5.0 ) ) );
	$pr_dhl_test_tax['rates']['DE'][''] = 19;

	// A single item class that is not in the settings list is used as it is, like WooCommerce does.
	pr_dhl_set_tax( array( 'item_classes' => array( 'custom-class' ) ) );
	$pr_dhl_test_tax['rates']['DE']['custom-class'] = 10;
	pr_dhl_assert( 'Single item class outside the settings list', pr_dhl_block_fee_totals(), array( array( 'DHL Delivery Day', 4.55, 0.45, 5.0 ) ) );
	unset( $pr_dhl_test_tax['rates']['DE']['custom-class'] );

	// Cart totals calculated without a customer (no session) charge the fee without tax.
	pr_dhl_set_tax();
	$pr_dhl_test_no_customer = true;
	pr_dhl_assert( 'No customer: the fee is charged without tax', pr_dhl_block_fee_totals(), array( array( 'DHL Delivery Day', 5.0, 0.0, 5.0 ) ) );
	$pr_dhl_test_no_customer = false;

	// WooCommerce does not apply the shipping tax class filter when no item is taxable.
	pr_dhl_set_tax( array( 'item_classes' => array() ) );
	add_filter(
		'woocommerce_shipping_tax_class',
		function () {
			return 'reduced-rate';
		}
	);
	pr_dhl_assert( 'Shipping tax class filter is not applied without taxable items', pr_dhl_block_fee_totals(), array( array( 'DHL Delivery Day', 5.0, 0.0, 5.0 ) ) );
	unset( $pr_dhl_test_hooks['woocommerce_shipping_tax_class'] );
	pr_dhl_set_tax();

	$pr_dhl_test_tax['enabled'] = false;
	$pr_dhl_test_settings       = array( 'dhl_preferred_day_cost' => '5' );

	// The order fee line holds the net amount and the tax separately.
	$paid_check = new \ReflectionMethod( \PR_DHL_Extend_Block_core::class, 'order_paid_preferred_day_fee' );
	$paid_check->setAccessible( true );
	$taxed_order       = new WC_Order();
	$taxed_order->fees = array( new PR_DHL_Test_Order_Fee( 'DHL Delivery Day', '4.20', '0.80' ) );
	pr_dhl_assert( 'Order with the fee as net plus tax paid for the preferred day', $paid_check->invoke( pr_dhl_new_block_core(), $taxed_order ), true );
	$taxed_order->fees = array( new PR_DHL_Test_Order_Fee( 'DHL Delivery Day', '4.20', '0.00' ) );
	pr_dhl_assert( 'Order with only the net part of the fee did not pay', $paid_check->invoke( pr_dhl_new_block_core(), $taxed_order ), false );
	pr_dhl_assert( 'Checkout with the fee as net plus tax saves the requested day', pr_dhl_place_order( pr_dhl_test_order( array( array( 'DHL Delivery Day', '4.20', '0.80' ) ) ), '2026-11-10' ), '2026-11-10' );

	echo $failures ? "\n{$failures} failure(s)\n" : "\nAll tests passed\n";
	exit( $failures ? 1 : 0 );
}
